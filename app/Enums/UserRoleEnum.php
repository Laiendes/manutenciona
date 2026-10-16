<?php

namespace App\Enums;

enum UserRoleEnum: string
{
    case Requester = 'requester';
    case Admin = 'admin';
}
