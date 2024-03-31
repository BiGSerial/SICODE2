<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserFakerCreate extends TestCase
{
    use WithFaker;
    /**
     * A basic feature test example.
     */
    public function testCreatingUser(): void
    {


        // Define o número de usuários a serem criados
        $numberOfUsers = 40;

        // Loop para criar múltiplos usuários
        for ($i = 0; $i < $numberOfUsers; $i++) {
            User::factory()->create([
                'name' => $this->faker->name,
                'email' => $this->faker->unique()->safeEmail,
                'password' => bcrypt('password'),
            ]);
        }

        // Verifica se todos os usuários foram criados corretamente no banco de dados
        $this->assertCount($numberOfUsers, User::all());


    }
}
