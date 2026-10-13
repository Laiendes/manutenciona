<?php

namespace App\Repositories;

use Laravel\Sanctum\PersonalAccessToken;

class PersonalAccessTokenRepository
{
    public function delete(PersonalAccessToken $token): void
    {
        $token->delete();
    }
}
