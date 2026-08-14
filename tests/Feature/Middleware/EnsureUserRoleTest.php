<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Http\Middleware\EnsureUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureUserRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_test/admin-role', function () {
            return response()->json([
                'success' => true,
            ]);
        })->middleware(EnsureUserRole::class . ':admin');
    }

    public function test_user_with_allowed_role_can_access_page(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/_test/admin-role');

        $response->assertOk();

        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_authenticated_user_with_wrong_role_is_redirected_to_participant_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/_test/admin-role');

        $response->assertRedirect(
            route('participant.dashboard')
        );

        $response->assertSessionHasErrors([
            'authorization' =>
                'Akses ditolak: halaman ini hanya untuk role yang diizinkan.',
        ]);
    }

    public function test_guest_is_redirected_to_participant_login(): void
    {
        $response = $this->get('/_test/admin-role');

        $response->assertRedirect(
            route('participant.login')
        );

        $response->assertSessionHasErrors([
            'authorization' =>
                'Akses ditolak: halaman ini hanya untuk role yang diizinkan.',
        ]);
    }

    public function test_authenticated_user_with_wrong_role_gets_403_for_json_request(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this
            ->actingAs($user)
            ->getJson('/_test/admin-role');

        $response->assertForbidden();

        $response->assertJson([
            'message' =>
                'Anda tidak memiliki akses ke halaman ini.',
        ]);
    }
}
