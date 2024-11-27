<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelWhitelist extends Model
{
    use HasFactory;
    protected $table = 'channel_whitelists'; 
    protected $fillable = [
        'name',
        'artist_name',
        'channel',
        'status',
    ];
}
