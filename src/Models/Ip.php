<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ip extends Model
{
    protected $connection = "hos";
    protected $table = "ipt";

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'an', 'an');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'hn', 'hn');
    }
    
    public function ward()
    {
        return $this->belongsTo(Ward::class, 'ward', 'ward');
    }
    
    public function pttype()
    {
        return $this->belongsTo(Pttype::class, 'pttype', 'pttype');
    }

    public function admdoctor()
    {
        return $this->belongsTo(Doctor::class, 'admdoctor', 'code');
    }
}