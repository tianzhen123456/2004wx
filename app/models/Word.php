<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Word extends Model
{
    public $timestamp=false;
    protected $table='word';
    protected $guarded = [];
}
