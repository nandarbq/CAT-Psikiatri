<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(array $overrides = []): User
    {
        $admin = User::query()->create(array_merge([
            'name' => 'Admin',
            'username' => 'admin_test',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('password123'),
        ], $overrides));

        $this->actingAs($admin);

        return $admin;
    }

    public function test_admin_can_create_update_and_delete_internal_user(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.users.store'), [
            'name' => 'Admin Baru',
            'username' => 'admin_baru',
            'email' => 'admin.baru@example.com',
            'role' => User::ROLE_ADMIN,
            'password' => 'password123',
        ])->assertRedirect(route('admin.users.index'));

        $created = User::query()->where('username', 'admin_baru')->firstOrFail();
        $this->assertDatabaseHas('users', [
            'id' => $created->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $this->put(route('admin.users.update', $created), [
            'name' => 'Admin Diperbarui',
            'username' => 'admin_baru',
            'email' => 'admin.baru@example.com',
            'role' => User::ROLE_ADMIN,
            'password' => '',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $created->id,
            'name' => 'Admin Diperbarui',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->delete(route('admin.users.destroy', $created))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $created->id,
        ]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->actingAsAdmin();

        $this->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('user');
    }

    public function test_cannot_delete_last_admin(): void
    {
        $admin = $this->actingAsAdmin();

        $this->from(route('admin.users.index'))
            ->post(route('admin.users.bulk'), [
                'ids' => [$admin->id],
                'action' => 'delete',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_bulk_delete_skips_own_account_and_keeps_last_admin(): void
    {
        $admin = $this->actingAsAdmin();

        $admin2 = User::query()->create([
            'name' => 'Admin Dua',
            'username' => 'admin_dua',
            'email' => 'admin.dua@example.com',
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('password123'),
        ]);

        $this->post(route('admin.users.bulk'), [
            'ids' => [$admin->id, $admin2->id],
            'action' => 'delete',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('users', ['id' => $admin2->id]);
        $this->assertSame(1, User::query()->where('role', User::ROLE_ADMIN)->count());
    }
}
