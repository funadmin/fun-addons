<?php

namespace speed\addons;

use think\App;
use think\facade\Lang;
use think\facade\View;
use think\facade\Config;
use app\common\controller\Base;

/**
 * 插件基类控制器.
 */
class Controller extends Base
{
    // 当前插件操作
    protected $addon = null;
    //插件路径
    protected $addon_path = null;
    protected $controller = null;
    protected $action = null;
    // 当前template
    protected $template;
    protected $view;

    /**
     * 无需登录的方法,同时也就不需要鉴权了.
     *
     * @var array
     */
    protected $noNeedLogin = ['*'];

    /**
     * 无需鉴权的方法,但需要登录.
     *
     * @var array
     */
    protected $noNeedRight = ['*'];


    /**
     * 布局模板
     *
     * @var string
     */
    protected $layout = null;

    /**
     * 架构函数.
     */
    public function __construct(App $app)
    {
        //移除HTML标签
        app()->request->filter('trim,strip_tags,htmlspecialchars');
        // 是否自动转换控制器和操作名
        $convert = Config::get('url_convert');

        $filter = $convert ? 'strtolower' : 'trim';
        // 处理路由参数
        $route = app()->request->rule()->getRoute();
        $var = explode('\\',$route);
        $addon = isset($var['1']) ? $var['1'] : '';
        $controller = isset($var['3']) ? $var['3'] : '';
        $action = isset($var['4']) ? $var['4'] : '';
        $this->addon = $addon ? call_user_func($filter, $addon) : '';
        $this->addon_path = $app->addons->getAddonsPath() . $this->addon . DIRECTORY_SEPARATOR;
        $this->controller = $controller ? call_user_func($filter, $controller) : 'index';
        $this->action = $action ? call_user_func($filter, $action) : 'index';
        // 父类的调用必须放在设置模板路径之后
        $this->_initialize();
        var_dump($this->controller);
        parent::__construct($app);

    }

    protected function _initialize()
    {

        // 重置模板引擎配置
        $this->view = clone View::engine('Think');
        $this->view->config(['view_path' =>  $this->addon_path .'view' .DIRECTORY_SEPARATOR. $this->controller.DIRECTORY_SEPARATOR]);
        // 渲染配置到视图中
        $config = get_addons_config($this->addon);
        $this->view->assign(['config'=>$config]);
        // 加载系统语言包
        Lang::load([
            $this->addon_path . 'lang' . DIRECTORY_SEPARATOR . Lang::getLangset() . '.php',
        ]);
        // 如果有使用模板布局
        if ($this->layout) {
            $this->view->layout($this->controller.DIRECTORY_SEPARATOR.  trim($this->layout,'/'));
        }

        parent::initialize();



    }



    /**
     * 加载模板输出
     * @param string $template
     * @param array $vars           模板文件名
     * @return false|mixed|string   模板输出变量
     * @throws \think\Exception
     */
    protected function fetch($template = '', $vars = [])
    {
        return $this->view->fetch($template, $vars);
    }

    /**
     * 渲染内容输出
     * @access protected
     * @param  string $content 模板内容
     * @param  array  $vars    模板输出变量
     * @return mixed
     */
    protected function display($content = '', $vars = [])
    {
        return $this->view->display($content, $vars);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param  mixed $name  要显示的模板变量
     * @param  mixed $value 变量的值
     * @return $this
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign([$name => $value]);

        return $this;
    }

    /**
     * 初始化模板引擎
     * @access protected
     * @param  array|string $engine 引擎参数
     * @return $this
     */
    protected function engine($engine)
    {
        $this->view->engine($engine);

        return $this;
    }


}
