<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Enrollment extends Model
{
    use HasFactory, Notifiable, HasApiTokens;
    
    protected $primaryKey = 'enrol_id';
    protected $fillable = [
        'LRN', 
        'regapproval_date', 
        'payment_approval', 
        'grade_level', 
        'guardian_name', 
        'last_attended', 
        'public_private', 
        'date_register', 
        'strand', 
        'school_year'
    ];
}
