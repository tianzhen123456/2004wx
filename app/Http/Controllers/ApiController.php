<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use DB;
use App\models\GoodsModel;
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
       $goods_id = Request()->id;
        $gs=GoodsModel::select('goods_name','shop_price','goods_desc','goods_imgs')->where('goods_id',$goods_id)->first()->toArray();
//        $goodsdd($gs);_imgs=explode('|',$g['goods_imgs']);

        $response=[
            'goods_name'=>$gs['goods_name'],
            'shop_price'=>$gs['shop_price'],
            'goods_desc'=>$gs['goods_desc'],
            'goods_imgs'=>explode('|',$gs['goods_imgs'])
        ];

        return $response;
    }
}
