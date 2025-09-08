<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RekeningSshItemHarga extends Model
{
    use HasFactory;

    protected $table = 'rekening_ssh_item_hargas';

    protected $fillable = [
        'rekening_ssh_item_id','tahun','harga'
    ];

    public function item()
    {
        return $this->belongsTo(RekeningSshItem::class, 'rekening_ssh_item_id');
    }
}
