<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use  Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
class TextController extends Controller
{
     public function test1(){
//         echo __METHOD__;
//
//         $list=DB::table('user')->limit(5)->get();
//         //dd($list);
//         echo '<pre>';print_r($list);echo'</pre>';
         $key='wx2004';
         Redis::set($key,time());
         echo Redis::get($key);
     }
}
