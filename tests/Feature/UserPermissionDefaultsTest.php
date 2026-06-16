<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_null_boolean_permissions_are_saved_as_false(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'permission-defaults@example.com',
            'password' => 'password',
            'analyst' => null,
            'legal_controller' => null,
            'legal_field' => null,
            'legal_manager' => null,
        ]);

        $this->assertFalse($user->analyst);
        $this->assertFalse($user->legal_controller);
        $this->assertFalse($user->legal_field);
        $this->assertFalse($user->legal_manager);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'analyst' => false,
            'legal_controller' => false,
            'legal_field' => false,
            'legal_manager' => false,
        ]);
    }
}
