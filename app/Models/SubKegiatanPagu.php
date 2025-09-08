<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubKegiatanPagu extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_kegiatan_id',
        'tahun',
    'pagu',
    'verified_by',
    'verified_at',
    ];

    protected $casts = [
    'verified_at' => 'datetime',
    'verified_by' => 'integer',
    ];

    public function subKegiatan()
    {
        return $this->belongsTo(SubKegiatan::class);
    }
}
