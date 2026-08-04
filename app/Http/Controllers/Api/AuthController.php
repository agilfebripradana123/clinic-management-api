<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'gender' => ['required', 'in:L,P'],
            'birth_date' => ['required', 'date'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'patient',
            ]);

            $lastPatientId = Patient::withTrashed()->max('id') ?? 0;
            $nextNumber = (int) $lastPatientId + 1;
            $medicalRecordNumber = 'RM' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

            Patient::create([
                'user_id' => $user->id,
                'medical_record_number' => $medicalRecordNumber,
                'gender' => $validated['gender'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'is_active' => true,
            ]);

            return $user;
        });

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        // Validasi input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        // Cari user berdasarkan email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // Cek password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Password salah'
            ], 401);
        }

        // Membuat token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // Kirim token dan data user
        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => $user,
        ], 200);
    }

        /**
     * Pasien lupa password → kirim permintaan reset ke admin.
     */
    public function requestPasswordReset(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email tidak ditemukan.',
            ], 404);
        }

        NotificationService::sendToAdmins(
            'password_reset_request',
            'Permintaan Reset Password',
            "{$user->name} ({$user->email}) meminta reset password.",
            ['user_id' => $user->id, 'email' => $user->email]
        );

        return response()->json([
            'message' => 'Permintaan reset password dikirim ke admin.',
        ], 200);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }
        public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }
}