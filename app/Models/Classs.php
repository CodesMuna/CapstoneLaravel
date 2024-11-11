<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classs extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'admin_id',
        'subject_id',
        'section',
        'room',
        'schedule'
    ];
}
