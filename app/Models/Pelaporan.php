<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelaporan extends Model
{
    protected $table = 'pelaporan';
    protected $fillable = [
        'item_id',
        'bulan',
        'vol',
        'rupiah',
    ];
}
