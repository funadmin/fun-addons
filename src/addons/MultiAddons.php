<?php
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace fun\addons;

use Closure;
use think\App;
use think\exception\HttpException;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Lang;
use think\Request;
use think\Response;

/**
 * 多应用模式支持
 */
class MultiAddons
{

    /** @var App */
    protected $app;

    protected $domain;

    protected $subdomain;

    protected $addonsPath;

    /**
     * 应用名称
     * @var string
     */
    protected $appName;

    /**
     * 应用名称
     * @var string
     */
    protected $moduleName = 'frontend';

    /**
     * 网址路径
     * @var string
     */
    protected $uri;

    /**
     * 网址路径
     * @var string
     */
    protected $controller;


    protected $action;


    public function __construct(App $app)
    {
        $this->app  = $app;
//        //网址路径(不包含域名，包含后缀名)
        $this->uri = $this->app->request->pathinfo();
        $this->subDomain = $this->app->request->subDomain();
        $this->domain    = $this->app->request->host(true);
//      //根据路径获取模块和插件名称
        $uri = explode('/',$this->uri);
        if(count($uri)==4 && $uri[0] =='addons'){
            $this->appName = $uri[1];
            $this->addonsPath = $this->app->getRootPath().'addons'.DS.$this->appName.DS;
            $this->moduleName  = $uri[2];
            $this->controller = $uri[3];
            $this->action = $uri[4]??'index';
        }else{
            $route = Config::get('addons.route');
            $this->uri =  $this->uri?:"/";
            if($route){
                foreach ($route as $key => $value){
                    $domain =array_filter( explode(',',$value['domain']));
                    if($domain && in_array($this->subDomain,$domain) &&  $value['rule']){
                        $rewrite  = $value['rule'];
                        $rewrite_val = array_values($rewrite);
                        $rewrite_key = array_keys($rewrite);
                        $key = false;
                        foreach ($rewrite_key as $i=>$v) {
                            if($v==$this->uri){
                                $key = $i;
                                break;
                            }
                            $v= explode('/',$v);
                            if($this->uri == $v[0]){
                                $key = $i;
                                break;
                            }
                        }
                        if($key!==false){
                            $uri = explode('/',$rewrite_val[$key]);
                            $this->appName = $value['addons'];
                            $this->addonsPath = $this->app->getRootPath().'addons'.DS.$this->appName.DS;
                            $this->moduleName  = $uri[1];
                            $this->controller = $uri[2];
                            $this->action = $uri[3]??'index';
                        }
                    }
                }
            }
        }
    }

    /**
     * 多应用解析
     * @access public
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        if (!$this->loadApp()) {
            return $next($request);
        }
        return $this->app->middleware->pipeline('app')
            ->send($request)
            ->then(function ($request) use ($next) {
                return $next($request);
            });
    }

    /**
     * 加载配置，路由，语言，中间件等
     */
    protected function loadApp()
    {
        if($this->appName){
            if (is_file($this->addonsPath . 'middleware.php')) {
                $this->app->middleware->import(include $this->addonsPath . 'middleware.php', 'route');
            }
            if (is_file($this->addonsPath . 'common.php')) {
                include_once  $this->addonsPath . 'common.php';
            }
            if (is_file($this->addonsPath . 'provider.php')) {
                $this->app->bind(include $this->addonsPath . 'provider.php');
            }
            //事件
            if (is_file($this->addonsPath. 'event.php')) {
                $this->app->loadEvent(include $this->addonsPath . 'event.php');
            }
            $modulePath = $this->addonsPath.$this->moduleName.DS;
            $results = scandir($modulePath);
            foreach ($results as $childname){
                if (in_array($childname, ['.', '..','public','view'])) {
                    continue;
                }
                if (is_file($modulePath . 'middleware.php')) {
                    $this->app->middleware->import(include $modulePath . 'middleware.php', 'app');
                }
                if (is_file($modulePath . 'common.php')) {
                    include_once  $modulePath . 'common.php';
                }
                if (is_file($modulePath . 'provider.php')) {
                    $this->app->bind(include $modulePath. 'provider.php');
                }
                //事件
                if (is_file($modulePath. 'event.php')) {
                    $this->app->loadEvent(include $modulePath . 'event.php');
                }

                $commands = [];
                //配置文件
                $addons_config_dir = $modulePath . 'config' . DS;
                if (is_dir($addons_config_dir)) {
                    $files = [];
                    $files = array_merge($files, glob($addons_config_dir . '*' .$this->app->getConfigExt()));
                    if($files){
                        foreach ($files as $file) {
                            if (file_exists($file)) {
                                if(substr($file,-11) =='console.php'){
                                    $commands_config = include_once $file;
                                    isset($commands_config['commands']) && $commands = array_merge($commands, $commands_config['commands']);
                                    !empty($commands) &&
                                    \think\Console::starting(function (\think\Console $console) {$console->addCommands($commands);});
                                }else{
                                    $this->app->config->load($file, pathinfo($file, PATHINFO_FILENAME));
                                }
                            }
                        }
                    }
                }
                //语言文件
                $addons_lang_dir = $modulePath  . 'lang' . DS;
                if (is_dir($addons_lang_dir)) {
                    $files = glob($addons_lang_dir .$this->app->lang->defaultLangSet() . '.php');
                    foreach ($files as $file) {
                        if (file_exists($file)) {
                            Lang::load([$file]);
                        }
                    }
                }
            }
        }
        return true;

    }

}
