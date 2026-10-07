<?php

namespace App\Enums\Api;

enum SystemResponseCode: string
{
    case Success = '00001';
    case BadRequest = '00400';
    case Unauthenticated = '00401';
    case Forbidden = '00403';
    case NotFound = '00404';
    case MethodNotAllowed = '00405';
    case ValidationError = '00422';
    case TooManyRequests = '00429';
    case ServerError = '00500';
    case RequestError = '00999';
}
