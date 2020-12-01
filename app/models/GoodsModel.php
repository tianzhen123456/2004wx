<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class GoodsModel extends Model
{
    public $timestamp=false;
    protected $table='ecs_goods';
    protected $primaryKey = 'goods_id';
    public $timestamps = false;
    protected $guarded = [];
}
