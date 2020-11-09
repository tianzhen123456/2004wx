<?php

namespace App\Http\Controllers;

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
                if($data->Event=="subscribe") {
                    $accesstoken = $this->getAccessToken();
                    $openid = $data->FromUserName;
                    $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $accesstoken . "&openid=" . $openid . "&lang=zh_CN";
                    $user = json_decode($this->http_get($url), true);
                    if (isset($user['errcode'])) {
                        file_put_contents('wx_event.log', $user['errcode']);
                    } else {
                        if ($data->Event == "subscribe") {
                            $first = User::where("openid", $user['openid'])->first();
                            if ($first) {
                                $datas = [
                                    "subscribe" => 1,
                                    "openid" => $user["openid"],
                                    "nickname" => $user["nickname"],
                                    "sex" => $user["sex"],
                                    "city" => $user["city"],
                                    "country" => $user["country"],
                                    "province" => $user["province"],
                                    "language" => $user["language"],
                                    "headimgurl" => $user["headimgurl"],
                                    "subscribe_time" => $user["subscribe_time"],
                                    "subscribe_scene" => $user["subscribe_scene"],
                                ];
                                User::where("openid", $user['openid'])->update($datas);
                                $Content = "欢迎回来";
                            } else {
                                $post = new User();
                                $datas = [
                                    "subscribe" => $user["subscribe"],
                                    "openid" => $user["openid"],
                                    "nickname" => $user["nickname"],
                                    "sex" => $user["sex"],
                                    "city" => $user["city"],
                                    "country" => $user["country"],
                                    "province" => $user["province"],
                                    "language" => $user["language"],
                                    "headimgurl" => $user["headimgurl"],
                                    "subscribe_time" => $user["subscribe_time"],
                                    "subscribe_scene" => $user["subscribe_scene"],
                                ];
                                $name = $post->insert($datas);
                                $Content = "谢谢关注";
                            }
                        } else {
                            User::where("openid", $user['openid'])->update(["subscribe" => 0]);
                            $Content = "取关成功";
                        }
                    }
                }
            }
        }else{
            echo "";
        }
    }

    public function getAccessToken(){
        //从redis中取出token
        $key="access_token";
        $token=Redis::get($key);
        //dd($token);
         if($token){
             echo "有缓存";
         }else{
             echo "无缓存";
             $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSECRET');
             $response=file_get_contents($url);

             $data=json_decode($response,true);
             $token=$data['access_token'];
//             dd($token);
             //将token存在redis中   命名为$key 3600秒后过期
             Redis::set($key,$token);
             Redis::expire($key,3600);
         }
       echo "access_token:".$token;
    }

    public function test2(){
      // echo '<pre>';print_r($_GET);echo'</pre>';
       // echo '<pre>';print_r($_POST);echo'</pre>';
        //file_get_contents("php://input");

    }

    public function responseMsg($array,$Content){
        $ToUserName = $array->FromUserName;
        $FromUserName = $array->ToUserName;
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

       function http_get($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);//向那个url地址上面发送
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);//设置发送http请求时需不需要证书
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置发送成功后要不要输出1 不输出，0输出
        $output = curl_exec($ch);//执行
        curl_close($ch);    //关闭
        return $output;
    }



}
