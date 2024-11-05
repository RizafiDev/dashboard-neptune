<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    use HasFactory;
    protected $table = 'announcements'; 
    protected $fillable = [
        'admin_name',
        'subject',
        'message',
        'is_fixed',
    ];
}
