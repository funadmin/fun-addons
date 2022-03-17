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
 * Date: 2019/10/3
 */
//配置api 接口
return [
    'authentication' => "authentication",
    'is_jwt' => 1,////是否开启jwt配置 1开启
    'jwt_key' => 'funadmin',//jwtkey，请一定记得修改
    'timeDif' => 10000,//时间误差
    'refreshExpires' => 3600 * 24 * 30,   //刷新token过期时间
    'expires' => 7200 * 12,//token有效期
    'responseType' => 'json',
    'authapp' => false,//是否启用appid;
    'driver'=>'redis',
];