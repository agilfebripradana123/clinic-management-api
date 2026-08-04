<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Schedule;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        // Cari referensi by relasi (bukan id hardcoded) agar tidak salah
        // saat urutan user/doctor berubah.
        $doctor1 = Doctor::whereHas('user', fn ($q) => $q->where('email', 'doctor1@clinic.com'))->first();
        $doctor2 = Doctor::whereHas('user', fn ($q) => $q->where('email', 'doctor2@clinic.com'))->first();
        $patient1 = Patient::where('medical_record_number', 'RM-0001')->first();
        $patient2 = Patient::where('medical_record_number', 'RM-0002')->first();
        $schedule1 = Schedule::where('doctor_id', $doctor1?->id)->first();
        $schedule2 = Schedule::where('doctor_id', $doctor2?->id)->first();

        if ($doctor1 && $patient1 && $schedule1) {
            Booking::create([
                'doctor_id' => $doctor1->id,
                'patient_id' => $patient1->id,
                'schedule_id' => $schedule1->id,
                'booking_code' => 'BK-20260811-001',
                'booking_date' => '2026-08-11',
                'queue_number' => 1,
                'status' => 'confirmed',
                'notes' => 'Batuk dan demam sejak 3 hari.',
            ]);
        }

        if ($doctor2 && $patient2 && $schedule2) {
            Booking::create([
                'doctor_id' => $doctor2->id,
                'patient_id' => $patient2->id,
                'schedule_id' => $schedule2->id,
                'booking_code' => 'BK-20260812-001',
                'booking_date' => '2026-08-12',
                'queue_number' => 1,
                'status' => 'pending',
                'notes' => 'Kontrol gigi.',
            ]);
        }
    }
}
