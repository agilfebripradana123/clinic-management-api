<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::create([
            'role' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@clinic.com',
            'password' => 'password',
        ]);

        // Dokter 1
        User::create([
            'role' => 'doctor',
            'name' => 'dr. Budi Santoso',
            'email' => 'doctor1@clinic.com',
            'password' => 'password',
        ]);

        // Dokter 2
        User::create([
            'role' => 'doctor',
            'name' => 'dr. Andi Wijaya',
            'email' => 'doctor2@clinic.com',
            'password' => 'password',
        ]);

        // Dokter 3
        User::create([
            'role' => 'doctor',
            'name' => 'dr. X7Agil',
            'email' => 'dokter@gmail.com',
            'password' => 'password',
        ]);

        // Dokter 4
        User::create([
            'role' => 'doctor',
            'name' => 'dr. Rania Putri',
            'email' => 'doctor4@clinic.com',
            'password' => 'password',
        ]);

        // Dokter 5
        User::create([
            'role' => 'doctor',
            'name' => 'dr. Dimas Prakoso',
            'email' => 'doctor5@clinic.com',
            'password' => 'password',
        ]);

        // Dokter 6
        User::create([
            'role' => 'doctor',
            'name' => 'dr. Sari Wulandari',
            'email' => 'doctor6@clinic.com',
            'password' => 'password',
        ]);

        // Dokter 7
        User::create([
            'role' => 'doctor',
            'name' => 'dr. Fajar Nugroho',
            'email' => 'doctor7@clinic.com',
            'password' => 'password',
        ]);

        // Pasien 1
        User::create([
            'role' => 'patient',
            'name' => 'Agil Febri',
            'email' => 'patient1@clinic.com',
            'password' => 'password',
        ]);

        // Pasien 2
        User::create([
            'role' => 'patient',
            'name' => 'Budi Prasetyo',
            'email' => 'patient2@clinic.com',
            'password' => 'password',
        ]);
    }
}