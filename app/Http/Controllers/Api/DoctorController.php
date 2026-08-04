<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $query = Doctor::with('user');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('specialist', 'like', "%{$search}%")
                    ->orWhere('license_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $sortColumns = [
            'created_at' => 'created_at',
            'name' => User::select('name')
                ->whereColumn('users.id', 'doctors.user_id'),
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
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|min:8',
            'specialty'  => 'required|string|max:255',
            'phone'      => 'required|string|max:20',
            'address'    => 'required|string',
            'photo'      => 'nullable|string',
            'is_active'  => 'sometimes|boolean',
        ]);

        $doctor = DB::transaction(function () use ($validated) {

            // Buat akun user dokter
            $user = User::create([
                'role'     => 'doctor',
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => $validated['password'], // otomatis di-hash oleh cast di User
            ]);

            // Buat data dokter
            return Doctor::create([
                'user_id'         => $user->id,
                'license_number'  => 'DOC-' . time(),
                'specialist'      => $validated['specialty'],
                'phone'           => $validated['phone'],
                'address'         => $validated['address'],
                'photo'           => $validated['photo'] ?? null,
                'is_active'       => $validated['is_active'] ?? true,
            ]);
        });

        return response()->json([
            'message' => 'Doctor created successfully',
            'data'    => $doctor->load('user'),
        ], 201);
    }

    public function show(Doctor $doctor)
    {
        return response()->json($doctor->load('user'));
    } 

    public function update(Request $request, Doctor $doctor)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'sometimes|email|unique:users,email,' . $doctor->user_id,
            'password'  => 'nullable|min:8',
            'specialty' => 'required|string|max:255',
            'phone'     => 'required|string|max:20',
            'address'   => 'required|string',
            'photo'     => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($doctor, $validated) {

            // ==========================
            // Update data user
            // ==========================
            $userData = [
                'name' => $validated['name'],
            ];

            // Email hanya diubah jika dikirim
            if (!empty($validated['email'])) {
                $userData['email'] = $validated['email'];
            }

            // Password hanya diubah jika diisi
            if (!empty($validated['password'])) {
                $userData['password'] = $validated['password'];
            }

            $doctor->user()->update($userData);

            // ==========================
            // Update data doctor
            // ==========================
            $doctor->update([
                'specialist' => $validated['specialty'],
                'phone'      => $validated['phone'],
                'address'    => $validated['address'],
                'photo'      => $validated['photo'] ?? $doctor->photo,
                'is_active'  => $validated['is_active'] ?? $doctor->is_active,
            ]);
        });

        return response()->json([
            'message' => 'Doctor updated successfully',
            'data'    => $doctor->fresh()->load('user'),
        ]);
    }

    public function destroy(Doctor $doctor)
    {
        DB::transaction(function () use ($doctor) {
            $doctor->user()->delete(); // hapus akun user
            $doctor->delete();         // hapus data dokter
        });

        return response()->json([
            'message' => 'Doctor deleted successfully',
        ]);
    }
}