<?php

namespace App\Http\Controllers;

use App\Actions\Systems\IndexAction;
use App\Actions\Systems\StatusAction;
use Illuminate\Http\JsonResponse;

class SystemController extends Controller
{
    public function index(IndexAction $action): JsonResponse
    {
        return $action->handle();
    }

    public function status(StatusAction $action): JsonResponse
    {
        return $action->handle();
    }
}
