<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rekening extends Model
{
    use HasFactory;

    protected $table = 'rekenings';

    protected $fillable = [
        'koderekening',
        'namarekening',
        'id_sub_sub_kegiatan',
        'stAktif',
    ];

    protected $casts = [
        'stAktif' => 'boolean',
    ];

    public function subSubKegiatan()
    {
        return $this->belongsTo(SubSubKegiatan::class, 'id_sub_sub_kegiatan');
    }
}
