<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    #[OA\Post(
        path: '/auth/login',
        summary: 'Authenticate and issue a Sanctum token',
        security: [],
        responses: [new OA\Response(response: 200, description: 'Login token and user details')]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->email,
            $request->password
        );

        $user = $result['user']->load(['tenant', 'organization', 'company', 'department', 'team', 'roles', 'permissions']);

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $result['token'],
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $revoked = $this->authService->logoutAll($request->user());

        return response()->json([
            'success' => true,
            'message' => 'All active sessions have been revoked.',
            'data' => [
                'revoked_tokens' => $revoked,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['tenant', 'organization', 'company', 'department', 'team', 'roles', 'permissions']);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }
}