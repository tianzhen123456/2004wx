<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\models\GoodsModel;
use App\models\CartModel;
use App\models\UserxModel;
use App\models\UsermModel;
class ApiController extends Controller
{
    public function test(){
//        echo '<pre>';print_r($_GET);echo'</pre>';
//        echo '<pre>';print_r($_POST);echo'</pre>';

//        $goods_info=[
//            'goods_id'     =>  12345,
//            'goods_name' => 'iphone',
//            'price'  =>  299
//        ];
        //return $goods_info;
        //echo json_encode($goods_info);
//        $GoodsModel=new GoodsModel();
      $data=GoodsModel::get();
//      echo '<pre>';print_r($data);echo'</pre>';
//dd($data);
        return $data;

    }

    /**
     * 小程序首页登录
     */
    public function xcxLogin(Request $request)
    {
        //接收code
        $code = $request->get('code');

        //使用code
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . env('WX_XCX_APPID') . '&secret=' . env('WX_XCX_SECRET') . '&js_code=' . $code . '&grant_type=authorization_code';

        $data = json_decode(file_get_contents($url), true);

        //自定义登录状态
        if (isset($data['errcode']))     //有错误
        {
            $response = [
                'errno' => 50001,
                'msg' => '登录失败',
            ];

        } else {              //成功
            $openid = $data['openid'];          //用户OpenID
            //判断新用户 老用户
            $u = UserxModel::where(['openid' => $openid])->first();
            if ($u) {
                // TODO 老用户
                $uid = $u->id;
                //更新用户信息

            } else {
                // TODO 新用户
                $u_info = [
                    'openid' => $openid,
                    'add_time' => time(),
                    'type' => 3        //小程序
                ];

                $uid = UserxModel::insertGetId($u_info);
//                dd($uid);
            }

            //生成token
            $token = sha1($data['openid'] . $data['session_key'] . mt_rand(0, 999999));
            //保存token
            $redis_login_hash = 'h:xcx:login:' . $token;

            $login_info = [
                'uid' => $uid,
                'user_name' => "",
                'login_time' => date('Y-m-d H:i:s'),
                'login_ip' => $request->getClientIp(),
                'token' => $token,
                'openid'    => $openid
            ];


            //保存登录信息
            Redis::hMset($redis_login_hash, $login_info);
            // 设置过期时间
            Redis::expire($redis_login_hash, 7200);

            $response = [
                'errno' => 0,
                'msg' => 'ok',
                'data' => [
                    'token' => $token
                ]
            ];
        }

        return $response;

    }

