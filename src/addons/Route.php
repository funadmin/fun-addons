<?php

declare(strict_types=1);

namespace speed\addons;

use think\helper\Str;
use think\facade\Event;
use think\facade\Config;
use think\exception\HttpException;

class Route
{
    /**
     * 插件路由请求
     * @param null $addon
     * @param null $controller
     * @param null $action
     * @return mixed
     */
    public static function execute($module=null,$addon = null, $controller = null, $action = null)
    {
        $app = app();
        $request = $app->request;
        // 注册插件公共中间件
        if (is_file($app->addons->getAddonsPath() . 'middleware.php')) {
            $app->middleware->import(include $app->addons->getAddonsPath() . 'middleware.php', 'route');
        }
        if (is_file($app->addons->getAddonsPath() . 'provider.php')) {
            $app->bind(include $basePath . 'provider.php');
        }
        $module_path  = $app->addons->getAddonsPath() . $addon . DIRECTORY_SEPARATOR .$module.DIRECTORY_SEPARATOR;
        //注册路由配置
        $addonsRouteConfig = [];
        if (is_file($module_path. 'config' . DIRECTORY_SEPARATOR . 'route.php')) {
            $addonsRouteConfig = include($module_path. 'config' . DIRECTORY_SEPARATOR . 'route.php');
            $app->config->load($module_path. 'config' . DIRECTORY_SEPARATOR . 'route.php', pathinfo($module_path. 'config' . DIRECTORY_SEPARATOR . 'route.php', PATHINFO_FILENAME));
        }
        if (isset($addonsRouteConfig['url_route_must']) && $addonsRouteConfig['url_route_must']) {
            throw new HttpException(400, lang("addon {$addon}：已开启强制路由"));
        }
        // 是否自动转换控制器和操作名
        $convert = $addonsRouteConfig['url_convert']??Config::get('route.url_convert');
        $filter = $convert ? 'strtolower' : 'trim';
        $addon = $addon ? trim(call_user_func($filter, $addon)) : '';
        $controller = $controller ? trim(call_user_func($filter, $controller)) :$app->route->config('default_action');
        $action = $action ? trim(call_user_func($filter, $action)) : $app->route->config('default_action');

        Event::trigger('addons_begin', $request);
        if (empty($addon) || empty($controller) || empty($action)) {
            throw new HttpException(500, lang('addon can not be empty'));
        }
        $request->addon = $addon;
        // 设置当前请求的控制器、操作
        $request->setController("{$module}.{$controller}")->setAction($action);
        // 获取插件基础信息
        $info = get_addons_info($addon);
        if (!$info) {
            throw new HttpException(404, lang('addon %s not found', [$addon]));
        }
        if (!$info['status']) {
            throw new HttpException(500, lang('addon %s is disabled', [$addon]));
        }
        // 监听addon_module_init
        Event::trigger('addon_module_init', $request);
        $class = get_addons_class($addon, 'controller', $controller,$module);
        if (!$class) {
            throw new HttpException(404, lang('addon controller %s not found', [Str::studly($module.DIRECTORY_SEPARATOR.$controller)]));
        }
        // 重写视图基础路径
        $config = Config::get('view');
        $config['view_path'] = $app->addons->getAddonsPath() . $addon . DIRECTORY_SEPARATOR.$module .DIRECTORY_SEPARATOR. 'view' . DIRECTORY_SEPARATOR;
        Config::set($config, 'view');
        // 生成控制器对象
        $instance = new $class($app);
        $vars = [];
        if (is_callable([$instance, $action])) {
            // 执行操作方法
            $call = [$instance, $action];
        } elseif (is_callable([$instance, '_empty'])) {
            // 空操作
            $call = [$instance, '_empty'];
            $vars = [$action];
        } else {
            // 操作不存在
            throw new HttpException(404, lang('addon action %s not found', [get_class($instance).'->'.$action.'()']));
        }
        Event::trigger('addons_action_begin', $call);

        return call_user_func_array($call, $vars);
    }
}