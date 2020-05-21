<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/10 0010
 * Time: 下午 5:24
 */

namespace controllers;

use vendor\base\Controller;
use \vendor\sdk\ApiClient;


class TestController extends Controller
{
    //App应用秘钥信息
    const  AESSCRE_KEY =    '15812f72db1c58ae79083f11c4be0b8d';
    const  AESSCR_SECRET =  'c669a863a999c64bb9d1c5eebd00bee6eadc15edd9ae482ae35cb77c5418faab';
    const  SERVER = 'mengoo';       //虚拟服务名称

     protected $access = [
             'index' => ['student'],
     ];

    //获取code 授权初始码 通过code 刷新access-token
    public function actionIndex()
    {
        $client =   new ApiClient(static::AESSCRE_KEY,static::AESSCR_SECRET, static::SERVER);
        $method = "POST";
        $path = 'app/app-token/code';
        $params =  ['app_key' => '15812f72db1c58ae79083f11c4be0b8d']; //app_key
        $headers = [
            'Host' => 'mengoo',
            'Cookie' =>'doctoru-test-zy=b29v5ef2ssirfmvv5fbk9nirn5;Path=/; HttpOnly'
        ];

        $body = json_encode($params,true);
        $data  =   $client->sendRequest($method , $path , $params ,$headers ,$body);
        $code = json_decode($data[2],true);

        //返回code参数
        // $res = [
        //      'code' => '2d0c1c1278580754cebd60ebd378813c48c1632eda91ea6ae12c0c89d90008ae',
        //      'expired_in' => 600
        //  ];

        //
        //$client =   new ApiClient(static::AESSCRE_KEY,static::AESSCR_SECRET, static::SERVER);
        $method = "POST";
        $path = 'app/app-token/access-token';
        $params =  ['code' => $code['code']];
        $headers = [
            'Host' => 'mengoo',
            'Cookie' =>'doctoru-test-zy=b29v5ef2ssirfmvv5fbk9nirn5;Path=/; HttpOnly'
        ];
        $body = json_encode($params,true);
        $data  = $client->sendRequest($method , $path , $params ,$headers ,$body);

        $token = json_decode($data[2],true);

        var_dump($token);
        //返回token参数
        // $token = [
        //      'access_token' => '6ac8529a48ae6fabc5ad2c88e43a9fe84b04cbae23207b2b1d0d30c8fab3ab02'
        //      'refresh_token' => 'f3346af85e3c7624439543a80d6424665d0215c871fb228497482978cb372dac'
        //]
    }

    //获取资源
    public function actionLink()
    {
        $client =   new ApiClient(static::AESSCRE_KEY, static::AESSCR_SECRET, static::SERVER);
        $method = "POST";
        $path =   'api/resource/create-link';
        $params =  [
            'app_key' => '15812f72db1c58ae79083f11c4be0b8d',
            'rid'=>'D96',
            'access_token'=>'4dd971e9a1339de52406265ffd8048d5df94f565cf7bea96e790cb2591616651'
        ];
        $headers = [];
        $body = json_encode($params,true);
        $data = $client->sendRequest($method , $path , $params ,$headers ,$body);
        $rir = json_decode($data[2],true);
        var_dump($rir['access_link']);
        //返回rir资源
        //$access_link = [ 'mengoo.com/resources/ror/access/8d9ae92679febcdd79159b03b41c3c07/Oyn7Gw7b'];
    }

    //获取用户信息
    public function ActionUser()
    {
        $client =   new ApiClient(static::AESSCRE_KEY, static::AESSCR_SECRET, static::SERVER);
        $method = "POST";
        $path = 'api/user/profile';
        $params =  [
            'access_token'=>'4dd971e9a1339de52406265ffd8048d5df94f565cf7bea96e790cb2591616651'
        ];
        $headers = [];
        $body = json_encode($params,true);
        $data = $client->sendRequest($method , $path , $params ,$headers ,$body);
        $userinfo = json_decode($data[2],true);

        var_dump($userinfo);
    }
}