    /**
     * 小程序 个人中心登录
     */
    public function userLogin(Request $request)
    {
        //接收code
        //$code = $request->get('code');
        $token = $request->get('token');

        //获取用户信息
        $userinfo = json_decode(file_get_contents("php://input"), true);
//        dd($userinfo);
        $redis_login_hash = 'h:xcx:login:' . $token;
        //获取openid
        $openid = Redis::hget($redis_login_hash,'openid');
//         dd($openid);
         //获取uid
        $uid = Redis::hget($redis_login_hash,'uid');
        //        $uid = UserxModel::where('openid' ,$openid)->get('id')->toArray();
//        $uid = $uid[0]["id"];
//        dd($uid);



        if(empty($umy=UsermModel::where('openid' ,$openid)->first())){
            $u_info=[
                "uid"=>$uid,
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

            UsermModel::insertGetId($u_info);
        }elseif($umy->update_time == 0){     // 未更新过资料
            //因为用户已经在首页登录过 所以只需更新用户信息表
            $u_infos= [
                'nickname' => $userinfo['u']['nickName'],
                'sex' => $userinfo['u']['gender'],
                'language' => $userinfo['u']['language'],
                'city' => $userinfo['u']['city'],
                'province' => $userinfo['u']['province'],
                'country' => $userinfo['u']['country'],
                'headimgurl' => $userinfo['u']['avatarUrl'],
                'update_time'   => time()
            ];
            UsermModel::where('openid' ,$openid)->update($u_infos);
        }

        $response = [
            'errno' => 0,
            'msg' => 'ok',
        ];

        return $response;

    }

    /**
     * 商品列表
     */
    public function  goodsList(Request $request){

            $page_size =$request->get('ps');
//            dd($page_size);
            $g=GoodsModel::select('goods_id','goods_name','shop_price','goods_img')->paginate($page_size);

            $response=[
                'errno' => 0,
                'msg'   => 'ok',
                'list'  =>$g->items()
            ];
      return $response;
    }

    /**
     * 商品详情
     */
    public function  goods(Request $request){
       $goods_id = Request()-> id;
        $gs=GoodsModel::select('goods_id','goods_name','shop_price','goods_word','goods_desc','goods_imgs')->where('goods_id',$goods_id)->first()->toArray();
//        $goodsdd($gs);_imgs=explode('|',$g['goods_imgs']);

        $response=[
            'goods_id' =>$gs['goods_id'],
            'goods_name'=>$gs['goods_name'],
            'shop_price'=>$gs['shop_price'],
            'goods_word'=>$gs['goods_word'],
            'goods_desc'=>explode('|',$gs['goods_desc']),
            'goods_imgs'=>explode('|',$gs['goods_imgs'])
        ];

        return $response;
    }

    /**
     * 收藏
     */

    public function addFav(Request $request){
        $goods_id=$request->get('id');
//        dd($goods_id);die;
        $token=$request->get('token');

        $redis_login_hash = 'h:xcx:login:' . $token;
        $uid = Redis::hget($redis_login_hash,'uid');

        //加入收藏 Redis有序集合
        // 用户收藏的商品有序集合
        $redis_key = 'ss:goods:fav:'.$uid;
        Redis::Zadd($redis_key,time(),$goods_id);       //将商品id加入有序集合，并给排序值

        $response = [
            'errno' => 0,
            'msg'   => 'ok'
        ];

        return $response;
    }

    /**
     * 加入购物车
     */

    public function addCart(Request $request)
    {
        $goods_id = $request->post('goodsid');
        $goods_name = $request->post('goods_name');
        $buy_number = $request->post('buy_number');
        //   dd($goods_id);
        $uid = $_SERVER['uid'];
//        dd($uid);

        //查询商品的价格  购买数量  商品名称
        $shop_price = GoodsModel::find($goods_id)->shop_price;
//        $buy_number=GoodsModel::find($goods_id)->buy_number;
//        $goods_name=GoodsModel::find($goods_id)->goods_name;

        $g = CartModel::where('goods_id', $goods_id)->first();
        if ($g) {

            //购物车商品数量增加
            CartModel::where('goods_id', $goods_id)->increment('buy_number');
            $response = [
                'errno' => 0,
                'msg' => 'ok'
            ];
        } else {
            //将商品存储购物车表 或 Redis
            $info = [
                'goods_id' => $goods_id,
                'uid' => $uid,
                'goods_name' => $goods_name,
                'buy_number' => $buy_number,
                'add_time' => time(),
                'shop_price' => $shop_price
            ];
            $id = CartModel::insertGetId($info);


            if ($id) {
                $response = [
                    'errno' => 0,
                    'msg' => 'ok'
                ];
            } else {
                $response = [
                    'errno' => 50002,
                    'msg' => '加入购物车失败'
                ];
            }


        }
        return $response;
    }

    /**
     * 小程序购物车列表
     */
    public function cartList()
    {
//        $uid = UserxModel::where('openid' ,$openid)->get('id')->toArray();
//       $uid = $uid[0]["id"];
        $uid =CartModel::get('uid')->toArray();
        $uid = $uid[0]["uid"];
//        dd($uid);
        $goods = CartModel::where(['uid'=>$uid])->get();
        if($goods)      //购物车有商品
        {
            $goods = $goods->toArray();
            //dd($goods);
            foreach($goods as $k=>&$v)
            {
                //根据购物表的goods_id 去  商品表 中查询  goods_id 所对应商品数据
                $g = GoodsModel::find($v['goods_id']);
                //dd($g);
                $v['goods_name'] = $g->goods_name;
            }
        }else{          //购物车无商品
            $goods = [];
        }

        //echo '<pre>';print_r($goods);echo '</pre>';die;
        $response = [
            'errno' => 0,
            'msg'   => 'ok',
            'data'  => [
                'list'  => $goods
            ]
        ];

        return $response;
    }



}

