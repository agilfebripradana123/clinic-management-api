<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Doctor;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Dokter 1 & 2 (Budi, Andi) — biar BookingSeeder punya jadwal
        $this->schedule('doctor1@clinic.com', [
            ['Monday', '08:00:00', '11:00:00'],
            ['Wednesday', '13:00:00', '16:00:00'],
        ]);

        $this->schedule('doctor2@clinic.com', [
            ['Tuesday', '08:00:00', '12:00:00'],
            ['Friday', '09:00:00', '14:00:00'],
        ]);

        // Dokter 3 (X7Agil) — beberapa jadwal
        $this->schedule('dokter@gmail.com', [
            ['Monday', '08:00:00', '11:00:00'],
            ['Wednesday', '13:00:00', '16:00:00'],
            ['Friday', '09:00:00', '12:00:00'],
        ]);
    }

    private function schedule(string $doctorEmail, array $rows): void
    {
        $doctor = Doctor::whereHas('user', fn ($q) => $q->where('email', $doctorEmail))->first();

        if (!$doctor) {
            return;
        }

        foreach ($rows as [$day, $start, $end]) {
            Schedule::create([
                'doctor_id' => $doctor->id,
                'day' => $day,
                'start_time' => $start,
                'end_time' => $end,
                'is_active' => true,
            ]);
        }
    }
}
