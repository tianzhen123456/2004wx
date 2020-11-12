<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Log;
use App\models\User;
class IndexController extends Controller
{
    /**
     * 处理事件推送
     */
    public function wxEvent()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        //验证通过
        if( $tmpStr == $signature ){

            $client = new Client();
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET');
            //使用guzzle发送get请求
            //echo $url;

//            $response = $client->request('GET',$url,['verify'=>false]);
//            $json_str = $response->getBody();
//            echo $json_str;
            // 接收数据
            $xml_str=file_get_contents("php://input");
//         //记录日志
       //  file_put_contents('wx_event.log',$xml_str);
////            Log::info($xml_str);
//            echo "";
//            die;
        //    把xml文本转换为php的对象或数组
            $data=simplexml_load_string($xml_str,'SimpleXMLElement',LIBXML_NOCDATA);
           // dd($data);
//            file_put_contents('wx_event.log',$data);die;

            if($data->MsgType=="event"){
                if($data->Event=="subscribe"){
                    $accesstoken = $this->getAccessToken();
                    $openid = $data->FromUserName;
                    $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$accesstoken."&openid=".$openid."&lang=zh_CN";
                    $user = file_get_contents($url);
                    $res = json_decode($user,true);
                    if(isset($res['errcode'])){
                        file_put_contents('wx_event.log',$res['errcode']);
                    }else{
                        $user_id = User::where('openid',$openid)->first();
                        if($user_id){
                            $user_id->subscribe=1;
                            $user_id->save();
                            $contentt = "感谢再次关注";
                        }else{
                            $res = [
                                'subscribe'=>$res['subscribe'],
                                'openid'=>$res['openid'],
                                'nickname'=>$res['nickname'],
                                'sex'=>$res['sex'],
                                'city'=>$res['city'],
                                'country'=>$res['country'],
                                'province'=>$res['province'],
                                'language'=>$res['language'],
                                'headimgurl'=>$res['headimgurl'],
                                'subscribe_time'=>$res['subscribe_time'],
                                'subscribe_scene'=>$res['subscribe_scene']

                            ];
                            User::insert($res);
                            $contentt = "欢迎平民关注";

                        }

                    }
                    echo $this->responseMsg($data,$contentt);

                }
                //取消关注
                if($data->Event=='unsubscribe'){
                    $user_id->subscribe=0;
                    $user_id->save();
                }
            }



        }else{

            echo "";
        }
    }

    public function getAccessToken()
    {

        $key = 'wx:access_token';

        //检查是否有token
        $token = Redis::get($key);
        if ($token) {
            echo "有缓存";
            echo '</br>';
            echo $token;
        } else {
            // echo "无缓存";
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . env('WX_APPID') . "&secret=" . env('WX_APPSECRET');
            //echo $url;die;
            // $response = file_get_contents($url);
            //echo $response;

            //使用guzzle发起get请求
            $client = new Client(); //实例化 客户端
            $response = $client->request('GET',$url,['verify'=>false]); //发起请求并接收响应
            $json_str = $response->getBody();  //服务器的响应数据
            //echo $json_str;die;

            $data = json_decode($json_str, true);
            $token = $data['access_token'];

            //保存到redis中时间为3600

            Redis::set($key, $token);
            Redis::expire($key, 1000);
        }

        return $token;

    }

    public function createMenu()
    {
        $menu = ' {
             "button":[
             {
                  "type":"click",
                  "name":"商城",
                  "key":"V1001_TODAY_MUSIC"
              },
              {
                   "name":"菜单",
                   "sub_button":[
                   {
                       "type":"view",
                       "name":"天气",
                       "url":"http://www.soso.com/"
                    },
                    {
                       "type":"click",
                       "name":"图片",
                       "key":"V1001_GOOD"
                    }]
               }]
         }';
        $access_token =$this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' . $access_token;
        $res = $this->curl($url, $menu);
        echo $res;
       }


    //下载临时素材
    public function linShi(){

        $xml_str=file_get_contents("php://input");
        $data=simplexml_load_string($xml_str,'SimpleXMLElement',LIBXML_NOCDATA);
        $media_id=$data->MediaId;
        dd($media_id);die;
        $access_token = $this->token();
        $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=$access_token&media_id=$media_id";


    }

    public function responseMsg($data,$Content){
        $ToUserName = $data->FromUserName;
        $FromUserName = $data->ToUserName;
        $CreateTime = time();
        $MsgType = "text";

        $text = "<xml>
                  <ToUserName><![CDATA[%s]]></ToUserName>
                  <FromUserName><![CDATA[%s]]></FromUserName>
                  <CreateTime>%s</CreateTime>
                  <MsgType><![CDATA[%s]]></MsgType>
                  <Content><![CDATA[%s]]></Content>
                </xml>";
        echo sprintf($text,$ToUserName,$FromUserName,$CreateTime,$MsgType,$Content);
    }

    public function curl($url,$menu){
        //1.初始化
        $ch = curl_init();
        //2.设置
        curl_setopt($ch,CURLOPT_URL,$url);//设置提交地址
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);//设置返回值返回字符串
        curl_setopt($ch,CURLOPT_POST,1);//post提交方式
        curl_setopt($ch,CURLOPT_POSTFIELDS,$menu);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        //3.执行
        $output = curl_exec($ch);
        //关闭
        curl_close($ch);
        return $output;
    }
    public  function guzzle2(){
        $access_token =$this->getAccessToken();
        $type='image';
        $url='https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$access_token.'&type='.$type;
        $client=new Client();
        $response  =  $client->request ('POST' , $url, [

            //上传的文件路径
            //  'media' =>fopen('a.jpg','r'),
            'verify'      => false,
            'multipart'  =>[
                [
                    'name'      => 'media' ,
                    'contents'  =>  fopen('a.jpg','r')
                ]

            ]
        ]);
        $data=$response->getBody();
        echo $data;
    }

}
