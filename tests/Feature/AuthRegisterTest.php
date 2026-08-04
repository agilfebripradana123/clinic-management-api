<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_can_register_with_auto_created_patient_record(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Ayu Putri',
            'email' => 'ayu@example.com',
            'password' => 'password123',
            'gender' => 'P',
            'birth_date' => '2000-05-15',
            'phone' => '081234567890',
            'address' => 'Bandung',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.role', 'patient');

        $this->assertDatabaseHas('users', [
            'email' => 'ayu@example.com',
            'role' => 'patient',
        ]);

        $this->assertDatabaseHas('patients', [
            'user_id' => User::where('email', 'ayu@example.com')->value('id'),
            'gender' => 'P',
        ]);

        $this->assertSame('patient', User::where('email', 'ayu@example.com')->first()->role);
        $this->assertDatabaseCount('patients', 1);
    }
}
