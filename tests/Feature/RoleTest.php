<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'manage privileges']);
    }

    public function test_admin_can_list_roles(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage privileges');

        $response = $this->actingAs($user)->get('/admin/roles');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_role(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage privileges');

        $response = $this->actingAs($user)->post('/admin/roles', [
            'name' => 'New Role',
            'permissions' => [],
        ]);

        $response->assertRedirect('/admin/roles');
        $this->assertDatabaseHas('roles', ['name' => 'New Role']);
    }

    public function test_admin_can_update_role(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage privileges');
        $role = Role::create(['name' => 'Old Role']);

        $response = $this->actingAs($user)->put("/admin/roles/{$role->id}", [
            'name' => 'Updated Role',
            'permissions' => [],
        ]);

        $response->assertRedirect('/admin/roles');
        $this->assertDatabaseHas('roles', ['name' => 'Updated Role']);
    }

    public function test_admin_can_delete_role(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage privileges');
        $role = Role::create(['name' => 'Delete Me']);

        $response = $this->actingAs($user)->delete("/admin/roles/{$role->id}");

        $response->assertRedirect('/admin/roles');
        $this->assertDatabaseMissing('roles', ['name' => 'Delete Me']);
    }
}
