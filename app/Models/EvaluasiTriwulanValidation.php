<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluasiTriwulanValidation extends Model
{
    protected $fillable = [
        'tahun', 'triwulan', 'validated_by', 'validated_at'
    ];
}