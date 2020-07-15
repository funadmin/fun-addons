<?php
declare(strict_types=1);

namespace speed\addons;

use speed\helper\FileHelper;
use think\Exception;
use think\Route;
use think\helper\Str;
use think\facade\Config;
use think\facade\Lang;
use think\facade\Cache;
use think\facade\Event;
use speed\addons\middleware\Addons;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
/**
 * 插件服务
 * Class Service
 * @package speed\addons
 */
class Service extends \think\Service
{
    protected $addons_path;
    protected $addons_name;

    public function register()
    {
        $this->addons_path = $this->getAddonsPath();
        $this->registerRoutes(function (Route $route) {
            // 路由脚本
            $execute = '\\speed\\addons\\Route::execute';
            // 注册控制器路由
            $route->rule("addons/:addon/[:module]/[:controller]/[:action]", $execute)
                ->middleware(Addons::class);
            // 自定义路由
            $routes = (array) Config::get('addons.route', []);
            foreach ($routes as $key => $val) {
                if (!$val) {
                    continue;
                }
                if (is_array($val)) {
                    $domain = $val['domain'];
                    $rules = [];
                    foreach ($val['rule'] as $k => $rule) {
                        list($module,$addon, $controller, $action) = explode('/', $rule);
                        $rules[$k] = [
                            'module'        => $module,
                            'addons'        => $addon,
                            'controller'    => $controller,
                            'action'        => $action,
                            'indomain'      => 1,
                        ];
                    }
                    $route->domain($domain, function () use ($rules, $route, $execute) {
                        // 动态注册域名的路由规则
                        foreach ($rules as $k => $rule) {
                            $route->rule($k, $execute)
                                ->name($k)
                                ->completeMatch(true)
                                ->append($rule);
                        }
                    });
                } else {
                    list($module,$addon, $controller, $action) = explode('/', $val);
                    $route->rule($key, $execute)
                        ->name($key)
                        ->completeMatch(true)
                        ->append([
                            'module' => $module,
                            'addons' => $addon,
                            'controller' => $controller,
                            'action' => $action
                        ]);
                }
            }
        });
        // 绑定插件容器
        $this->app->bind('addons', Service::class);

    }

    public function boot()
    {
        // 自动载入插件
        $this->loadRoutes();
        //加载语言
        $this->loadLang();
        //挂载插件的自定义路由
        $this->autoload();
        // 加载插件事件
        $this->loadEvent();
        // 加载插件系统服务
        $this->loadService();

    }


    private function loadLang(){
        // 加载系统语言包
        Lang::load([
            $this->app->getRootPath() . '/vendor/speed/speed-addons/src/lang/zh-cn.php'
        ]);
        // 加载应用默认语言包
        $this->app->loadLangPack($this->app->lang->defaultLangSet());
    }
    /**
     *  加载插件自定义路由文件
     */
    private function loadRoutes()
    {
        $addons_route_dir = scandir($this->addons_path);
        foreach ($addons_route_dir as $dir) {
            if (in_array($dir, ['.', '..'])) {
                continue;
            }
            $addons_route_dir = $this->addons_path . $dir . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR;
            if (is_dir($addons_route_dir)) {
                $files = glob($addons_route_dir . '*.php');
                foreach ($files as $file) {
                    include $file;
                }
            }
        }
    }
    /**
     * 插件事件
     */
    private function loadEvent()
    {
        if (is_file($this->getAddonsPath() . 'event.php')) {
            $this->app->loadEvent(include $this->getAddonsPath() . 'event.php');
        }
        $hooks = $this->app->isDebug() ? [] : Cache::get('hooks', []);
        if (empty($hooks)) {
            $hooks = (array) Config::get('addons.hooks', []);
            // 初始化钩子
            foreach ($hooks as $key => $values) {
                if (is_string($values)) {
                    $values = explode(',', $values);
                } else {
                    $values = (array) $values;
                }
                $hooks[$key] = array_filter(array_map(function ($v) use ($key) {
                    return [get_addons_class($v), $key];
                }, $values));
            }
            Cache::set('hooks', $hooks);
        }
        //如果在插件中有定义 AddonsInit，则直接执行
        if (isset($hooks['AddonsInit'])) {
            foreach ($hooks['AddonsInit'] as $k => $v) {
                Event::trigger('AddonsInit', $v);
            }
        }
        Event::listenEvents($hooks);
    }

    /**
     * 挂载插件服务
     */
    private function loadService()
    {
        $results = scandir($this->addons_path);
        $bind = [];
        foreach ($results as $name) {
            if ($name === '.' or $name === '..') {
                continue;
            }
            if (is_file($this->addons_path . $name)) {
                continue;
            }
            $addonDir = $this->addons_path . $name . DIRECTORY_SEPARATOR;
            if (!is_dir($addonDir)) {
                continue;
            }

            if (!is_file($addonDir . ucfirst($name) . '.php')) {
                continue;
            }

            $service_file = $addonDir . 'Plugin.ini';
            if (!is_file($service_file)) {
                continue;
            }
            $info = parse_ini_file($service_file, true, INI_SCANNER_TYPED) ?: [];
            $bind = array_merge($bind, $info);
        }
        $this->app->bind($bind);
    }

