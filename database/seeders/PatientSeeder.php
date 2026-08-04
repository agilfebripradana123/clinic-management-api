<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        Patient::create([
            'user_id' => 9,
            'medical_record_number' => 'RM-0001',
            'gender' => 'L',
            'birth_date' => '2002-04-16',
            'phone' => '081111111111',
            'address' => 'Yogyakarta',
            'is_active' => true,
        ]);

        Patient::create([
            'user_id' => 10,
            'medical_record_number' => 'RM-0002',
            'gender' => 'L',
            'birth_date' => '2001-08-21',
            'phone' => '082222222222',
            'address' => 'Bantul',
            'is_active' => true,
        ]);
    }
}