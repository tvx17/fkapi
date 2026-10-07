<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
    }

    public function test_users_without_admin_permission_are_denied(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('run-artisan-runner-ui', 'web'));

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_explicit_admin_access_allows_the_dashboard_without_runner_access(): void
    {
        $user = User::factory()->create();

        $this->artisan('admin:grant-access', ['email' => $user->email])->assertSuccessful();

        $this->actingAs($user->fresh())->get('/admin')->assertOk();
        $this->assertFalse($user->fresh()->checkPermissionTo('run-artisan-runner-ui', 'web'));
    }

    public function test_granting_access_to_an_unknown_user_does_not_create_a_user_or_permission(): void
    {
        $this->artisan('admin:grant-access', ['email' => 'missing@example.com'])->assertFailed();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseMissing('permissions', ['name' => 'access-admin-panel']);
    }
}
