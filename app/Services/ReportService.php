<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Schedule;

class ReportService
{
    /** Nama bulan pendek Bahasa Indonesia (ganti "Agt" yang dihasilkan Carbon 'en'). */
    protected const SHORT_MONTHS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    public function summary()
    {
        return [
            'total_patients' => Patient::count(),
            'total_doctors' => Doctor::count(),
            'total_bookings' => Booking::count(),
            'total_medical_records' => MedicalRecord::count(),
            'total_schedules' => Schedule::count(),
            'today_bookings' => Booking::whereDate('booking_date', today())->count(),
            'completed_bookings' => Booking::where('status', 'completed')->count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),

            // --- Tambahan untuk dashboard & laporan ---
            'booking_trend' => $this->bookingTrend(7),
            'status_breakdown' => $this->statusBreakdown(),
            'top_doctors' => $this->topDoctors(5),
            'gender_split' => $this->genderSplit(),
            'monthly_records' => $this->monthlyRecords(6),
        ];
    }

    /**
     * Jumlah booking per hari, N hari terakhir (termasuk hari 0).
     */
    protected function bookingTrend(int $days): array
    {
        $start = today()->subDays($days - 1);

        $rows = Booking::whereDate('booking_date', '>=', $start)
            ->selectRaw('DATE(booking_date) as date, count(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $trend = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $key = $date->format('Y-m-d');

            $trend[] = [
                'label' => $date->format('j') . ' ' . self::SHORT_MONTHS[(int) $date->format('n')],
                'total' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $trend;
    }

    /**
     * Breakdown status booking: pending, confirmed, completed, cancelled.
     */
    protected function statusBreakdown(): array
    {
        $counts = Booking::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0),
            'confirmed' => (int) ($counts['confirmed'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
            'cancelled' => (int) ($counts['cancelled'] ?? 0),
        ];
    }

    /**
     * Dokter dengan booking terbanyak.
     */
    protected function topDoctors(int $limit): array
    {
        return Doctor::with('user')
            ->withCount('bookings')
            ->orderByDesc('bookings_count')
            ->take($limit)
            ->get()
            ->map(function ($doctor) {
                return [
                    'name' => $doctor->user->name ?? 'Tanpa nama',
                    'specialist' => $doctor->specialist,
                    'photo' => $doctor->photo ?? $doctor->user->photo,
                    'total' => (int) $doctor->bookings_count,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Jumlah pasien per jenis kelamin.
     */
    protected function genderSplit(): array
    {
        $counts = Patient::selectRaw('gender, count(*) as total')
            ->groupBy('gender')
            ->pluck('total', 'gender');

        return [
            'L' => (int) ($counts['L'] ?? 0),
            'P' => (int) ($counts['P'] ?? 0),
        ];
    }

    /**
     * Jumlah rekam medis per bulan, N bulan terakhir.
     */
    protected function monthlyRecords(int $months): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $rows = MedicalRecord::where('created_at', '>=', $start)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, count(*) as total')
            ->groupBy('month')
            ->pluck('total', 'month');

        $result = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');

            $result[] = [
                'label' => self::SHORT_MONTHS[(int) $month->format('n')],
                'total' => (int) ($rows[$key] ?? 0),
            ];
        }

        return $result;
    }
}
