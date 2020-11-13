<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class UserInfo extends Model
{
    public $timestamp=false;
    protected $table='p_wx_media';
    protected $guarded = [];
}
