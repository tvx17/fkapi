<?php

namespace App\Enums\Api;

enum UserResponseCode: string
{
    case LoginSuccessful = '05001';
    case CredentialsError = '05002';
}
