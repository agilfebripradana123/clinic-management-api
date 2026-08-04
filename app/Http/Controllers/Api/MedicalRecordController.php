<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\MedicalRecord;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicalRecordController extends Controller
{
    public function availableBookings(Request $request)
    {
        $bookings = Booking::with([
            'patient.user',
            'doctor.user',
            'schedule',
        ])
        ->whereDoesntHave('medicalRecord')
        ->whereIn('status', ['confirmed', 'completed']);

        // Filter hanya booking milik dokter tertentu (untuk role doctor)
        if ($request->has('doctor_id')) {
            $bookings->where('doctor_id', $request->integer('doctor_id'));
        }

        $bookings = $bookings->latest()->get();

        return response()->json($bookings);
    }
    /**
     * Ekspor semua rekam medis ke CSV (kompatibel Excel, dengan BOM UTF-8).
     */
    public function export()
    {
        $records = MedicalRecord::with([
            'booking.patient.user',
            'booking.doctor.user',
        ])
        ->orderByDesc('id')
        ->get();

        $handle = fopen('php://temp', 'r+');

        // BOM UTF-8 agar karakter khusus terbaca di Excel
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'No',
            'Kode Booking',
            'Pasien',
            'Dokter',
            'Keluhan',
            'Diagnosa',
            'Pengobatan',
            'Resep',
            'Catatan',
            'Tanggal Dibuat',
        ]);

        $records->each(function ($record, $index) use ($handle) {
            $booking = $record->booking;

            fputcsv($handle, [
                $index + 1,
                $booking->booking_code ?? '-',
                $booking->patient?->user?->name ?? '-',
                $booking->doctor?->user?->name ?? '-',
                $record->complaint ?? '-',
                $record->diagnosis ?? '-',
                $record->treatment ?? '-',
                $record->prescription ?? '-',
                $record->notes ?? '-',
                $record->created_at?->format('Y-m-d') ?? '-',
            ]);
        });

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $filename = 'rekam-medis-' . now()->format('Y-m-d') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function index(Request $request)
    {
        $query = MedicalRecord::with([
            'booking.patient.user',
            'booking.doctor.user',
            'booking.schedule',
        ]);

        if ($request->has('doctor_id')) {
            $query->whereHas('booking', function ($bq) use ($request) {
                $bq->where('doctor_id', $request->integer('doctor_id'));
            });
        }

        if ($request->has('patient_id')) {
            $query->whereHas('booking', function ($bq) use ($request) {
                $bq->where('patient_id', $request->integer('patient_id'));
            });
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('diagnosis', 'like', "%{$search}%")
                    ->orWhere('complaint', 'like', "%{$search}%")
                    ->orWhereHas('booking', function ($bq) use ($search) {
                        $bq->where('booking_code', 'like', "%{$search}%")
                            ->orWhereHas('patient.user', function ($pq) use ($search) {
                                $pq->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $sortColumns = [
            'created_at' => 'created_at',
            'booking_date' => Booking::select('booking_date')
                ->whereColumn('bookings.id', 'medical_records.booking_id'),
        ];

        $column = $sortColumns[$sortBy] ?? $sortColumns['created_at'];

        $query->orderBy($column, $sortDir);

        return response()->json(
            $query->paginate($request->integer('per_page', 10))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id'   => 'required|exists:bookings,id|unique:medical_records,booking_id',
            'complaint'    => 'required|string',
            'diagnosis'    => 'required|string',
            'treatment'    => 'required|string',
            'prescription' => 'nullable|string',
            'notes'        => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            $booking = Booking::findOrFail($validated['booking_id']);

            $record = MedicalRecord::create($validated);

            $booking->update([
                'status' => 'completed',
            ]);

            DB::commit();

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
            ],500);
        }

        // Notifikasi ke pasien: rekam medis sudah dibuat & booking selesai
        try {
            $booking->refresh();

            NotificationService::send(
                $booking->patient->user_id,
                'medical_record_created',
                'Rekam Medis Tersedia',
                "Rekam medis untuk booking {$booking->booking_code} telah dibuat. Anda dapat melihatnya di menu Riwayat.",
                ['booking_id' => $booking->id, 'medical_record_id' => $record->id]
            );

            NotificationService::send(
                $booking->patient->user_id,
                'booking_status',
                'Booking Selesai',
                "Booking {$booking->booking_code} kini berstatus Selesai.",
                ['booking_id' => $booking->id, 'status' => 'completed']
            );
        } catch (\Throwable $e) {
            // abaikan — rekam medis tetap sukses
        }

        return response()->json(
            $record->load([
                'booking.patient.user',
                'booking.doctor.user',
                'booking.schedule',
            ]),
            201
        );
    }

    public function show(MedicalRecord $medicalRecord)
    {
        return response()->json(
            $medicalRecord->load([
                'booking.patient.user',
                'booking.doctor.user',
                'booking.schedule',
            ])
        );
    }

    public function update(Request $request, MedicalRecord $medicalRecord)
    {
        $validated = $request->validate([
            'complaint'    => 'required|string',
            'diagnosis'    => 'required|string',
            'treatment'    => 'required|string',
            'prescription' => 'nullable|string',
            'notes'        => 'nullable|string',
        ]);

        $medicalRecord->update($validated);

        return response()->json(
            $medicalRecord->load([
                'booking.patient.user',
                'booking.doctor.user',
                'booking.schedule',
            ])
        );
    }

    public function destroy(MedicalRecord $medicalRecord)
    {
        $medicalRecord->delete();

        return response()->json([
            'message' => 'Medical record deleted successfully.'
        ]);
    }
}