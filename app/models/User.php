<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public $timestamp=false;
    protected $table='user';
    protected $guarded = [];
}