    /**
     * 自动载入插件
     * @return bool
     */
    private function autoload()
    {
        // 是否处理自动载入
        if (!Config::get('addons.autoload', true)) {
            return true;
        }
        foreach (scandir($this->addons_path)  as $name){
            if ($name === '.' or $name === '..') {
                continue;
            }
            $addonDir = $this->addons_path . $name . DIRECTORY_SEPARATOR;
            if (is_dir($addonDir)) {
                $this->addons_name = $name;
            }
        }
        $basePath = $this->addons_path.$this->addons_name;
        $configPath = $this->app->getConfigPath();
        $files = [];
        if (is_dir($basePath . 'config')) {
            $files = array_merge($files, glob($basePath . 'config' . DIRECTORY_SEPARATOR . '*' . $this->app->getConfigExt()));
        } elseif (is_dir($configPath . $this->addons_name)) {
            $files = array_merge($files, glob($configPath .$this->addons_name . DIRECTORY_SEPARATOR . '*' . $this->app->getConfigExt()));
        }
        foreach ($files as $file) {
            $this->app->config->load($file, pathinfo($file, PATHINFO_FILENAME));
        }
        if (is_file($basePath . 'middleware.php')) {
            $this->app->middleware->import(include $basePath . 'middleware.php', 'app');
        }
        if (is_file($basePath . 'provider.php')) {
            $this->app->bind(include $basePath . 'provider.php');
        }
        $config = Config::get('addons');
        // 读取插件目录及钩子列表
        $base = get_class_methods("\\speed\\Addons");
        // 读取插件目录中的php文件
        foreach (glob($this->getAddonsPath() . '*/*.php') as $addons_file) {
            // 格式化路径信息
            $info = pathinfo($addons_file);
            // 获取插件目录名
            $name = pathinfo($info['dirname'], PATHINFO_FILENAME);
            // 找到插件入口文件
            if (strtolower($info['filename']) === 'plugin') {
                // 读取出所有公共方法
                $methods = (array)get_class_methods("\\addons\\" . $name . "\\" . $info['filename']);
                // 跟插件基类方法做比对，得到差异结果
                $hooks = array_diff($methods, $base);
                // 循环将钩子方法写入配置中
                foreach ($hooks as $hook) {
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
            }
        }
        Config::set($config, 'addons');
    }

    /**
     * 获取 addons 路径
     * @return string
     */
    public function getAddonsPath()
    {
        // 初始化插件目录
        $addons_path = $this->app->getRootPath() . 'addons' . DIRECTORY_SEPARATOR;
        // 如果插件目录不存在则创建
        if (!is_dir($addons_path)) {
            @mkdir($addons_path, 0755, true);
        }

        return $addons_path;
    }
   
    /**
     * 获取插件的配置信息
     * @param string $name
     * @return array
     */
    public function getAddonsConfig()
    {
        $name = $this->app->request->addon;
        $addon = get_addons_instance($name);
        if (!$addon) {
            return [];
        }

        return $addon->getConfig();
    }


    /**
     * 获取插件源资源文件夹
     * @param string $name 插件名称
     * @return  string
     */
    public static function getSourceAssetsDir($name)
    {
        return app()->getRootPath() . 'addons/' . $name . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
    }
    
    /**
     * 获取插件目标资源文件夹
     * @param string $name 插件名称
     * @return  string
     */
    public static function getDestAssetsDir($name)
    {
        $assetsDir = app()->getRootPath() . str_replace("/", DIRECTORY_SEPARATOR, "public/static/addons/{$name}");
        if (!is_dir($assetsDir)) {
            mkdir($assetsDir, 0755, true);
        }
        return $assetsDir;
    }


    //获取插件目录
    public static function getAddonsNamePath($name){

        return app()->getRootPath().'addons'.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR;
    }


    /**
     * 获取检测的全局文件夹目录
     * @return  array
     */
    public static function getCheckDirs()
    {
        return [
            'app',
        ];
    }

    /**
     * 获取插件在全局的文件
     * @param   int $onlyconflict 冲突
     * @param   string $name 插件名称
     * @return  array
     */
    public  static function getGlobalAddonsFiles($name, $onlyconflict = false)
    {
        $list = [];
        $addonDir = self::getAddonsNamePath($name);
        // 扫描插件目录是否有覆盖的文件
        foreach (self::getCheckDirs() as $k => $dir) {
            $checkDir = app()->getRootPath() . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR;
            if (!is_dir($checkDir))
                continue;
            //检测到存在插件外目录
            if (is_dir($addonDir . $dir)) {
                //匹配出所有的文件
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($addonDir . $dir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($files as $fileinfo) {
                    if ($fileinfo->isFile()) {
                        $filePath = $fileinfo->getPathName();
                        $path = str_replace($addonDir, '', $filePath);
                        if ($onlyconflict) {
                            $destPath = app()->getRootPath() . $path;
                            if (is_file($destPath)) {
                                if (filesize($filePath) != filesize($destPath) || md5_file($filePath) != md5_file($destPath)) {
                                    $list[] = $path;
                                }
                            }
                        } else {
                            $list[] = $path;
                        }
                    }
                }
            }
        }
        return $list;
    }




    //更新addons 文件；
    public static function updateAdddonsConfig(){
        $config = get_addons_autoload_config(true);
        if ($config['autoload'])
            return '';
        $file = app()->getRootPath() . 'config' . DIRECTORY_SEPARATOR . 'addons.php';
        if (!FileHelper::isWritable($file)) {
            throw new \Exception("addons.php文件没有写入权限");
        }

        if ($handle = fopen($file, 'w')) {
            fwrite($handle, "<?php\n\n" . "return " . var_export($config, TRUE) . ";");
            fclose($handle);
        } else {
            throw new Exception("文件没有写入权限");
        }
        return true;
    }
    //更新插件状态
    public static function updateAddonsInfo($name,$state=1){
        $addonslist  = get_addons_list();
        $addonslist[$name]['status'] =$state;
        Cache::set('addonslist',$addonslist);

    }
    
}
