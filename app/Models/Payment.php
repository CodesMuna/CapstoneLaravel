<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $primaryKey = 'payment_id';
    protected $fillable = [
        'LRN',
        'OR_number',
        'amount_paid',
        'proof_payment',
        'description'
    ];
}
