<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\Api\AuthResource;
use App\Services\Auth\LoginService;
use App\Services\Auth\LogoutService;
use App\Services\Auth\RegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private LoginService $loginService,
        private RegisterService $registerService,
        private LogoutService $logoutService,
    ) {}

    public function login(LoginRequest $request): AuthResource
    {
        $data = $this->loginService->login($request->validated());

        return new AuthResource($data);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $this->registerService->register($request->validated());

        return (new AuthResource($data))
            ->response()
            ->setStatusCode(201);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        $this->logoutService->logout($token);

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }
}
