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

namespace fun\auth;

use app\common\service\PredisService;
use think\facade\Request;
use fun\auth\Send;
use fun\auth\Oauth;
use think\facade\Cache;
use app\common\model\WxFans;
use think\facade\Db;
use think\Lang;

/**
 * 生成token
 */
class Token
{
    use Send;

    /**
     * @var bool
     * 是否需要验证数据库账号
     */
    public $authapp = false;
    /**
     * 测试appid，正式请数据库进行相关验证
     */
    public $appid = 'funadmin';
    /**
     * appsecret
     */
    public $appsecret = '';

    /**
     * 构造方法
     * @param Request $request Request对象
     */
    public function __construct(Request $request)
    {
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Headers:Accept,Referer,Host,Keep-Alive,User-Agent,X-Requested-With,Cache-Control,Content-Type,Cookie,token');
        header('Access-Control-Allow-Credentials:true');
        header('Access-Control-Allow-Methods:GET, POST, PATCH, PUT, DELETE,OPTIONS');
        $this->request = Request::instance();
        if ($this->authapp) {
            $appid = Request::post('appid');
            $appsecret = Request::post('appsecret');
            $oauth2_client = Db::name('oauth2_client')->where('appid', $appid)->find();
            if (!$oauth2_client) {
                $this->error('Invalid authorization credentials', '', 401);
            }
            if ($oauth2_client['appsecret'] != $appsecret) {
                $this->error(lang('appsecret is not right'));
            }
            $this->appid = $oauth2_client['appid'];
            $this->appsecret = $oauth2_client['appsecret'];
        }
    }

    /**
     */
    public function accessToken(Request $request)
    {
        //参数验证
        $validate = new \fun\auth\validate\Token;
        if ($this->authapp) {
            if (!$validate->scene('authapp')->check(Request::post())) {
                $this->error($validate->getError());
            }
        } else {
            if (!$validate->scene('noauthapp')->check(Request::post())) {
                $this->error($validate->getError());
            }
        }

        $this->checkParams(Request::post());  //参数校验
        //数据库已经有一个用户,这里需要根据input('mobile')去数据库查找有没有这个用户
        $memberInfo = $this->getMember(Request::post('username'), Request::post('password'));
        //虚拟一个uid返回给调用方
        try {
            $accessToken = $this->setAccessToken(array_merge($memberInfo, Request::post()));  //传入参数应该是根据手机号查询改用户的数据
        } catch (\Exception $e) {
            $this->error($e, $e->getMessage(), 500);
        }
        $this->success('success', $accessToken);

    }

    /**
     * token 过期 刷新token
     */
    public function refresh($refresh_token = '', $appid = '')
    {
        $cache_refresh_token = Cache::get($this->refreshAccessTokenPrefix . $appid);  //查看刷新token是否存在
        if (!$cache_refresh_token) {
            $this->error('refresh_token is null', '', 401);
        } else {
            if ($cache_refresh_token !== $refresh_token) {
                $this->error('refresh_token is error', '', 401);
            } else {    //重新给用户生成调用token
                $data['appid'] = $appid;
                $accessToken = $this->setAccessToken($data);
                $this->success('success', $accessToken);
            }
        }
    }

    /**
     * 参数检测和验证签名
     */
    public function checkParams($params = [])
    {
        //时间戳校验
        if (abs($params['timestamp'] - time()) > $this->timeDif) {
            
            $this->error('请求时间戳与服务器时间戳异常' . time(), '', 401);
        }
        if ($this->authapp) {
            //appid检测，查找数据库或者redis进行验证
            if ($params['appid'] !== $this->appid) {
                $this->error('appid 错误', '', 401);
            }
        }
        //签名检测
        $Oauth = new Oauth();
        $sign = $Oauth->makeSign($params, $this->appsecret);
        if ($sign !== $params['sign']) {
            $this->error('sign错误','', 401);
        }
        

    }

    /**
     * 设置AccessToken
     * @param $clientInfo
     * @return int
     */
    protected function setAccessToken($clientInfo)
    {
        //生成令牌
        $accessToken = $this->buildAccessToken();
        $refresh_token = $this->getRefreshToken($clientInfo['appid']);
        $accessTokenInfo = [
            'access_token' => $accessToken,//访问令牌
            'expires_time' => time() + $this->expires,      //过期时间时间戳
            'refresh_token' => $refresh_token,//刷新的token
            'refresh_expires_time' => time() + $this->refreshExpires,      //过期时间时间戳
            'client' => $clientInfo,//用户信息
        ];
        $this->saveAccessToken($accessToken, $accessTokenInfo);  //保存本次token
        $this->saveRefreshToken($refresh_token, $clientInfo['appid']);
        return $accessTokenInfo;
    }

    /**
     * 获取刷新用的token检测是否还有效
     */
    public function getRefreshToken($appid = '')
    {
        return Cache::get($this->refreshAccessTokenPrefix . $appid) ? Cache::get($this->refreshAccessTokenPrefix . $appid) : $this->buildAccessToken();
    }

    /**
     * 生成AccessToken
     * @return string
     */
    protected function buildAccessToken($lenght = 32)
    {
        //生成AccessToken
        $str_pol = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789abcdefghijklmnopqrstuvwxyz";
        return substr(str_shuffle($str_pol), 0, $lenght);

    }

    /**
     * 存储token
     * @param $accessToken
     * @param $accessTokenInfo
     */
    protected function saveAccessToken($accessToken, $accessTokenInfo)
    {
        $token_type = config('api.auth.token_type');
        cache($this->accessTokenPrefix . $accessToken, $accessTokenInfo, $this->expires);
    }

    /**
     * 刷新token存储
     * @param $accessToken
     * @param $accessTokenInfo
     */
    protected function saveRefreshToken($refresh_token, $appid)
    {
        //存储RefreshToken
        cache($this->refreshAccessTokenPrefix . $appid, $refresh_token, $this->refreshExpires);
    }

    protected function getMember($membername, $password)
    {
        $member = Db::name('member')->where('username', $membername)
            ->whereOr('mobile', $membername)
            ->whereOr('email', $membername)->find();
        if ($member) {
            if (password_verify($password, $member['password'])) {
                $member['uid'] = $member['id'];
                return $member;
            } else {
                $this->error(lang('Password is not right'), '', 401);
            }
        } else {
            $this->error(lang('Account is not exist'), '', 401);
        }
    }
}