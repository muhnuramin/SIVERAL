<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekeningSshItem extends Model
{
    use HasFactory;

    protected $table = 'rekening_ssh_items';

    protected $fillable = [
        'rekening_kode',
        'nama',
        'satuan',
        'spesifikasi',
    ];

    public function hargas()
    {
        return $this->hasMany(RekeningSshItemHarga::class, 'rekening_ssh_item_id');
    }

    public function hargaTahun(int $tahun)
    {
        return $this->hargas()->where('tahun', $tahun)->first();
    }
}
