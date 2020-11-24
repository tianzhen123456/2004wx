<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use  App\models\UserxModel;

class XcxController extends Controller
{
    /**
     * 小程序登录
     */

    public function xcxLogin(Request $request){
      //  echo '<pre>';print_r($_GET);echo'</pre>';

        //接受code
        $code = $request->get('code');

        //获取用户信息
        $userinfo=json_decode(file_get_contents("php://input"),true);

        $url ='https://api.weixin.qq.com/sns/jscode2session?appid='.env('WX_XCX_APPID').'&secret='.env('WX_XCX_SECRET').'&js_code='.$code.'&grant_type=authorization_code';
        //获取 session_key  会话密钥    openid  用户唯一标识
            $data=json_decode(file_get_contents($url),true);
//            echo '<pre>';print_r($data); echo '</pre>';

            //自定义登录状态
        //有错误码  登录失败
        if(isset($data['errcode'])){
                $response=[
                    'errno' => 50001,
                    'msg'   => '登录失败',
                ];
        }else{

            $openid=$data['openid'];
            $u = UserxModel::where(['openid'=>$openid])->first();

            if($u){
                //echo "老用户,已入库"
            }else{
               // dd($userinfo);
                $u_info=[
                    'openid' => $openid,
                    'nickname' => $userinfo['u']['nickName'],
                    'sex' =>  $userinfo['u']['gender'],
                    'language' => $userinfo['u']['language'],
                    'city'=> $userinfo['u']['city'],
                    'province' =>  $userinfo['u']['province'],
                    'country'  => $userinfo['u']['country'],
                    'headimgurl'=>$userinfo['u']['avatarUrl'],
                    'add_time'=>time(),
                     'type'=> 3
                ];

                UserxModel::insertGetId($u_info);
            }

//            if(empty(DB::table("userx")->where("openid",$data["openid"])->first())){
//                DB::table("userx")->insert(["openid"=>$data["openid"]]);
//            }

            //生成token   mt_rand() 生成随机整数   介于0到999999之间
             $token=sha1($data['openid'].$data['session_key'].mt_rand(0,999999));
           //保存token
            $redis_key='xcx_token:'.$token;
            Redis::set($redis_key,time());
            //设置过期时间
            Redis::expire($redis_key,7200);

             $response=[
                 'errno' => 0,
                 'msg'   => 'ok',
                 'data'  => [
                     'token'  =>$token
                 ]
             ];
        }

        return $response;
    }
}

