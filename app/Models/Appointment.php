<?php

/// Author: Yuen Yun Jia & Foo Tek Sian

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'service_id',
        'appointment_date',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'appointment_date' => 'datetime',
    ];

    /**
     * Get the patient for this appointment
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Get the doctor for this appointment
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    /**
     * Get the service for this appointment
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the payment for this appointment
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Check if appointment can be cancelled (at least 1 day before)
     */
    public function canBeCancelled()
    {
        return $this->appointment_date->isFuture() &&
               $this->appointment_date->diffInDays(now()) >= 1;
    }
}
