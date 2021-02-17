<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ip extends Model
{
    protected $connection = "hos";
    protected $table = "ipt";

    public function booking()
    {
        return $this->hasMany(Booking::class, 'an', 'an');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'hn', 'hn');
    }
    
    public function ward()
    {
        return $this->hasMany(Ward::class, 'ward', 'ward');
    }
}