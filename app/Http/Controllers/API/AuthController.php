<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\User\Resource;
use App\Jobs\Auth\LoginUserJob;
use App\Jobs\Auth\RegisterJob;
use App\Models\User;
use Illuminate\Http\Request;
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

        $user = LoginUserJob::dispatchSync($validated);

        return response()->json([
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    /**
     * Retrieve the authenticated user.
     */
    public function me(): Resource
    {
        $user = QueryBuilder::for(User::class)
            ->where('id', auth()->id())
            ->allowedIncludes(User::INCLUDES)
            ->firstOrFail();

        return new Resource($user);
    }

    public function register(RegisterRequest $request): Resource
    {
        $validated = $request->validated();

        $user = RegisterJob::dispatchSync($validated);

        $user->load(['roles.permissions']);

        return new Resource($user);
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(): \Illuminate\Http\JsonResponse
    {
        $user = User::query()
            ->where('id', auth()->id())
            ->firstOrFail();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }
}
