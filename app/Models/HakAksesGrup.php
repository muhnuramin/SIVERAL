<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HakAksesGrup extends Model
{
    use HasFactory;

    protected $table = 'hakaksesgrubs';

    protected $fillable = [
        'nama',
        'hak_akses',
    ];

    protected $casts = [
        'hak_akses' => 'array',
    ];
}
