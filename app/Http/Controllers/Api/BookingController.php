<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Booking::with([
            'doctor.user',
            'patient.user',
            'schedule',
        ]);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('patient.user', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('doctor.user', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->integer('doctor_id'));
        }

        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->integer('patient_id'));
        }

        if ($request->has('booking_date')) {
            $query->whereDate('booking_date', $request->query('booking_date'));
        }

        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $sortColumns = [
            'created_at' => 'created_at',
            'booking_date' => 'booking_date',
        ];

        $column = $sortColumns[$sortBy] ?? $sortColumns['created_at'];

        $query->orderBy($column, $sortDir);

        return response()->json(
            $query->paginate($request->integer('per_page', 10))
        );
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_id'    => 'required|exists:doctors,id',
            'patient_id'   => 'required|exists:patients,id',
            'schedule_id'  => 'required|exists:schedules,id',
            'booking_date' => 'required|date',
            'status'       => 'required|in:pending,confirmed,completed,cancelled',
            'notes'        => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            $lastBooking = Booking::withTrashed()
                ->latest('id')
                ->first();

            $nextNumber = $lastBooking
                ? $lastBooking->id + 1
                : 1;

            $bookingCode = 'BK' .
                now()->format('Ymd') .
                str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $queueNumber = Booking::whereDate(
                'booking_date',
                $validated['booking_date']
            )->count() + 1;

            $booking = Booking::create([
                'doctor_id'     => $validated['doctor_id'],
                'patient_id'    => $validated['patient_id'],
                'schedule_id'   => $validated['schedule_id'],
                'booking_code'  => $bookingCode,
                'booking_date'  => $validated['booking_date'],
                'queue_number'  => $queueNumber,
                'status'        => $validated['status'],
                'notes'         => $validated['notes'] ?? null,
            ]);

            DB::commit();

        } catch (\Throwable $th) {

            DB::rollBack();

            return response()->json([
                'message' => 'Gagal membuat booking.',
                'error' => $th->getMessage(),
            ], 500);
        }

        // Notifikasi (di luar transaksi — kegagalan tak menggagalkan booking)
        $booking->refresh();

        // 1. Kirim ke admin
        try {
            $patientName = $booking->patient?->user?->name ?? 'Pasien';

            NotificationService::sendToAdmins(
                'booking_new',
                'Booking Baru',
                "Booking baru {$booking->booking_code} oleh {$patientName}.",
                ['booking_id' => $booking->id]
            );
        } catch (\Throwable $th) {
            report($th);
        }

        // 2. Kirim ke dokter
        try {
            $doctorUserId = $booking->doctor?->user_id;

            if ($doctorUserId) {
                $months = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                    4 => 'April', 5 => 'Mei', 6 => 'Juni',
                    7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                    10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                ];

                $bookingDate = $booking->booking_date;
                $monthName = $months[$bookingDate->format('n')] ?? $bookingDate->format('n');
                $dateLabel = $bookingDate->format('j') . ' ' . $monthName . ' ' . $bookingDate->format('Y');

                NotificationService::send(
                    $doctorUserId,
                    'booking_new',
                    'Jadwal Booking Baru',
                    "Anda mendapat booking baru {$booking->booking_code} pada {$dateLabel}.",
                    ['booking_id' => $booking->id]
                );
            }
        } catch (\Throwable $th) {
            report($th);
        }

        return response()->json([
            'message' => 'Booking berhasil dibuat.',
            'data' => $booking->load([
                'doctor.user',
                'patient.user',
                'schedule'
            ])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        return response()->json(
            $booking->load([
                'doctor.user',
                'patient.user',
                'schedule'
            ])
        );
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'doctor_id'    => 'sometimes|required|exists:doctors,id',
            'patient_id'   => 'sometimes|required|exists:patients,id',
            'schedule_id'  => 'sometimes|required|exists:schedules,id',
            'booking_date' => 'sometimes|required|date',
            'status'       => 'sometimes|required|in:pending,confirmed,completed,cancelled',
            'notes'        => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            $booking->update($validated);

            $statusChanged = $booking->wasChanged('status');

            DB::commit();

        } catch (\Throwable $th) {

            DB::rollBack();

            return response()->json([
                'message' => 'Gagal memperbarui booking.',
                'error' => $th->getMessage(),
            ], 500);
        }

        // Notifikasi: pasien saat status booking berubah
        if ($statusChanged) {
            try {
                $booking->refresh();

                $statusLabels = [
                    'pending' => 'Menunggu',
                    'confirmed' => 'Dikonfirmasi',
                    'completed' => 'Selesai',
                    'cancelled' => 'Dibatalkan',
                ];

                NotificationService::send(
                    $booking->patient->user_id,
                    'booking_status',
                    'Status Booking Diperbarui',
                    "Booking {$booking->booking_code} kini berstatus " .
                        ($statusLabels[$booking->status] ?? $booking->status) . ".",
                    ['booking_id' => $booking->id, 'status' => $booking->status]
                );
            } catch (\Throwable $th) {
                // abaikan — update tetap sukses
            }
        }

        return response()->json([
            'message' => 'Booking berhasil diperbarui.',
            'data' => $booking->fresh()->load([
                'doctor.user',
                'patient.user',
                'schedule'
            ])
        ]);
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Booking $booking)
    {
        try {

            $booking->delete();

            return response()->json([
                'message' => 'Booking berhasil dihapus.'
            ]);

        } catch (\Throwable $th) {

            return response()->json([
                'message' => 'Gagal menghapus booking.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}