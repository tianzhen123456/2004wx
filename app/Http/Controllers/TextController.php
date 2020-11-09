<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use  Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
class TextController extends Controller
{
    //public function test1(){
//         echo __METHOD__;
//
//         $list=DB::table('user')->limit(5)->get();
//         //dd($list);
//         echo '<pre>';print_r($list);echo'</pre>';
    //     $key='wx2004';
    //     Redis::set($key,time());
    //     echo Redis::get($key);
    // }

//     public function token(){
//      $echostr=request()->get('echostr','');
//      if($this->checkSignature() && !empty($echostr)){
//         echo $echostr;
//      }
//   }
//
//private function checkSignature()
//{
//    $signature = $_GET["signature"];
//    $timestamp = $_GET["timestamp"];
//    $nonce = $_GET["nonce"];
//
//    $token = "Token";
//    $tmpArr = array($token, $timestamp, $nonce);
//    sort($tmpArr, SORT_STRING);
//    $tmpStr = implode( $tmpArr );
//    $tmpStr = sha1( $tmpStr );
//
//    if( $tmpStr == $signature ){
//        return true;
//    }else{
//        return false;
//    }
//
//  }

   public function guzzle1(){
       $client = new Client();
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET');
        //使用guzzle发送get请求
       //echo $url;

       $response = $client->request('GET',$url,['verify'=>flase]);
       $json_str = $response->getBody();
       echo $json_str;
    }
}
