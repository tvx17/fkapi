<?php

namespace Tests\Feature;

use App\Enums\Api\UserResponseCode;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiTest extends TestCase
{
    public function test_root_endpoint_uses_the_same_response_structure(): void
    {
        $this->get('/')->assertOk()->assertExactJson([
            'code' => '00001',
            'status' => true,
            'data' => ['name' => 'LaravelAPI', 'status' => 'ok'],
            'responseStatus' => 200,
        ]);
    }

    public function test_user_codes_keep_their_leading_zero_and_set_status_automatically(): void
    {
        $responder = new class
        {
            use ApiResponse;
        };

        $success = $responder->success(UserResponseCode::LoginSuccessful, ['id' => 42]);
        $this->assertSame(200, $success->getStatusCode());
        $this->assertSame([
            'code' => '05001', 'status' => true, 'data' => ['id' => 42], 'responseStatus' => 200,
        ], $success->getData(true));

        $error = $responder->error(UserResponseCode::CredentialsError, null, 401);
        $this->assertSame(401, $error->getStatusCode());
        $this->assertSame([
            'code' => '05002', 'status' => false, 'data' => null, 'responseStatus' => 401,
        ], $error->getData(true));
    }

    public function test_validation_errors_keep_field_details_inside_data(): void
    {
        Route::post('/api/test-validation', function (Request $request): void {
            $request->validate(['email' => ['required', 'email']]);
        });

        $this->postJson('/api/test-validation', [])->assertUnprocessable()
            ->assertJsonPath('code', '00422')
            ->assertJsonPath('status', false)
            ->assertJsonPath('responseStatus', 422)
            ->assertJsonStructure(['data' => ['message', 'errors' => ['email']]]);
    }

    public function test_unauthenticated_api_requests_use_the_error_structure(): void
    {
        Route::get('/api/test-auth', fn () => null)->middleware('auth:web');

        $this->get('/api/test-auth')->assertUnauthorized()
            ->assertJsonPath('code', '00401')
            ->assertJsonPath('status', false)
            ->assertJsonPath('responseStatus', 401);
    }

    public function test_error_headers_are_preserved(): void
    {
        Route::get('/api/test-throttle', fn () => abort(429, 'Too many requests.', ['Retry-After' => '60']));

        $this->get('/api/test-throttle')->assertTooManyRequests()
            ->assertHeader('Retry-After', '60')
            ->assertJsonPath('code', '00429')
            ->assertJsonPath('responseStatus', 429);
    }

    public function test_server_errors_do_not_expose_exception_details_even_in_debug_mode(): void
    {
        config(['app.debug' => true]);
        Route::get('/api/test-error', fn () => throw new \RuntimeException('private exception details'));

        $this->get('/api/test-error')->assertStatus(500)->assertExactJson([
            'code' => '00500', 'status' => false,
            'data' => ['message' => 'Server error.'], 'responseStatus' => 500,
        ]);
    }

    public function test_status_endpoint_returns_json_without_an_accept_header(): void
    {
        $this->get('/api/status')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertExactJson([
                'code' => '00001',
                'status' => true,
                'data' => ['status' => 'ok'],
                'responseStatus' => 200,
            ]);
    }

    public function test_unknown_api_endpoint_returns_a_json_error(): void
    {
        $this->get('/api/missing')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('code', '00404')
            ->assertJsonPath('status', false)
            ->assertJsonPath('responseStatus', 404)
            ->assertJsonStructure(['data' => ['message']]);
    }
}
