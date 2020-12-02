<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Log;
use App\models\User;
use App\models\UserInfo;

class IndexController extends Controller
{
          //微信接入
            public function checkSignature()
            {
                $signature = $_GET["signature"];
                $timestamp = $_GET["timestamp"];
                $nonce = $_GET["nonce"];

                $token = env('WX_TOKEN');
                $tmpArr = array($token, $timestamp, $nonce);
                sort($tmpArr, SORT_STRING);
                $tmpStr = implode( $tmpArr );
                $tmpStr = sha1( $tmpStr );

                if( $tmpStr == $signature ){
                    echo $_GET['echostr'];
                }else{
                    echo '123';
                }
            }
              //执行微信方法
                public function index()
                {
            //        $result = $this->checkSignature();
            //        if($result){
            //            echo $_GET['echostr'];
            //            exit;
            //        }
                    //getToken();
                    $this->wxEvent();
                    $this->createMenu();
                    $this->callBack();
                }

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
                                 file_put_contents('wx_event.log',$xml_str,FILE_APPEND);
                                    //   Log::info($xml_str);

                        //    把xml文本转换为php的对象或数组
                                    $data=simplexml_load_string($xml_str,'SimpleXMLElement',LIBXML_NOCDATA);
                                   // dd($data);

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

                        /***
                         * 自动回复
                           判断消息类型   自动回复  并将素材下载到服务器上
                         */

                     public function callBack()
                     {
                         //   echo '123';die;
                         $xml_str = file_get_contents("php://input");
                         // Log::info("====天气====".$xml_str);

                         $data = simplexml_load_string($xml_str, 'SimpleXMLElement', LIBXML_NOCDATA);
                         if ($data->MsgType == "text") {
//                             if ($data->Content == "天气") {
//                                 $Content = $this->getweather();
//                                 $this->responseMsg($data, $Content);
//                             }
                             $Content= $data->Content;
//                             $this->getword($Content);
                             $apikey ='873c5c2a1fd9db69286296dea1a59c63';
                             $url="http://api.tianapi.com/txapi/pinyin/index?key=".$apikey."&text=".$Content;
                             $word=file_get_contents($url);
                             $word=json_decode($word,true);
                             if($word['code'] == 200) { //判断状态码
//                        $Content='';
//                            $Content .='pinyin:'.$Content;

                                 echo $this->responseMsg($data, $Content);
                             }
                             //判断是否是图片信息
                         } else if ($data->MsgType == "image") {
                             $datas = [
                                 "tousername" => $data->ToUserName,
                                 "fromusername" => $data->FromUserName,
                                 "createtime" => $data->CreateTime,
                                 "msgtype" => $data->MsgType,
                                 "picurl" => $data->PicUrl,
                                 "msgid" => $data->MsgId,
                                 "mediaid" => $data->MediaId,
                             ];
                             $image = new UserInfo();
                             $images = UserInfo::where('picurl', $datas['picurl'])->first();
                             if (!$images) {
                                 $images = $image->insert($datas);
                             }
                             //存入线上public中
                             $access_token = $this->getAccessToken();
//                             file_put_contents('wx_event.log',$access_token,FILE_APPEND);
                             $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=" . $access_token . "&media_id=" . $data->MediaId;
                             $get = file_get_contents($url);
                             file_put_contents("image.jpg", $get);
                             $Content = "是图片哦~";
                             $this->responseMsg($data, $Content);
                         } else if ($data->MsgType == "voice") {
                             $access_token = $this->getAccessToken();
                             Log::info("====语音====" . $access_token);
                             $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=" . $access_token . "&media_id=" . $data->MediaId;
                             $get = file_get_contents($url);
                             file_put_contents("voice.amr", $get);
                             $Content = "是语音哦~";
                             $this->responseMsg($data, $Content);
                         } else if ($data->MsgType == "video") {
                             $access_token = $this->getAccessToken();
                             Log::info("====视频====" . $access_token);
                             $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=" . $access_token . "&media_id=" . $data->MediaId;
                             $get = file_get_contents($url);
                             file_put_contents("video.mp4", $get);
                             $Content = "是视频呀-";
                             $this->responseMsg($data, $Content);
                         } else if ($data->Event == "CLICK") {
                             if ($data->EventKey == "V1001_TODAY_QQ") {
                                 $key = "qiandao";
                                 $openid = (string)$data->FromUserName;
                                 //sismember 命令判断成员元素是否是集合的成员。
                                 $slsMember = Redis::sismember($key, $openid);
                                 //是成员元素  返回 1  已签到
                                 if ($slsMember == "1") {
                                     $Content = "已签到过啦！";
                                     $this->responseMsg($data, $Content);
                                 } else {
                                     $Content = "签到成功";
                                     Redis::sAdd($key, $openid);
                                     $this->responseMsg($data, $Content);
                                 }
//                Log::info("=====slemenber=======".$slsMember);
                             }
                          }
                     }

                public function getweather()
                {
                    $ip='123.125.71.38';
                    $url = 'http://api.k780.com:88/?app=weather.future&weaid='.$ip.'&&appkey=10003&sign=b59bc3ef6191eb9f747dd4e83c99f2a4&format=json';
                    $weather = file_get_contents($url);
                    $weather = json_decode($weather,true);
                    if ($weather['success']) {
                        $Content = '';
                        foreach ($weather['result'] as $v) {
                            $Content .= '地区:' .$v['citynm'].'日期:' . $v['days'] . $v['week'] . ' 当日温度 : ' . $v['temperature'] . '天气:' . $v['weather'] . '风向:' . $v['wind'];
                        }
                    }
                    return  $Content;
                }


                public function getword($Content){
                         $apikey ='873c5c2a1fd9db69286296dea1a59c63';
//                         $text=$data->Content;
                         $url="http://api.tianapi.com/txapi/pinyin/index?key=".$apikey."&text=".$Content;
                         $word=file_get_contents($url);
                         $word=json_decode($word,true);
                    if($word['code'] == 200){ //判断状态码
//                        $Content='';
//                            $Content .='pinyin:'.$Content;
                        return $Content;
                    }else{
                        echo "返回错误，状态消息：".$word['msg'];
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

    //创建自定义菜单
    public function createMenu(){
        $menu = [
            "button"=>[
                [
                    "type"=>"click",
                    "name"=>"签到",
                    "key"=>"V1001_TODAY_QQ"
                ],
                [
                    "name"=>"商城",
                    "sub_button"=>[
                        [
                            "type"=>"view",
                            "name"=>"查询历史",
                            "url"=>"http://www.jd.com"
                        ],
                        [
                            "type"=>"view",
                            "name"=>"商城",
                            "url"=>"http://2004tz.liyazhou.top"
                        ]
                    ]
                ],
                [
                    "name"=>"菜单",
                    "sub_button"=>[
                        [
                            "type"=>"view",
                            "name"=>"搜索",
                            "url"=>"http://www.baidu.com/"
                        ],
                        [
                            "type"=>"click",
                            "name"=>"赞一下我们",
                            "key"=>"V1001_GOOD"
                        ]
                    ]
                ]]
        ];
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
        $client = new Client();  //实例化客户端
        $response = $client->request('POST',$url,[   //发起请求并且接受响应
            'verify'=>false,
            'body'=>json_encode($menu,JSON_UNESCAPED_UNICODE)
        ]);
        $res = $response->getBody();   //响应服务器的数据
        echo $res;
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
