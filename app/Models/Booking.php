<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'schedule_id',
        'booking_code',
        'booking_date',
        'queue_number',
        'status',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }
}