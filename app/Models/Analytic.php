<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Analytic extends Model
{
    use HasFactory;
// Di model Analytic
public function release()
{
    return $this->belongsTo(Release::class);
}

}

