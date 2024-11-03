<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artist extends Model
{
    use HasFactory;
    protected $table = 'artists'; 
    protected $fillable = [
        'artist_name',
        'legal_name',
        'artist_avatar',
        'artist_idcard',
        'total_royalties',
        'total_releases',
        'email',
    ];
    public function releases(): HasMany
    {
        return $this->hasMany(Release::class, 'artist_name', 'artist_name');
    }

    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class, 'artist_name', 'artist_name');
    }
}
