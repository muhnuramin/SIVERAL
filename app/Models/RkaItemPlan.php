<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RkaItemPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_sub_kegiatan_id','rekening_ssh_item_id','tahun',
        'jan_vol','feb_vol','mar_vol','apr_vol','mei_vol','jun_vol','jul_vol','agu_vol','sep_vol','okt_vol','nov_vol','des_vol'
    ];

    protected $casts = [
        'tahun' => 'integer',
        'jan_vol'=>'integer','feb_vol'=>'integer','mar_vol'=>'integer','apr_vol'=>'integer','mei_vol'=>'integer','jun_vol'=>'integer','jul_vol'=>'integer','agu_vol'=>'integer','sep_vol'=>'integer','okt_vol'=>'integer','nov_vol'=>'integer','des_vol'=>'integer'
    ];

    public function subSubKegiatan(){ return $this->belongsTo(SubSubKegiatan::class); }
    public function item(){ return $this->belongsTo(RekeningSshItem::class,'rekening_ssh_item_id'); }
}
