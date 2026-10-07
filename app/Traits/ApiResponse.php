<?php

namespace App\Traits;

use BackedEnum;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

trait ApiResponse
{
    public function success(BackedEnum $code, mixed $data = null, int $responseStatus = 200): JsonResponse
    {
        return $this->apiResponse($code, true, $data, $responseStatus);
    }

    public function error(BackedEnum $code, mixed $data = null, int $responseStatus = 400): JsonResponse
    {
        return $this->apiResponse($code, false, $data, $responseStatus);
    }

    private function apiResponse(BackedEnum $code, bool $status, mixed $data, int $responseStatus): JsonResponse
    {
        if (! is_string($code->value) || ! preg_match('/^\d{5}$/D', $code->value)) {
            throw new InvalidArgumentException('API-Antwortcodes müssen fünfstellige String-Enum-Werte sein.');
        }

        return response()->json([
            'code' => $code->value,
            'status' => $status,
            'data' => $data,
            'responseStatus' => $responseStatus,
        ], $responseStatus);
    }
}
