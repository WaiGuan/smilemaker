<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
        'price',
    ];

    /**
     * Get all appointments for this service
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Check if this service is free (walk-in)
     */
    public function isFree()
    {
        return is_null($this->price) || $this->price == 0;
    }
}
