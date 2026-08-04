<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'doctor_id' => $this->doctor?->id,
            'patient_id' => $this->patient?->id,
            'photo' => $this->photo
                ? Storage::url($this->photo)
                : ($this->doctor?->photo
                    ? Storage::url($this->doctor->photo)
                    : null),
            'phone' =>
                $this->doctor?->phone ??
                $this->patient?->phone,
            'address' =>
                $this->doctor?->address ??
                $this->patient?->address,
            'specialist' => $this->doctor?->specialist,
            'license_number' => $this->doctor?->license_number,
            'medical_record_number' =>
                $this->patient?->medical_record_number,
            'gender' =>
                $this->patient?->gender,
            'birth_date' =>
                $this->patient?->birth_date,
            'created_at' =>
                optional($this->created_at)
                    ->format('d M Y'),
        ];
    }
}
