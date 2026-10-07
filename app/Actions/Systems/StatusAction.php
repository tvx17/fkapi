<?php

namespace App\Actions\Systems;

use App\Enums\Api\SystemResponseCode;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class StatusAction
{
    use ApiResponse;

    public function handle(): JsonResponse
    {
        return $this->success(SystemResponseCode::Success, ['status' => 'ok']);
    }
}
