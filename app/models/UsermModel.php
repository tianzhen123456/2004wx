<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class UsermModel extends Model
{
    public $timestamp = false;
    protected $table = 'userm';
    protected $guarded = [];
}
