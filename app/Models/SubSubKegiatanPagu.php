<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubSubKegiatanPagu extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_sub_kegiatan_id',
        'tahun',
        'pagu',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'verified_by' => 'integer',
        'tahun' => 'integer',
        'pagu' => 'integer',
    ];

    public function subSubKegiatan()
    {
        return $this->belongsTo(SubSubKegiatan::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
