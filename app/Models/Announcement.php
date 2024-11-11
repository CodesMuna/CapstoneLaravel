<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Announcement extends Model
{
    use HasFactory;

    protected $primaryKey = 'ancmnt_id';
    protected $fillable = [
        'ancmnt_id',
        'admin_id',
        'class_id',
        'title',
        'announcemnt',
        'date_announced',
    ];
    
    
}
