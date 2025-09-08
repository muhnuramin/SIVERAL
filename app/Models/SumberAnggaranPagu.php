<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SumberAnggaranPagu extends Model
{
    protected $fillable = [
        'sumber_anggaran_id',
        'tahun',
        'pagu',
    ];

    protected $casts = [
        'pagu' => 'decimal:2',
        'tahun' => 'integer',
    ];

    public function sumberAnggaran(): BelongsTo
    {
        return $this->belongsTo(SumberAnggaran::class);
    }
}
