<?php
declare(strict_types=1);

use speed\addons\Service;
use think\facade\Db;
use think\facade\App;
use think\facade\Config;
use think\facade\Event;
use think\facade\Route;
use think\facade\Cache;
use think\helper\{
    Str, Arr
};

\think\Console::starting(function (\think\Console $console) {
    $console->addCommands([
        'addons:config' => '\\speed\\addons\\command\\SendConfig'
    ]);
});

// 插件类库自动载入
spl_autoload_register(function ($class) {

    $class = ltrim($class, '\\');

    $dir = app()->getRootPath();
    $namespace = 'addons';

    if (strpos($class, $namespace) === 0) {
        $class = substr($class, strlen($namespace));
        $path = '';
        if (($pos = strripos($class, '\\')) !== false) {
            $path = str_replace('\\', '/', substr($class, 0, $pos)) . '/';
            $class = substr($class, $pos + 1);
        }
        $path .= str_replace('_', '/', $class) . '.php';
        $dir .= $namespace . $path;

        if (file_exists($dir)) {
            include $dir;
            return true;
        }

        return false;
    }

    return false;

});

if (!function_exists('hook')) {
    /**
     * 处理插件钩子
     * @param string $event 钩子名称
     * @param array|null $params 传入参数
     * @param bool $once 是否只返回一个结果
     * @return mixed
     */
    function hook($event, $params = null, bool $once = false)
    {
        $result = Event::trigger($event, $params, $once);
        return join('', $result);
    }
}

if (!function_exists('get_addons_info')) {
    /**
     * 读取插件的基础信息
     * @param string $name 插件名
     * @return array
     */
    function get_addons_info($name)
    {
        $addon = get_addons_instance($name);
        if (!$addon) {
            return [];
        }

        return $addon->getInfo();
    }
}

if (!function_exists('get_addons_instance')) {
    /**
     * 获取插件的单例
     * @param string $name 插件名
     * @return mixed|null
     */
    function get_addons_instance($name)
    {
        static $_addons = [];
        if (isset($_addons[$name])) {
            return $_addons[$name];
        }
        $class = get_addons_class($name);
        if (class_exists($class)) {
            $_addons[$name] = new $class(app());

            return $_addons[$name];
        } else {
            return null;
        }
    }
}

if (!function_exists('get_addons_class')) {
    /**
     * 获取插件类的类名
     * @param string $name 插件名
     * @param string $type 返回命名空间类型
     * @param string $class 当前类名
     * @return string
     */
    function get_addons_class($name, $type = 'hook', $class = null,$module='backend')
    {
        $name = trim($name);
        // 处理多级控制器情况
        if (!is_null($class) && strpos($class, '.')) {
            $class = explode('.', $class);
            $class[count($class) - 1] = Str::studly(end($class));
            $class = implode('\\', $class);

        } else {
            $class = Str::studly(is_null($class) ? $name : $class);
        }
        switch ($type) {
            case 'controller':
//                $namespace = '\\addons\\' . $name . '\\controller\\' .$module.'\\'. $class;
                 $namespace = '\\addons\\' . $name.'\\'  .$module. '\\controller\\' .$class;
                break;
            default:
                $namespace = '\\addons\\' . $name . '\\Plugin';
        }

        return class_exists($namespace) ? $namespace : '';
    }
}


if (!function_exists('get_addons_config')) {
    /**
     * 获取插件的配置
     * @param string $name 插件名
     * @return mixed|null
     */
    function get_addons_config($name)
    {
        $addon = get_addons_instance($name);
        if (! $addon) {
            return [];
        }

        return $addon->getConfig($name);
    }
}

if (!function_exists('addons_url')) {
    /**
     * 插件显示内容里生成访问插件的url
     * @param $url
     * @param array $param
     * @param bool|string $suffix 生成的URL后缀
     * @param bool|string $domain 域名
     * @return bool|string
     */
    function addons_url($url = '', $param = [], $suffix = true, $domain = false)
    {
        $request = app('request');
        if (empty($url)) {
            // 生成 url 模板变量
            $addons = $request->addon;
            $controller = $request->controller();
            $controller = str_replace('/', '.', $controller);
            $action = $request->action();
        } else {
            $url = Str::studly($url);
            $url = parse_url($url);
            if (isset($url['scheme'])) {
                $addons = strtolower($url['scheme']);
                $controller = $url['host'];
                $action = trim($url['path'], '/');
            } else {
                $route = explode('/', $url['path']);
                $addons = $request->addon;
                $action = array_pop($route);
                $controller = array_pop($route) ?: $request->controller();
            }
            $controller = Str::snake((string)$controller);

            /* 解析URL带的参数 */
            if (isset($url['query'])) {
                parse_str($url['query'], $query);
                $param = array_merge($query, $param);
            }
        }

        return Route::buildUrl("@addons/{$addons}/{$controller}/{$action}", $param)->suffix($suffix)->domain($domain);
    }
}


/**
 * 获得插件列表
 * @return array
 */
