<?php

namespace App\Services\Auth;

use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;

class RegisterService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function register(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $user->role = 'requester';
            $user->save();

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        });
    }
}
