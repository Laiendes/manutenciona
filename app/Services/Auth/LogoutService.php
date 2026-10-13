<?php

namespace App\Services\Auth;

use App\Repositories\PersonalAccessTokenRepository;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutService
{
    public function __construct(
        private PersonalAccessTokenRepository $personalAccessTokenRepository,
    ) {}

    public function logout(PersonalAccessToken $token): void
    {
        $this->personalAccessTokenRepository->delete($token);
    }
}
