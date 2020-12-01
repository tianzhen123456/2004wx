<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class CartModel extends Model
{
    public $timestamps=false;
    protected $table='ecs_cart';
    protected $primaryKey = 'rec_id';
}
