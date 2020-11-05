<?php
/**
 * FunAdmin
 * ============================================================================
 * 版权所有 2017-2028 FunAdmin，并保留所有权利。
 * 网站地址: https://www.FunAdmin.com
 * ----------------------------------------------------------------------------
 * 采用最新Thinkphp6实现
 * ============================================================================
 * Author: yuege
 * Date: 2019/9/22
 */
namespace fun\auth;

use think\exception\HttpResponseException;
use think\Response;

trait Send
{
    public static $timeDif = 10000;
    public static $accessTokenPrefix = 'accessToken_';
    public static $refreshAccessTokenPrefix = 'refreshAccessToken_';
    public static $refreshExpires = 3600 * 24 * 30;   //刷新token过期时间
    public static $expires = 7200;
    public static $responseType = 'json';

    /**
     * 操作成功返回的数据
     * @param string $msg 提示信息
     * @param mixed $data 要返回的数据
     * @param int $code 错误码，默认为1
     * @param string $type 输出类型
     * @param array $header 发送的 Header 信息
     */
    public static function success($msg = '', $data = null, $code = 200, $type = null, array $header = [])
    {
        self::result($msg, $data, $code, $type, $header);
    }

    /**
     * 操作失败返回的数据
     * @param string $msg 提示信息
     * @param mixed $data 要返回的数据
     * @param int $code 错误码，默认为0
     * @param string $type 输出类型
     * @param array $header 发送的 Header 信息
     */
    public static function error($msg = '', $data = null, $code = 404, $type = null, array $header = [])
    {
        self::result($msg, $data, $code, $type, $header);
    }

    protected static function result($msg, $data = null, $code = 404, $type = null, array $header = [])
    {
        $result = [
            'code' => $code,
            'msg' => $msg,
            'time' => \think\facade\Request::instance()->server('REQUEST_TIME'),
            'data' => $data,
        ];
        // 如果未设置类型则自动判断
        $type = $type ? $type : (\think\facade\Request::param(config('var_jsonp_handler')) ? 'jsonp' : self::$responseType);
        // 发送头部信息
        foreach ($header as $name => $val) {
            if (is_null($val)) {
                header($name);
            } else {
                header($name . ':' . $val);
            }
        }
        $response = Response::create($result, $type, $code)->header($header);
        throw new HttpResponseException($response);
    }
}
