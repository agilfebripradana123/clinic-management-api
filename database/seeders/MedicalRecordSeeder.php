<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\MedicalRecord;

class MedicalRecordSeeder extends Seeder
{
    public function run(): void
    {
        $this->record('BK-20260811-001', [
            'complaint' => 'Batuk dan demam sejak 3 hari.',
            'diagnosis' => 'Infeksi Saluran Pernapasan Atas (ISPA)',
            'treatment' => 'Pemeriksaan fisik dan pemberian terapi.',
            'prescription' => 'Paracetamol 500mg, Amoxicillin 500mg',
            'notes' => 'Kontrol kembali dalam 5 hari jika belum membaik.',
        ]);

        $this->record('BK-20260812-001', [
            'complaint' => 'Kontrol kesehatan gigi.',
            'diagnosis' => 'Karies gigi ringan.',
            'treatment' => 'Pembersihan karang gigi.',
            'prescription' => 'Obat kumur antiseptik.',
            'notes' => 'Sikat gigi minimal 2 kali sehari.',
        ]);
    }

    private function record(string $bookingCode, array $data): void
    {
        $booking = Booking::where('booking_code', $bookingCode)->first();

        if (!$booking) {
            return;
        }

        MedicalRecord::create([
            'booking_id' => $booking->id,
            ...$data,
        ]);
    }
}
