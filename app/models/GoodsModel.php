<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class GoodsModel extends Model
{
    public $timestamp=false;
    protected $table='ecs_goods';
    protected $guarded = [];
}
