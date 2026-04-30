<?php

namespace App\Http\Controllers\API;

use App\Enums\PRFAppTopics;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SocialAuthRequest;
use App\Http\Requests\User\UpdateRequest;
use App\Http\Resources\User\Resource;
use App\Http\Resources\User\StudentResource;
use App\Jobs\Auth\LoginSocialLeaderJob;
use App\Jobs\Auth\LoginSocialUserJob;
use App\Jobs\Auth\LoginUserJob;
use App\Jobs\Auth\RegisterJob;
use App\Jobs\Auth\RegisterStudentJob;
use App\Models\APIClient;
use App\Models\Member;
use App\Models\Student;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\NewAccessToken;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class AuthController extends Controller
{
    /**
     * Handle the login request.
     *
     * @param  LoginRequest  $request  The login request.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $user = LoginUserJob::dispatchSync($validated);
            $apiClient = $this->resolveAPIClient($request);

            if ($apiClient && ! $apiClient->allowsUser($user)) {
                return response()->json([
                    'message' => 'You are not authorized to access this application.',
                ], 403);
            }

            return response()->json([
                'token' => $this->createTokenForClient($user, $apiClient)->plainTextToken,
            ]);
        } catch (Throwable $th) {
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
            ->allowedIncludes(...User::INCLUDES)
            ->firstOrFail();

        return new Resource($user);
    }

    public function register(RegisterRequest $request): Resource|JsonResponse
    {
        $validated = $request->validated();

        try {
            $user = RegisterJob::dispatchSync($validated);

            $user->load(['roles.permissions']);

            return new Resource($user);
        } catch (Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
            ], 422);
        }
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(): JsonResponse
    {
        $user = User::query()
            ->where('id', Auth::id())
            ->firstOrFail();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    public function registerStudent(Request $request): StudentResource
    {
        $results = RegisterStudentJob::dispatchSync();

        $user = $results[0];
        $password = $results[1];

        $user->load(['roles.permissions', 'student']);
        $apiClient = $this->resolveAPIClient($request);

        return (new StudentResource($user))
            ->additional([
                'token' => $this->createTokenForClient($user, $apiClient)->plainTextToken,
                'password' => $password,
            ]);
    }

    public function socialLogin(SocialAuthRequest $request): JsonResponse
    {
        $validated = $request->validated();
        try {
            $user = LoginSocialUserJob::dispatchSync($validated);
            $apiClient = $this->resolveAPIClient($request);

            if ($apiClient && ! $apiClient->allowsUser($user)) {
                return response()->json([
                    'message' => 'You are not authorized to access this application.',
                ], 403);
            }

            return response()->json([
                'token' => $this->createTokenForClient($user, $apiClient)->plainTextToken,
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
            $appTopic = PRFAppTopics::fromAppHeader($request->header('X-PRF-App', ''));

            $newTokenValues = (array) $validated['fcm_tokens'];

            // Remove entries matching both the same token AND same app, then add updated entries.
            // The same token may exist for different apps (same device, shared Firebase project).
            $data['fcm_tokens'] = collect((array) $user->fcm_tokens)
                ->reject(fn ($t) => is_array($t)
                    ? in_array($t['token'], $newTokenValues) && $t['app'] === $appTopic?->value
                    : in_array($t, $newTokenValues)
                )
                ->merge(collect($newTokenValues)->map(fn ($token) => [
                    'token' => $token,
                    'app' => $appTopic?->value,
                ]))
                ->values()
                ->all();
        }

        $user->update($data);
        $user->refresh();

        // Update the members table with tokens if available
        Member::where('user_id', $user->id)
            ->update([
                'fcm_tokens' => $user->fcm_tokens,
            ]);

        return new Resource($user);
    }

    public function updateStudentProfile(UpdateRequest $request): StudentResource
    {
        $validated = $request->validated();

        $user = User::findOrFail(Auth::id());

        $data = [];

        if (Arr::has($validated, 'fcm_tokens')) {
            $appTopic = PRFAppTopics::fromAppHeader($request->header('X-PRF-App', ''));

            $newTokenValues = (array) $validated['fcm_tokens'];

            $data['fcm_tokens'] = collect((array) $user->fcm_tokens)
                ->reject(fn ($t) => is_array($t)
                    ? in_array($t['token'], $newTokenValues) && $t['app'] === $appTopic?->value
                    : in_array($t, $newTokenValues)
                )
                ->merge(collect($newTokenValues)->map(fn ($token) => [
                    'token' => $token,
                    'app' => $appTopic?->value,
                ]))
                ->values()
                ->all();
        }

        $user->update($data);

        // Update the students table with tokens if available
        Student::where('user_id', $user->id)
            ->update([
                'fcm_tokens' => $user->fcm_tokens,
            ]);

        $user->refresh();
        $user->load(['roles.permissions', 'student']);

        return new StudentResource($user);
    }

    public function deleteStudentProfile(): JsonResponse
    {
        Student::where('user_id', Auth::id())->delete();
        User::where('id', Auth::id())->delete();

        return response()->json([
            'message' => 'Your profile has been deleted successfully',
        ], 204);
    }

    public function socialLeaderLogin(SocialAuthRequest $request): JsonResponse
    {
        $validated = $request->validated();
        try {
            $user = LoginSocialLeaderJob::dispatchSync($validated);
            $apiClient = $this->resolveAPIClient($request);

            if ($apiClient && ! $apiClient->allowsUser($user)) {
                return response()->json([
                    'message' => 'You are not authorized to access this application.',
                ], 403);
            }

            return response()->json([
                'token' => $this->createTokenForClient($user, $apiClient)->plainTextToken,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function resolveAPIClient(Request $request): ?APIClient
    {
        $appId = $request->header('X-PRF-App-ID');

        if (! $appId) {
            return null;
        }

        return APIClient::query()
            ->active()
            ->where('app_id', $appId)
            ->first();
    }

    private function createTokenForClient(User $user, ?APIClient $apiClient): NewAccessToken
    {
        $tokenName = $apiClient
            ? "auth_token:{$apiClient->app_id}"
            : 'auth_token';

        // Delete old tokens for the same app to enforce single-session per app
        $user->tokens()
            ->where('name', $tokenName)
            ->delete();

        $token = $user->createToken($tokenName);

        // Store the api_client_id on the token for later verification
        if ($apiClient) {
            $token->accessToken->update(['api_client_id' => $apiClient->id]);
        }

        return $token;
    }
}
