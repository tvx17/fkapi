<?php

namespace App\Actions\Systems;

use App\Enums\Api\SystemResponseCode;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class IndexAction
{
    use ApiResponse;

    public function handle(): JsonResponse
    {
        return $this->success(SystemResponseCode::Success, ['name' => 'LaravelAPI', 'status' => 'ok']);
    }
}
