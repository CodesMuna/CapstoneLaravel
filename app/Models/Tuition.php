<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tuition extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_id',
        'year_level',
        'tuition',
        'general',
        'esc',
        'subsidy',
        'req_downpayment'
    ];
}
