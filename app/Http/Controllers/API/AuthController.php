<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SocialAuthRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Http\Resources\User\Resource;
use App\Http\Resources\User\StudentResource;
use App\Jobs\Auth\LoginSocialUserJob;
use App\Jobs\Auth\LoginUserJob;
use App\Jobs\Auth\RegisterJob;
use App\Jobs\Auth\RegisterStudentJob;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\QueryBuilder;

class AuthController extends Controller
{
    /**
     * Handle the login request.
     *
     * @param  LoginRequest  $request  The login request.
     */
    public function login(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            $user = LoginUserJob::dispatchSync($validated);

            return response()->json([
                'token' => $user->createToken('auth_token')->plainTextToken,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Retrieve the authenticated user.
     */
    public function me(): Resource
    {
        $user = QueryBuilder::for(User::class)
            ->where('id', Auth::id())
            ->allowedIncludes(User::INCLUDES)
            ->firstOrFail();

        return new Resource($user);
    }

    public function register(RegisterRequest $request): Resource|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            $user = RegisterJob::dispatchSync($validated);

            $user->load(['roles.permissions']);

            return new Resource($user);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(): \Illuminate\Http\JsonResponse
    {
        $user = User::query()
            ->where('id', Auth::id())
            ->firstOrFail();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    public function registerStudent(): StudentResource
    {
        $results = RegisterStudentJob::dispatchSync();

        $user = $results[0];
        $password = $results[1];

        $user->load(['roles.permissions', 'student']);

        return (new StudentResource($user))
            ->additional([
                'token' => $user->createToken('auth_token')->plainTextToken,
                'password' => $password,
            ]);
    }

    public function socialLogin(SocialAuthRequest $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();
        try {
            $user = LoginSocialUserJob::dispatchSync($validated);

            return response()->json([
                'token' => $user->createToken('auth_token')->plainTextToken,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateProfile(UpdateRequest $request): Resource
    {
        $validated = $request->validated();

        $user = User::findOrFail(Auth::id());

        $data = [];

        if (Arr::has($validated, 'timezone')) {
            $data['timezone'] = $validated['timezone'];
        }

        if (Arr::has($validated, 'fcm_tokens')) {
            $data['fcm_tokens'] = collect([
                ...((array) $user->fcm_tokens),
                ...((array) $validated['fcm_tokens']),
            ])->unique()->values()->all();
        }

        $user->update($data);
        $user->refresh();

        return new Resource($user);
    }
}
