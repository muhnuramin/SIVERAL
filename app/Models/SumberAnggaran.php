<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SumberAnggaran extends Model
{
    use HasFactory;

    protected $table = 'sumber_anggarans';

    protected $fillable = [
        'nama',
        'kode',
        'stAktif',
    ];

    protected $casts = [
        'stAktif' => 'boolean',
    ];
}
