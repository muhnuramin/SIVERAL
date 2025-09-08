<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubSubKegiatan extends Model
{
    use HasFactory;

    protected $table = 'sub_sub_kegiatans';

    protected $fillable = ['sub_kegiatan_id', 'kode', 'nama'];

    public function subKegiatan()
    {
        return $this->belongsTo(SubKegiatan::class, 'sub_kegiatan_id');
    }

    // Akun relation removed; using Rekening as the chart of accounts for heads.

    public function pagus()
    {
        return $this->hasMany(SubSubKegiatanPagu::class, 'sub_sub_kegiatan_id');
    }

    public function rekenings()
    {
        return $this->hasMany(Rekening::class, 'id_sub_sub_kegiatan');
    }

    public function pics()
    {
        return $this->belongsToMany(User::class, 'sub_sub_kegiatan_user', 'sub_sub_kegiatan_id', 'user_id')->withTimestamps();
    }
}
