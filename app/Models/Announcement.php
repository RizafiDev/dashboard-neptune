<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;
    protected $table = 'announcements'; 
    protected $fillable = [
        'artist_name',
        'legal_name',
        'artist_avatar',
        'artist_idcard',
        'total_royalties',
        'total_releases',
        'email',
    ];
}
