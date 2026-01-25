<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::create(['name' => 'manage privileges']);
    }

    public function test_admin_can_list_permissions(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage privileges');

        $response = $this->actingAs($user)->get('/admin/permissions');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage privileges');

        $response = $this->actingAs($user)->post('/admin/permissions', [
            'name' => 'new permission',
        ]);

        $response->assertRedirect('/admin/permissions');
        $this->assertDatabaseHas('permissions', ['name' => 'new permission']);
    }

    public function test_admin_can_update_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage privileges');
        $permission = Permission::create(['name' => 'old permission']);

        $response = $this->actingAs($user)->put("/admin/permissions/{$permission->id}", [
            'name' => 'updated permission',
        ]);

        $response->assertRedirect('/admin/permissions');
        $this->assertDatabaseHas('permissions', ['name' => 'updated permission']);
    }

    public function test_admin_can_delete_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('manage privileges');
        $permission = Permission::create(['name' => 'delete me']);

        $response = $this->actingAs($user)->delete("/admin/permissions/{$permission->id}");

        $response->assertRedirect('/admin/permissions');
        $this->assertDatabaseMissing('permissions', ['name' => 'delete me']);
    }
}
