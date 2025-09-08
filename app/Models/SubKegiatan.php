<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubKegiatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'kegiatan_id',
        'kode',
        'nama',
    ];

    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class);
    }

    public function subKegiatanPagus()
    {
        return $this->hasMany(SubKegiatanPagu::class);
    }

    public function sub_sub_kegiatans()
    {
        return $this->hasMany(SubSubKegiatan::class, 'sub_kegiatan_id');
    }

    // Backwards-compatible camelCase relation name used elsewhere in controllers
    public function subSubKegiatans()
    {
        return $this->hasMany(SubSubKegiatan::class, 'sub_kegiatan_id');
    }
}
