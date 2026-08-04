<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'complaint',
        'diagnosis',
        'treatment',
        'prescription',
        'notes',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}