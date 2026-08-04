<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        Doctor::create([
            'user_id' => 2,
            'photo' => null,
            'license_number' => 'DOC-0001',
            'specialist' => 'Dokter Umum',
            'phone' => '081234567890',
            'address' => 'Yogyakarta',
        ]);

        Doctor::create([
            'user_id' => 3,
            'photo' => null,
            'license_number' => 'DOC-0002',
            'specialist' => 'Dokter Gigi',
            'phone' => '081234567891',
            'address' => 'Sleman',
        ]);

        Doctor::create([
            'user_id' => 4,
            'photo' => null,
            'license_number' => 'DOC-1785697826',
            'specialist' => 'Dokter Hewan',
            'phone' => '08123123232',
            'address' => 'Yogyakarta',
            'is_active' => true,
        ]);

        Doctor::create([
            'user_id' => 5,
            'photo' => null,
            'license_number' => 'DOC-0003',
            'specialist' => 'Dokter Anak',
            'phone' => '081298765432',
            'address' => 'Bantul',
            'is_active' => true,
        ]);

        Doctor::create([
            'user_id' => 6,
            'photo' => null,
            'license_number' => 'DOC-0004',
            'specialist' => 'Dokter Mata',
            'phone' => '081298765433',
            'address' => 'Sleman',
            'is_active' => true,
        ]);

        Doctor::create([
            'user_id' => 7,
            'photo' => null,
            'license_number' => 'DOC-0005',
            'specialist' => 'Dokter Kulit',
            'phone' => '081298765434',
            'address' => 'Yogyakarta',
            'is_active' => true,
        ]);

        Doctor::create([
            'user_id' => 8,
            'photo' => null,
            'license_number' => 'DOC-0006',
            'specialist' => 'Dokter Saraf',
            'phone' => '081298765435',
            'address' => 'Kulon Progo',
            'is_active' => true,
        ]);
    }
}