<?php

namespace App\Support;

use App\Enums\Api\SystemResponseCode;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiExceptionResponse
{
    use ApiResponse;

    public function handle(Response $response): Response
    {
        $httpStatus = $response->getStatusCode();

        if ($httpStatus < 400) {
            return $response;
        }

        $code = match ($httpStatus) {
            400 => SystemResponseCode::BadRequest,
            401 => SystemResponseCode::Unauthenticated,
            403 => SystemResponseCode::Forbidden,
            404 => SystemResponseCode::NotFound,
            405 => SystemResponseCode::MethodNotAllowed,
            422 => SystemResponseCode::ValidationError,
            429 => SystemResponseCode::TooManyRequests,
            default => $httpStatus >= 500 ? SystemResponseCode::ServerError : SystemResponseCode::RequestError,
        };

        $original = $response instanceof JsonResponse ? $response->getData(true) : [];
        $data = ['message' => $httpStatus >= 500 ? 'Server error.' : ($original['message'] ?? Response::$statusTexts[$httpStatus] ?? 'Request failed.')];

        if ($httpStatus === 422 && isset($original['errors'])) {
            $data['errors'] = $original['errors'];
        }

        $payload = $this->error($code, $data, $httpStatus)->getData(true);

        // Keep framework headers such as Retry-After, Allow and cookies intact.
        if ($response instanceof JsonResponse) {
            return $response->setData($payload);
        }

        $json = response()->json($payload, $httpStatus);
        $json->headers->add($response->headers->all());
        $json->headers->set('Content-Type', 'application/json');

        return $json;
    }
}