if (!function_exists('get_addons_list')) {

    function get_addons_list()
    {   
        if(! Cache::get('addonslist')){
            $service = new Service(App::instance()); // 获取service 服务
            $addons_path = $service->getAddonsPath(); // 插件列表
            $results = scandir($addons_path);
            $list = [];
            foreach ($results as $name) {

                if ($name === '.' or $name === '..')
                    continue;
                if (is_file($addons_path . $name))
                    continue;
                $addonDir = $addons_path . $name . DS;
                if (!is_dir($addonDir))
                    continue;

                if (!is_file($addonDir . 'Plugin' . '.php'))
                    continue;
                $addon = get_addons_instance($name);
                $info = $addon->getInfo($name);
                if (!isset($info['name']))
                    continue;
                $info['url'] = (string)addons_url();
                $list[$name] = $info;
                Cache::set('addonslist',$list);
            }
        }else{
            $list = Cache::get('addonslist')  ;
        }
        
        return $list;
    }
}



/**
 * 获得插件自动加载的配置
 * @param bool $chunk 是否清除手动配置的钩子
 * @return array
 */
if (!function_exists('get_addons_autoload_config')) {

    function get_addons_autoload_config($chunk = false)
    {
        // 读取addons的配置
        $config = (array)Config::get('addons');
        if ($chunk) {
            // 清空手动配置的钩子
            $config['hooks'] = [];
        }
        $route = [];
        // 读取插件目录及钩子列表
        $base = get_class_methods("\\speed\\Addons");
        $base = array_merge($base, ['install', 'uninstall', 'enabled', 'disabled']);

        $url_domain_deploy = Config::get('route.url_domain_deploy');
        $addons = get_addons_list();
        $domain = [];
        foreach ($addons as $name => $addon) {
            if (!$addon['status'])
                continue;
            // 读取出所有公共方法
            $methods = (array)get_class_methods("\\addons\\" . $name . "\\" . 'Plugin');
            // 跟插件基类方法做比对，得到差异结果
            $hooks = array_diff($methods, $base);
            // 循环将钩子方法写入配置中
            foreach ($hooks as $hook) {
                $hook = Str::studly($hook);
                if (!isset($config['hooks'][$hook])) {
                    $config['hooks'][$hook] = [];
                }
                // 兼容手动配置项
                if (is_string($config['hooks'][$hook])) {
                    $config['hooks'][$hook] = explode(',', $config['hooks'][$hook]);
                }
                if (!in_array($name, $config['hooks'][$hook])) {
                    $config['hooks'][$hook][] = $name;
                }
            }
            $conf = get_addons_config($addon['name']);
            if ($conf) {
                $conf['rewrite'] = isset($conf['rewrite']) && is_array($conf['rewrite']) ? $conf['rewrite'] : [];
                $rule =  $conf['rewrite']?$conf['rewrite']['value']:[] ;
                if ($url_domain_deploy && isset($conf['domain']) && $conf['domain']) {
                    $domain[] = [
                        'addons' => $addon['name'],
                        'indomain' => $conf['indomain'],
                        'rule' => $rule
                    ];
                } else {
                    $route = array_merge($route, $rule);
                }
            }
        }
        $config['route'] = $route;
        $config['route'] = array_merge($config['route'], $domain);
        return $config;
    }
}
/**
 * 导入SQL
 *
 * @param string $name 插件名称
 * @return  boolean
 */
if (!function_exists('importsql')) {

    function importsql($name)
    {
        $service = new Service(App::instance()); // 获取service 服务
        $addons_path = $service->getAddonsPath(); // 插件列表
        $sqlFile = $addons_path. $name . DS . 'install.sql';
        if (is_file($sqlFile)) {
            $lines = file($sqlFile);
            $templine = '';
            foreach ($lines as $line) {
                if (substr($line, 0, 2) == '--' || $line == '' || substr($line, 0, 2) == '/*')
                    continue;

                $templine .= $line;
                if (substr(trim($line), -1, 1) == ';') {
                    $templine = str_ireplace('__PREFIX__', config('database.connections.mysql.prefix'), $templine);
                    $templine = str_ireplace('INSERT INTO ', 'INSERT IGNORE INTO ', $templine);
                    try {
                        Db::query($templine);
                    } catch (\PDOException $e) {
                        throw new PDOException($e->getMessage());

                    }
                    $templine = '';
                }
            }
           
        }
        return true;
    }
}



/**
 * 卸载SQL
 *
 * @param string $name 插件名称
 * @return  boolean
 */
if (!function_exists('uninstallsql')) {

    function uninstallsql($name)
    {
        $service = new Service(App::instance()); // 获取service 服务
        $addons_path = $service->getAddonsPath(); // 插件列表
        $sqlFile = $addons_path. $name . DS . 'uninstall.sql';
        if (is_file($sqlFile)) {
            $lines = file($sqlFile);
            $templine = '';
            foreach ($lines as $line) {
                if (substr($line, 0, 2) == '--' || $line == '' || substr($line, 0, 2) == '/*')
                    continue;
                $templine .= $line;
                if (substr(trim($line), -1, 1) == ';') {
                    $templine = str_ireplace('__PREFIX__', config('database.connections.mysql.prefix'), $templine);
                    try {
                        Db::query($templine);
                    } catch (\PDOException $e) {
                        throw new PDOException($e->getMessage());

                    }
                    $templine = '';
                }
            }
        }
        return true;
    }
}
