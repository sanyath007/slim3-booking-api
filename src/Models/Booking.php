<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $table = "bookings";
    protected $primaryKey = 'book_id';

    public function checkin()
    {
        return $this->hasOne(BookingCheckin::class, 'book_id', 'book_id');
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class, 'ward', 'ward');
    }

    public function ip()
    {
        return $this->hasOne(Ip::class, 'an', 'an');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'hn', 'hn');
    }

    public function newborns()
    {
        return $this->hasMany(BookingNewborn::class, 'book_id', 'book_id');
    }

    public function user()
    {
        return $this->belongsTo(Staff::class, 'user', 'person_id');
    }
}