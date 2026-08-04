<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function __construct(
        protected ProfileService $profileService
    ) {
    }

    public function show(Request $request)
    {
        $profile = $this->profileService
            ->getProfile($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diambil.',
            'data' => new ProfileResource($profile),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $user->load(['doctor', 'patient']);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string'],
            'specialist' => ['sometimes', 'nullable', 'string', 'max:255'],
            'gender' => ['sometimes', 'nullable', 'in:L,P'],
            'birth_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $userData = [];
        if (isset($validated['name'])) {
            $userData['name'] = $validated['name'];
        }

        if (isset($validated['email'])) {
            $userData['email'] = $validated['email'];
        }

        if ($userData) {
            $user->update($userData);
        }

        if ($user->doctor) {
            $user->doctor()->update([
                'phone' => $validated['phone'] ?? $user->doctor->phone,
                'address' => $validated['address'] ?? $user->doctor->address,
                'specialist' => $validated['specialist'] ?? $user->doctor->specialist,
            ]);
        }

        if ($user->patient) {
            $user->patient()->update([
                'phone' => $validated['phone'] ?? $user->patient->phone,
                'address' => $validated['address'] ?? $user->patient->address,
                'gender' => $validated['gender'] ?? $user->patient->gender,
                'birth_date' => $validated['birth_date'] ?? $user->patient->birth_date,
            ]);
        }

        $profile = $this->profileService->getProfile($user->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diperbarui.',
            'data' => new ProfileResource($profile),
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'max:2048'],
        ]);

        $user = $request->user()->load(['doctor', 'patient']);
        $photo = $request->file('photo');
        $path = $photo->store('profile', 'public');

        $user->update([
            'photo' => $path,
        ]);

        if ($user->doctor) {
            $user->doctor()->update([
                'photo' => $path,
            ]);
        }

        $profile = $this->profileService->getProfile($user->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diunggah.',
            'data' => new ProfileResource($profile),
        ]);
    }
}
