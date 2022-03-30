<?php
/**
 * FunAdmin
 * ============================================================================
 * 版权所有 2017-2028 FunAdmin，并保留所有权利。
 * 网站地址: http://www.FunAdmin.com
 * ----------------------------------------------------------------------------
 * 采用最新Thinkphp6实现
 * ============================================================================
 * Author: yuege
 * Date: 2017/8/2
 */

namespace fun\curd\service;

use app\backend\model\AuthRule;
use think\App;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\helper\Str;

class CurdService
{
    protected $config = [
        'keepField' => ['admin_id', 'member_id'],//保留字段
        'fields' => [],//显示的字段
        'ignoreFields' => ['create_time', 'status', 'update_time', 'delete_time'],//忽略字段
        'tagsSuffix' => ['tags', 'tag'],//识别为tag类型
        'fileSuffix' => ['file', 'files', 'path', 'paths'],//识别为文件字段
        'priSuffix' => ['_id','_ids'],//识别为别的表的主键
        'sortSuffix' => ['sort'],//排序
        'imageSuffix' => ['image', 'images', 'thumb', 'thumbs', 'avatar', 'avatars'],//识别为图片字段
        'editorSuffix' => ['editor', 'content', 'detail', 'details', 'description'],//识别为编辑器字段
        'iconSuffix' => ['icon'],//识别为图标字段
        'colorSuffix' => ['color'],//颜色
        'jsonSuffix' => ['json'],//识别为json字段
        'timeSuffix' => ['time', 'date', 'datetime'],//识别为日期时间字段
        'checkboxSuffix' => ['checkbox'],//多选
        'selectSuffix' => ['select', 'selects'],//下拉框
        'switchSuffix' => ['switch'],//开关
        'enumRadioSuffix' => ['data', 'state', 'status', 'radio'],//开关
        'setCheckboxSuffix' => ['data', 'state', 'status',],//多选
    ];
    /**
     * /**
     * 表前缀
     * @var string
     */
    protected $tablePrefix = 'fun_';
    /**
     * 数据库名
     * @var string
     */
    protected $database = 'funadmin';
    protected $driver = 'mysql';
    protected $force = false;
    protected $jump = true;//跳过文件
    protected $rootPath;
    protected $tplPath;//模板路径
    protected $method = 'index,add,edit,destroy,delete,recycle,import,export,modify,restore';
    protected $fileList;
    protected $fieldsList;
    protected $table;
    protected $addon;
    protected $module;
    protected $nodeType='__u';
    protected $baseController;
    protected $tableComment;
    protected $controllerNamespace;
    protected $controllerName;
    protected $modelName;
    protected $modelNamespace;
    protected $controllerUrl;
    protected $modelTableName;
    protected $childMethod;
    protected $validateName;
    protected $validateNamespace;
    protected $joinMethod;
    protected $joinName;
    protected $joinModel;
    protected $joinTable;
    protected $joinForeignKey;
    protected $joinPrimaryKey;
    protected $selectFields;
    protected $jsCols;
    protected $jsColsRecycle;
    protected $assign;
    protected $script;
    protected $requests;
    protected $requestsRecycle;
    protected $limit;
    protected $page = "true";
    protected $controllerArr;
    protected $modelArr;
    protected $menuListStr;
    protected $softDelete;

    public function __construct(array $config)
    {
        $this->setParam($config);
        $this->tablePrefix = config('database.connections.'.$config['driver'].'.prefix');
        $this->database = Config::get('database.connections' . '.' .$config['driver'] . '.database');
        $this->driver = $config['driver'];
        $this->dir = __DIR__;
        $this->rootPath = root_path();
        $this->tplPath = $this->rootPath . 'vendor' . DS . 'funadmin'. DS . 'fun-addons' . DS . 'src' . DS . 'curd'  . DS . 'tpl' . DS ;
        return $this;
    }
    /**
     * 获取配置
     * @return \string[][]
     */
    public function getParam()
    {
        return $this->config;
    }
    /**
     * 设置配置
     * @param $config
     */
    public function setParam($config)
    {
        $res = array();
        foreach ($this->config as $k => $v) {
            if (isset($config[$k])) {
                $config[$k] = $config[$k] ?
                    (is_array($config[$k]) ? $config[$k] : explode(',', $config[$k])) : [];
                $res[$k] = array_merge($this->config[$k], $config[$k]);
                unset($this->config[$k], $config[$k]);
            }
        }
//        foreach ($config as $k=>&$v){
//
//            if(!empty($v)  && is_array($v) && strpos($v[0],',')!==false) {
////                $v = explode(',',$v[0]);
//            }
//        }
//        unset($v);
        $this->config = array_merge($res, $this->config, $config);
        $this->setArg();
    }
    /**
     * 设置基础参数
     */
    protected function setArg()
    {
        $this->table = $this->config['table'];
        $this->table = str_replace($this->tablePrefix, '', $this->table);
        $this->addon = isset($this->config['addon']) && $this->config['addon'] ? $this->config['addon'] : '';
        $this->nodeType = $this->addon?'addons_url':'__u';
        $this->module = $this->config['module'] ?: 'backend';
        $this->force = $this->config['force'];
        $this->jump = $this->config['jump'];
        $this->limit = $this->config['limit'] ?:15;
        $this->page = (empty($this->config['page']) || $this->config['page']=='true')? "true" : 'false';
        $this->joinTable = $this->config['joinTable'] ;
        foreach ($this->joinTable as $k=>$v){
            $this->joinTable[$k] = str_replace($this->tablePrefix,'',$v);
        }
        $this->joinName = $this->config['joinName']?:$this->joinTable;
        $this->joinModel = $this->config['joinModel']?:$this->joinTable ;
        $this->joinMethod = $this->config['joinMethod'];
        $this->joinForeignKey = $this->config['joinForeignKey'] ;
        if(count( $this->joinForeignKey)==1 && strpos($this->joinForeignKey[0],',')){
            $this->joinForeignKey = array_filter(explode(',',( $this->joinForeignKey[0])));
        }
        $this->joinPrimaryKey = $this->config['joinPrimaryKey'] ;
        if(count( $this->joinPrimaryKey)==1 && strpos($this->joinPrimaryKey[0],',')){
            $this->joinPrimaryKey = array_filter(explode(',',( $this->joinPrimaryKey[0])));
        }
        $this->selectFields = $this->config['selectFields'];
        $controllerStr = $this->config['controller'] ?: Str::studly($this->table);
        $controllerArr = explode('/', $controllerStr);
        foreach ($controllerArr as $k => &$v) {
            $v = ucfirst(Str::studly($v));
        }
        unset($v);
        $this->controllerName = array_pop($controllerArr);
        $this->controllerArr = $controllerArr;
        $modelStr = $this->config['model'] ?: Str::studly($this->table);
        $modelArr = explode('/', $modelStr);
        foreach ($modelArr as $k => &$v) {
            $v = ucfirst(Str::studly($v));
        }
        unset($v);
        $this->modelName = array_pop($modelArr);
        $modelArr?$modelArr[0] = Str::lower($modelArr[0]):'';
        $this->modelArr = $modelArr;
        $this->validateName = $this->config['validate'] ?: $this->modelName;
        $this->validateName = Str::studly($this->validateName);
        $this->controllerUrl = $controllerArr ? Str::lower($controllerArr[0]) . '.' . Str::camel($this->controllerName) : Str::camel($this->controllerName);
        if (isset($this->config['method']) and $this->config['method']) {
            $this->method = $this->config['method'];
        }
        $methodArr = explode(',', $this->method);
        foreach ($methodArr as $k => $v) {
            if ($v != 'refresh') {
                $space = $k==0?'':'                    ';
                if(!in_array($v,['restore'])) {
                    $controllerPrefix  = $this->addon?"addons/$this->addon/". ($this->module=='common'?'backend':$this->module) ."/":"";
                    $space = $k==0?'':'                    ';
                    $this->requests .=  $space. $v . '_url:' ."'{$controllerPrefix}{$this->controllerUrl}/{$v}'" . ','.PHP_EOL;
                }
                if(in_array($v,['recycle','restore','delete'])){
                    $this->requestsRecycle .= $v . '_url:' ."'{$controllerPrefix}{$this->controllerUrl}/{$v}'" . ','.PHP_EOL.$space;
                }
            }
        }
        $nameSpace = $controllerArr ? '\\' . Str::lower($controllerArr[0]) : "";
        //普通模式
        if (!$this->addon) {
            $this->controllerNamespace = 'app\\'.$this->module.'\\controller' . $nameSpace;
            $this->baseController = '\\app\\common\\controller\\'.ucfirst($this->module);
            $this->modelNamespace = "app\\{$this->module}\\model".($modelArr?'\\'.$modelArr[0]:'');
            $this->validateNamespace = "app\\{$this->module}\\validate".($modelArr?'\\'.$modelArr[0]:'');
            $this->fileList = [
                'controllerFileName' =>
                    $this->rootPath . "app" . DS . "$this->module" . DS . "controller" . DS . ($controllerArr ? Str::lower($controllerArr[0]) . DS . $this->controllerName . '.php' : $this->controllerName . '.php'),
                'modelFileName' =>
                    $this->rootPath . "app" . DS . $this->module . DS . "model" . DS .($modelArr?$modelArr[0].DS :''). ($this->modelName) . '.php',
                'validateFileName' =>
                    $this->rootPath . "app" . DS . $this->module . DS . "validate" . DS .($modelArr?$modelArr[0].DS :''). ($this->modelName) . '.php',
                'langFileName' =>
                    $this->rootPath . "app" . DS . $this->module . DS . "lang" . DS . "zh-cn" . DS . ($controllerArr ? Str::lower($controllerArr[0]) . DS . Str::lower($this->controllerName) . '.php' : Str::lower($this->controllerName) . '.php'),
                'jsFileName' =>
                    $this->rootPath . "public" . DS . "static" . DS . "$this->module" . DS . "js" . DS . ($controllerArr ? Str::lower($controllerArr[0]) . DS . Str::lower($this->controllerName) . '.js' : Str::lower($this->controllerName) . '.js'),

                'indexFileName' =>
                    $this->rootPath . "app" . DS . "$this->module" . DS . "view" . DS . ($controllerArr ? Str::lower($controllerArr[0]) . DS . Str::snake($this->controllerName) : Str::snake($this->controllerName)) . DS . "index.html",
                'addFileName' =>
                    $this->rootPath . "app" . DS . "$this->module" . DS . "view" . DS . ($controllerArr ? Str::lower($controllerArr[0]) . DS . Str::snake($this->controllerName) : Str::snake($this->controllerName)) . DS . 'add.html',
            ];
        } else {
            //插件模式
            $this->controllerNamespace = "addons\\{$this->addon}\\$this->module\\controller" . $nameSpace;
            $this->baseController = '\\app\\common\\controller\\AddonsBackend';
            //默认没有二级目录
            $this->modelNamespace = "addons\\{$this->addon}\\{$this->module}\\model";
            $this->validateNamespace = "addons\\{$this->addon}\\{$this->module}\\validate";
            $this->fileList = [
                'controllerFileName' => $this->rootPath . "addons" . DS . "{$this->addon}" . DS . "$this->module" . DS . "controller" . DS . ($controllerArr ? Str::lower($controllerArr[0]) . DS . $this->controllerName . '.php' : $this->controllerName . '.php'),
                'controllerFrontFileName' => $this->rootPath . "addons" . DS . "{$this->addon}" . DS . "frontend" . DS . "controller" . DS . ($controllerArr ? Str::lower($controllerArr[0]) . DS . $this->controllerName . '.php' : $this->controllerName . '.php'),
                'modelFileName' => $this->rootPath . "addons" . DS . "{$this->addon}" . DS . "{$this->module}" . DS . "model" . DS . $this->modelName . '.php',
                'validateFileName' => $this->rootPath . "addons" . DS . "{$this->addon}" . DS . "{$this->module}" . DS . "validate" . DS . $this->modelName . '.php',
                'langFileName' => $this->rootPath . "addons" . DS . "{$this->addon}" . DS . "{$this->module}".DS. "lang" . DS . "zh-cn" . DS . ($controllerArr ? Str::lower($controllerArr[0]) . DS . Str::lower($this->controllerName) . '.php' : Str::lower($this->controllerName) . '.php'),
                'jsFileName' => $this->rootPath . "addons" . DS . "{$this->addon}" . DS . "public".DS."$this->module".DS."js" . DS . ($controllerArr ? Str::lower($controllerArr[0]) . DS . Str::lower($this->controllerName) . '.js' : Str::lower($this->controllerName) . '.js'),
                'indexFileName' => $this->rootPath . "addons" . DS . "{$this->addon}" . DS . "view" . DS . "$this->module" . DS .($controllerArr ? Str::lower($controllerArr[0]) . DS . $this->controllerName : Str::snake($this->controllerName)). DS . "index.html",
                'addFileName' => $this->rootPath . "addons" . DS . "{$this->addon}" . DS . "view" . DS . "$this->module". DS .($controllerArr ? Str::lower($controllerArr[0]) . DS . $this->controllerName  : Str::snake($this->controllerName)). DS . "add.html",
                'pluginFileName' => $this->rootPath . "addons" . DS . "{$this->addon}" . DS . "Plugin.php",
                'pluginIniFileName' => $this->rootPath . "addons" . DS . "{$this->addon}" . DS . "Plugin.ini",
                'pluginConfigFileName' => $this->rootPath . "addons" . DS . "{$this->addon}" . DS . "config.php",
            ];
        }
        return $this;
    }
    /**
     *
     */
    public function maker()
    {
        $this->getFieldList();
        if (!$this->config['delete']) {
            $this->makeModel();
            $this->makeController();
            $this->makeJs();
            $this->makeView();
            $this->makeMenu(1);
            $this->makeAddon();
        } elseif ($this->config['force'] and $this->config['delete']) {
            foreach ($this->fileList as $k => $v) {
                @unlink($v);
            }
            $this->makeMenu(2);
        }
        return $this;
    }

    // 创建控制器文件
    protected function makeController()
    {
        $controllerTpl = $this->tplPath . 'controller.tpl';
        $modelTpl =  $this->tplPath . 'model.tpl';
        $indexTpl = '';
        $recycleTpl = '';
        $relationSearch = '';
        $statusResult = Db::connect($this->driver)->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_name ='".$this->tablePrefix.$this->table."' AND column_name ='status'");
        $status= $statusResult[0]['COUNT(*)'];
        if ($this->joinTable) {
            $relationSearch ='$this->relationSearch = true;';
            $joinIndexMethod = "withJoin([";
            foreach ($this->joinTable as $k => $v) {
                $joinName  = lcfirst(Str::studly($this->joinName[$k]));
                $joinIndexMethod .= "'{$joinName}'" . ',';
                if(!$this->addon){
                    $joinModelFile = $this->rootPath . "app" . DS . $this->module . DS . "model" . DS .($this->modelArr?$this->modelArr[0].DS :''). ucfirst(Str::studly($this->joinTable[$k])) . '.php';
                }else{
                    $joinModelFile = $this->rootPath . "addons" . DS . "{$this->addon}" . DS . "{$this->module}" . DS . "model" . DS . ucfirst(Str::studly($this->joinTable[$k])) . '.php';
                }
                $softDelete = '';
                //判断是否有删除字段
                $sql = "select COLUMN_NAME as name, COLUMN_DEFAULT as value from information_schema.columns where table_name = '" . $this->tablePrefix . $v . "' and table_schema = '" . $this->database . "' and column_name = 'delete_time'";
                $delete = Db::connect($this->driver)->query($sql);
                if(!empty($delete)){
                    $softDelete = $this->getSoftDelete($delete[0]);
                }
                //生成关联表的模型
                $connection = $this->driver=='mysql'?"":"protected \$connection = '".$this->driver."';";
                $modelTplTemp = str_replace([
                    '{{$modelNamespace}}',
                    '{{$modelName}}',
                    '{{$modelTableName}}',
                    '{{$softDelete}}',
                    '{{$connection}}',
                    '{{$joinTpl}}',
                    '{{$attrTpl}}',
                ],
                    [
                        $this->modelNamespace,
                        ucfirst(Str::studly($this->joinName[$k])),
                        $this->joinName[$k],
                        $softDelete,
                        $connection,
                        '',
                        '',
                    ],
                    file_get_contents($modelTpl));
                $this->makeFile($joinModelFile,$modelTplTemp);
            }
            $joinIndexMethod = substr($joinIndexMethod,0,strlen($joinIndexMethod)-1);
            $joinIndexMethod.="])";
            $joinIndexMethod = trim($joinIndexMethod, ',');
            $indexTpl = $this->tplPath . 'index.tpl';
            $indexTpl = str_replace(
                [
                    '{{$joinIndexMethod}}',
                    '{{$relationSearch}}',
                    '{{$table}}',
                    '{{$status}}',
                ],
                [$joinIndexMethod,$relationSearch,$this->table.'.',$status], file_get_contents($indexTpl));
            if($this->softDelete){
                $recycleTpl = $this->tplPath . 'indexrecycle.tpl';
                $recycleTpl = str_replace(
                    [
                        '{{$joinIndexMethod}}',
                        '{{$relationSearch}}',
                        '{{$table}}',
                        '{{$status}}',
                    ],
                    [$joinIndexMethod,$relationSearch,$this->table.'.',$status], file_get_contents($recycleTpl));
            }
       
        }
        $assignTpl = file_get_contents($this->tplPath . 'assign.tpl');
        $scriptTpl = file_get_contents($this->tplPath . 'script.tpl');
        $assignStr = '';
        $scriptStr = '<script>';
        foreach ($this->assign as $k => $v) {
            $kk = Str::studly($k);
            if(!$this->hasSuffix($k,$this->config['priSuffix'])){
                $assignStr .= str_replace(['{{$name}}','{{$method}}'],[lcfirst($kk),'get'.$kk],$assignTpl).PHP_EOL;
                $scriptStr .= str_replace(['{{$name}}','{{$method}}'],[lcfirst($kk),lcfirst($kk)],$scriptTpl).PHP_EOL;
            }elseif($this->hasSuffix($k,$this->config['priSuffix'])
                and $this->joinTable
                and in_array(substr($k,0,strlen($k)-4),$this->joinForeignKey)
            ){
                $assignStr .= str_replace(['{{$name}}','{{$method}}'],[lcfirst($kk),'get'.$kk],$assignTpl).PHP_EOL;
                $scriptStr .= str_replace(['{{$name}}','{{$method}}'],[lcfirst($kk),lcfirst($kk)],$scriptTpl).PHP_EOL;
            }
        }
        $scriptStr.='</script>';
        $this->script = $scriptStr;
        $this->tableComment = $this->tableComment?:$this->controllerName;
        $controllerTplBack = str_replace(
            [
                '{{$controllerNamespace}}',
                '{{$controllerName}}',
                '{{$baseController}}',
                '{{$tableComment}}',
                '{{$modelName}}',
                '{{$modelNamespace}}',
                '{{$assign}}',
                '{{$indexTpl}}',
                '{{$recycleTpl}}',
                '{{$limit}}'],
            [
                $this->controllerNamespace,
                $this->controllerName,
                $this->baseController,
                $this->tableComment,
                $this->modelName,
                $this->modelNamespace,
                $assignStr,
                $indexTpl,
                $recycleTpl,
                $this->limit
            ],
            file_get_contents($controllerTpl));
        $this->makeFile($this->fileList['controllerFileName'],$controllerTplBack);
        if($this->addon){
            $controllerTplFront = str_replace(
                [
                    '{{$controllerNamespace}}',
                    '{{$controllerName}}',
                    '{{$baseController}}',
                    '{{$tableComment}}',
                    '{{$modelName}}',
                    '{{$modelNamespace}}',
                    '{{$assign}}',
                    '{{$indexTpl}}',
                    '{{$recycleTpl}}',
                    '{{$limit}}'],
                [
                    str_replace('backend', $this->module, $this->controllerNamespace),
                    $this->controllerName,
                    '\\app\\common\\controller\\AddonsFrontend',
                    $this->tableComment,
                    $this->modelName,
                    $this->modelNamespace,
                    $assignStr,
                    $indexTpl,
                    $recycleTpl,
                    $this->limit,
                ],
                file_get_contents($controllerTpl));
            $this->makeFile(
                str_replace(
                    'backend',
                    $this->module=='backend'?"frontend":$this->module,
                    $this->fileList['controllerFileName'])
                , $controllerTplFront
            );
        }
        //语言文件
        $langTpl = $this->tplPath . 'lang.tpl';
        $langTpl = str_replace(
            [
                '{{$lang}}',
            ],
            [
                $this->lang,
            ],
            file_get_contents($langTpl));
        $this->makeFile(
            $this->fileList['langFileName'],
            $langTpl
        );

    }
    // 创建模型文件
    protected function makeModel()
    {
        $modelTpl = $this->tplPath . 'model.tpl';
        $validateTpl = $this->tplPath . 'validate.tpl';
        $attrTpl = $this->tplPath . 'attr.tpl';
        $joinAttrTpl = $this->tplPath . 'joinAttr.tpl';
        //单模型
        $joinTplStr = '';
        if ($this->joinTable) {
            foreach ($this->joinTable as $k=>$v){
                $method = 'hasOne';
                if(isset($this->joinMethod[$k])) $method = $this->joinMethod[$k];
                if ($method == 'hasOne') {
                    list($joinPrimaryKey,$joinForeignKey) = array($this->joinForeignKey[$k],$this->joinPrimaryKey[$k]);
                } else {
                    list($joinPrimaryKey,$joinForeignKey) = array($this->joinPrimaryKey[$k],$this->joinForeignKey[$k]);
                }
                $joinTpl = $this->tplPath . 'join.tpl';
                $joinTplStr .= str_replace([
                    '{{$joinName}}',
                        '{{$joinMethod}}',
                        '{{$joinModel}}',
                        '{{$joinForeignKey}}',
                        '{{$joinPrimaryKey}}'],
                        [
                            lcfirst(Str::studly($v)),
                            $method,
                            ucfirst(Str::studly($this->joinModel[$k])),
                            $joinForeignKey,
                            $joinPrimaryKey],
                        file_get_contents($joinTpl)).PHP_EOL;
            }
        }
        //变量分配
        $i=0;
        if($this->assign){
            foreach ($this->assign as $k=>$v){
                $kk = Str::studly($k);
                if(!$this->hasSuffix($k,$this->config['priSuffix'])){
                    $joinTplStr.=str_replace(['{{$method}}','{{$values}}'],
                            ['get'.$kk,$v],
                            file_get_contents($attrTpl)).PHP_EOL;
                }elseif($this->hasSuffix($k,$this->config['priSuffix'])
                    and $this->joinTable   and $this->joinTable
                    and in_array(substr($k,0,strlen($k)-4),$this->joinForeignKey)
                ){
                    //关联模型搜索属性
                    $model = isset($this->joinModel[$i])?$this->joinModel[$i]:$this->joinModel[0];
                    if(count($this->joinTable)==1){
                        $value = isset($this->selectFields[0])?$this->selectFields[0]:'title';
                    }else{
                        $value = isset($this->selectFields[$i])?$this->selectFields[$i]:'title';
                    }
                    $k = str_replace(['_id','_ids'],['',''],$k);
                    $joinTplStr.=str_replace(['{{$method}}','{{$values}}','{{$joinModel}}'],
                            ['get'.ucfirst($kk),$value,ucfirst(Str::studly($model))],
                            file_get_contents($joinAttrTpl)).PHP_EOL;
                    $i++;
                }
            }
        }
        $attrStr = $this->modifyAttr();
        $connection = $this->driver=='mysql'?"":"protected \$connection = '".$this->driver."';";
        $modelTpl = str_replace([
            '{{$modelNamespace}}',
            '{{$modelName}}',
            '{{$modelTableName}}',
            '{{$joinTpl}}',
            '{{$attrTpl}}',
            '{{$softDelete}}',
            '{{$connection}}',
        ],
            [$this->modelNamespace,
                ucfirst($this->modelName),
                $this->modelTableName,
                $joinTplStr,
                $attrStr,
                $this->softDelete,
                $connection,
            ],
            file_get_contents($modelTpl));
        $validateTpl = str_replace(
            ['{{$validateNamespace}}', '{{$validateName}}'],
            [$this->validateNamespace, $this->validateName], file_get_contents($validateTpl));
        $this->makeFile($this->fileList['modelFileName'], $modelTpl);
        $this->makeFile($this->fileList['validateFileName'], $validateTpl);
    }

    // 创建模板
    protected function makeView()
    {
        $formFieldData = $this->getFormData();
        $indexViewTpl = $this->tplPath .'view' . DS . 'index.tpl';
        $indexViewTpl = str_replace(['{{$nodeType}}','{{$script}}'],[$this->nodeType,$this->script], file_get_contents($indexViewTpl));
        $addViewTpl = $this->tplPath . 'view' . DS . 'add.tpl';
        $addViewTpl = str_replace(['{{$formDataField}}'], [$formFieldData], file_get_contents($addViewTpl));
        $this->makeFile($this->fileList['indexFileName'], $indexViewTpl);
        $this->makeFile($this->fileList['addFileName'], $addViewTpl);
    }

    //生成js
    protected function makeJs()
    {
        $this->getCols();
        $jsTpl = $this->tplPath . 'js.tpl';
        $jsrecycleTpl = '';
        if($this->softDelete){
            $jsrecycleTpl = $this->tplPath . 'jsrecycle.tpl';
            $jsrecycleTpl = str_replace(['{{$requestsRecycle}}','{{$jsColsRecycle}}',
                '{{$limit}}', '{{$page}}'
            ],
                [$this->requestsRecycle,$this->jsColsRecycle, $this->limit, $this->page,
                ],
                file_get_contents($jsrecycleTpl));
        }
        $jsTpl = str_replace(['{{$requests}}', '{{$jsCols}}',
            '{{$limit}}', '{{$page}}','{{$jsrecycleTpl}}'
        ],
            [$this->requests, $this->jsCols, $this->limit, $this->page,
            $jsrecycleTpl,
        ],
            file_get_contents($jsTpl));
        $this->makeFile($this->fileList['jsFileName'], $jsTpl);
    }

    /**
     * 生成插件文件
     * @throws \Exception
     */
    protected function makeAddon()
    {
        if ($this->addon and (!$this->fileList['pluginFileName'] || $this->force)){
            $configTpl = $this->tplPath . 'addon' . DS . 'config.tpl';
            $iniTpl = $this->tplPath . 'addon' . DS . 'ini.tpl';
            $pluginTpl = $this->tplPath . 'addon' . DS . 'plugin.tpl';
            $iniTpl = str_replace(
                ['{{$addon}}'],
                [Str::lower($this->addon)], file_get_contents($iniTpl));
            $pluginTpl = str_replace(
                ['{{$addon}}','{{$menu}}'],
                [Str::lower($this->addon),$this->menuListStr],
                file_get_contents($pluginTpl));
            $this->makeFile($this->fileList['pluginConfigFileName'], file_get_contents($configTpl));
            $this->makeFile($this->fileList['pluginIniFileName'], $iniTpl);
            $this->makeFile($this->fileList['pluginFileName'], $pluginTpl);
        }
    }

    /**
     * 
     * 生成菜单
     * @param int $type
     */
    protected function makeMenu(int $type=1)
    {
        if($this->module!='backend'){
            $this->menuListStr = '[]';
            return true;
        }
        $title  =  $this->addon?'addons/'.$this->addon.ucfirst($this->controllerName):($this->controllerArr ? strtolower($this->controllerArr[0]) . ucfirst($this->controllerName) : lcfirst($this->controllerName));
        $title = $this->tableComment?$this->tableComment:$title;
        $childMenu =  [
            'href' => $this->addon?'addons/'.$this->addon.'/backend/'.lcfirst($this->controllerName):($this->controllerArr ? strtolower($this->controllerArr[0]) . '.' . lcfirst($this->controllerName) : lcfirst($this->controllerName)),
            'title' => $title,
            'status' => 1,
            'menu_status' => 1,
            'type' => 1,
            'icon' => 'layui-icon layui-icon-app',
            'menulist' => [
            ]
        ];
        $menu = [
            'is_nav' => 1,//1导航栏；0 非导航栏
            'menu' => [ //菜单;
                'href' => $this->addon?$this->addon:$this->controllerName,
                'title' =>$this->addon?$this->addon:$this->controllerName,
                'status' => 1,
                'auth_verify' => 1,
                'type' => 1,
                'menu_status' => 1,
                'icon' => 'layui-icon layui-icon-app',
                'menulist' => [
                    $childMenu
                ]
            ]
        ];
        $plugins =$this->addon? get_addons_instance($this->addon):'';
        if($plugins){
            $menu = $plugins->menu;
        }
        if(!$this->softDelete){
            $this->method =  'index,add,edit,delete,import,export,modify';
        }
        foreach (explode(',', $this->method) as $k => $v) {
            if ($v == 'refresh') continue;
            if ($this->addon) {
                $menuList[] = [
                    'href'=>'addons/'.$this->addon.'/backend/' . lcfirst($this->controllerName . '/' . $v),
                    'title'=>$v,
                    'status'=>1,
                    'menu_status'=>0,
                    'icon'=>'layui-icon layui-icon-app'
                ];
                $childMethod[] = 'addons/'.$this->addon.'/backend/' . lcfirst($this->controllerName . '/' . $v);
            } else {
                $menuList[] = [
                    'href'=>($this->controllerArr ? strtolower($this->controllerArr[0]) . '.' . lcfirst($this->controllerName) : lcfirst($this->controllerName)) . '/' . $v,
                    'title'=>$v,
                    'status'=>1,
                    'menu_status'=>0,
                    'icon'=>'layui-icon layui-icon-app'
                ];
                $childMethod[] =($this->controllerArr ? strtolower($this->controllerArr[0]) . '.' . lcfirst($this->controllerName) : lcfirst($this->controllerName)) . '/' . $v;
            }
        }
        $parentMethod = $this->addon?'addons/'.$this->addon.'/backend/'.lcfirst($this->controllerName):($this->controllerArr ? strtolower($this->controllerArr[0]) . '.' . lcfirst($this->controllerName) : lcfirst($this->controllerName));
        $this->childMethod  = array_merge($childMethod,[$parentMethod]);
        if($plugins){
            $childMenu['menulist'] = $menuList;
            array_push($menu['menu']['menulist'],$childMenu);
            $menu['menu']['menulist'] = array_unique($menu['menu']['menulist'],SORT_REGULAR);//去重
        }else{
            $menu['menu']['menulist'][0]['menulist'] = $menuList;
        }
        $menuListArr[] = $menu['menu'];
        $this->menuListStr = $this->getMenuStr($menu);
        if(!$this->addon && $this->config['menu']){
            $this->operateMenu($menuListArr,$type);
        }
    }
    /**
     * 生成文件
     */
    public function makeFile($filename, $content)
    {
        if (is_file($filename) && !$this->force && !$this->jump) {
            throw new \Exception($filename.'文件已经存在');
        }
        if (!is_dir(dirname($filename))) {
            @mkdir(dirname($filename), 0755, true);
        }
        file_put_contents($filename, $content);
    }

    protected function operateMenu($menuListArr,$type=1){
        $module= $this->addon?'addon':'backend';
        foreach ($menuListArr as $k=>$v){
            $v['pid'] = 0 ;
            $v['href'] = trim($v['href'],'/');
            $v['module'] =$module;
            $menu = AuthRule::withTrashed()->where('href',$v['href'])->where('module',$module)->find();
            if($type==1){
                if(!$menu){
                    $menu = AuthRule::create($v);
                }else{
                    $menu->restore();
                }
            }else{
                $child = AuthRule::withTrashed()->where('href','not in',$this->childMethod)
                    ->where('pid',$menu['id'])->where('module',$module)->find();
                if(!$child){
                    $menu && $menu->delete();
                }
            }
            foreach ($v['menulist'] as $kk=>$vv){
                $menu2 = AuthRule::withTrashed()->where('href',$vv['href'])->where('module',$module)->find();
                if($type==1){
                    if(!$menu2){
                        $vv['pid'] = $menu['id'];
                        $vv['module'] = $module;
                        $menu2 = AuthRule::create($vv);
                    }else{
                        $menu2->restore();
                    }
                }else{
                    $menu2 && $menu2->delete();
                }
                foreach ($vv['menulist'] as $kkk=>$vvv){
                    $menu3 = AuthRule::withTrashed()->where('href',$vvv['href'])->where('module',$module)->find();
                    if($type==1) {
                        if (!$menu3) {
                            $vvv['pid'] = $menu2['id'];
                            $vvv['module'] = $module;
                            $menu3 = AuthRule::create($vvv);
                        }else{
                            $menu3->restore();
                        }
                    }else{
                        $menu3 && $menu3->delete();
                    }
                }
            }
        }
    }
    /**
     * 获取add表单
     * @param $fieldList
     * @param $this- >addon
     * @return string
     */
    protected function getFormData()
    {
        $formFieldData = '';
        foreach ($this->fieldsList as $k => $vo) {
            if ($vo['COLUMN_KEY'] == 'PRI') continue;
            if (in_array($vo['name'], $this->config['ignoreFields']) and $vo['name']!='status') continue;
            $name = Str::studly($vo['name']);
            switch ($vo['type']) {
                case "text":
                    $formFieldData .= "{:form_input('{$vo['name']}', 'text', ['label' => '{$name}', 'verify' => '{$vo['required']}'], '{$vo['value']}')}" . PHP_EOL;
                    break;
                case "tags":
                    $formFieldData .= "{:form_tags('{$vo['name']}', ['label' => '{$name}', 'verify' => '{$vo['required']}'], '{$vo['value']}')}" . PHP_EOL;
                    break;
                case "number":
                    $formFieldData .= "{:form_input('{$vo['name']}', 'number', ['label' => '{$name}', 'verify' => '{$vo['required']}'], '{$vo['value']}')}" . PHP_EOL;
                    break;
                case "switch":
                    $vo['name_list'] = lcfirst(Str::studly($vo['name']));
//                    $formFieldData .= "{:form_switch('{$vo['name']}', \${$vo['name_list']}List, ['label' => '{$name}', 'verify' => '{$vo['required']}'], \$formData?\$formData['{$vo['name']}']:'{$vo['value']}') }" . PHP_EOL;
                    $formFieldData .= "{:form_radio('{$vo['name']}' ,\${$vo['name_list']}List, ['label' => '{$name}', 'verify' => '{$vo['required']}'], '{$vo['value']}')}" . PHP_EOL;
                    break;
                case "array":
                    $formFieldData .= "{:form_textarea('{$vo['name']}', ['label' => '{$name}', 'verify' => '{$vo['required']}'])}" . PHP_EOL;
                    break;
                case "checkbox":
                    $vo['name_list'] = lcfirst(Str::studly($vo['name']));
                    $formFieldData .= "{:form_checkbox('{$vo['name']}', \${$vo['name_list']}List,['label' => '{$name}', 'verify' => '{$vo['required']}'], \$formData?\$formData['{$vo['name']}']:'{$vo['value']}')}" . PHP_EOL;
                    break;
                case "radio":
                    $vo['name_list'] = lcfirst(Str::studly($vo['name']));
                    $formFieldData .= "{:form_radio('{$vo['name']}' ,\${$vo['name_list']}List, ['label' => '{$name}', 'verify' => '{$vo['required']}'], '{$vo['value']}')}" . PHP_EOL;
                    break;
                case "_id":
                    if($this->joinTable){
                        $vo['name_list'] = lcfirst(Str::studly($vo['name']));
                        if(strpos($vo['name'],'_ids') and in_array($vo['name'],$this->joinForeignKey)){
                            $formFieldData .= "{:form_select('{$vo['name']}',\${$vo['name_list']}List, ['label' => '{$name}', 'verify' => '{$vo['required']}','multiple'=>1, 'search' => 1], [], '{$vo['value']}')}" . PHP_EOL;
                        }elseif(strpos($vo['name'],'_id') and in_array($vo['name'],$this->joinForeignKey)){
                            $formFieldData .= "{:form_select('{$vo['name']}',\${$vo['name_list']}List, ['label' => '{$name}', 'verify' => '{$vo['required']}', 'search' => 1], [], '{$vo['value']}')}" . PHP_EOL;
                        }else{
                            $formFieldData .= "{:form_input('{$vo['name']}', 'text', ['label' => '{$name}', 'verify' => '{$vo['required']}'], '{$vo['value']}')}" . PHP_EOL;
                        }
                    }else{
                        $formFieldData .= "{:form_input('{$vo['name']}', 'text', ['label' => '{$name}', 'verify' => '{$vo['required']}'], '{$vo['value']}')}" . PHP_EOL;
                    }
                    break;
                case "select":
                    $vo['name_list'] = lcfirst(Str::studly($vo['name']));
                    if (in_array($vo['DATA_TYPE'],['set','varchar','char'])) {
                        $formFieldData .= "{:form_select('{$vo['name']}',\${$vo['name_list']}List, ['label' => '{$name}', 'verify' => '{$vo['required']}', 'multiple'=>1,'search' => 1], [], '{$vo['value']}')}" . PHP_EOL;
                    } else {
                        $formFieldData .= "{:form_select('{$vo['name']}',\${$vo['name_list']}List, ['label' => '{$name}', 'verify' => '{$vo['required']}', 'search' => 1], [], '{$vo['value']}')}" . PHP_EOL;
                    }
                    break;
                case "color":
                    $formFieldData .= "{:form_color('{$vo['name']}',['label' => '{$name}', 'verify' => '{$vo['required']}', 'search' => 1])}" . PHP_EOL;
                    break;
                case "timestamp":
                case "datetime":
                    $formFieldData .= "{:form_date('{$vo['name']}', ['label' => '{$name}', 'verify' => '{$vo['required']}'])}" . PHP_EOL;
                    break;
                case "year":
                    $formFieldData .= "{:form_date('{$vo['name']}', ['label' => '{$name}', 'verify' => '{$vo['required']}', 'type' => 'year'])}" . PHP_EOL;
                    break;
                case "date":
                    $formFieldData .= "{:form_date('{$vo['name']}', ['label' => '{$name}', 'verify' => '{$vo['required']}', 'type' => 'date'])}" . PHP_EOL;
                    break;
                case "time":
                    $formFieldData .= "{:form_date('{$vo['name']}', ['label' => '{$name}', 'verify' => '{$vo['required']}', 'type' => 'time'])}" . PHP_EOL;
                    break;
                case "range":
                    $formFieldData .= "{:form_date('{$vo['name']}', ['label' => '{$name}', 'verify' => '{$vo['required']}','range' => 'range'])}" . PHP_EOL;
                    break;
                case "textarea":
                    $formFieldData .= "{:form_textarea('{$vo['name']}', ['label' => '{$name}', 'verify' => '{$vo['required']}',], '{$vo['value']}')}" . PHP_EOL;
                    break;
                case "image":
                    $formFieldData .= "{:form_upload('{$vo['name']}',\$formData?\$formData['{$vo['name']}']:'{$vo['value']}' ,['label' => '{$name}', 'verify' => '{$vo['required']}', 'type' => 'radio', 'mime' => 'image', 'path' => '{$this->modelName}', 'num' => '1'])}" . PHP_EOL;
                    break;
                case "images":
                    $formFieldData .= "{:form_upload('{$vo['name']}', \$formData?\$formData['{$vo['name']}']:'{$vo['value']}', ['label' => '{$name}', 'verify' => '{$vo['required']}', 'type' => 'checkbox', 'mime' => 'image', 'path' =>'{$this->modelName}', 'num' => '*'])}" . PHP_EOL;
                    break;
                case "file":
                    $formFieldData .= "{:form_upload('{$vo['name']}', \$formData?\$formData['{$vo['name']}']:'{$vo['value']}', ['label' => '{$name}', 'verify' => '{$vo['required']}', 'type' => 'radio', 'mime' => 'file', 'path' =>'{$this->modelName}', 'num' => '1'])}" . PHP_EOL;
                    break;
                case "files":
                    $formFieldData .= "{:form_upload('{$vo['name']}', \$formData?\$formData['{$vo['name']}']:'{$vo['value']}', ['label' => '{$name}', 'verify' => '{$vo['required']}', 'type' => 'checkbox', 'mime' => 'file', 'path' => '{$this->modelName}', 'num' => '*'])}" . PHP_EOL;
                    break;
                case "editor":
                    $formFieldData .= "{:form_editor('{$vo['name']}', 2,['label'=>'{$name}','verify' => '{$vo['required']}'])}" . PHP_EOL;
            }
        }
        return $formFieldData;
    }


    /**
     * 获取js 栏目
     * @return string
     */
    protected function getCols()
    {
        $this->jsCols = "{checkbox: true,},".PHP_EOL."                    {field: 'id', title: __('ID'), sort:true,},".PHP_EOL;
        foreach ($this->fieldsList as $k => $v) {
            if (($this->config['fields'] && in_array($v['name'], $this->config['fields'])) || !$this->config['fields']) {
                if ($v['COLUMN_KEY'] != "PRI") {
                    $name = Str::studly($v['name']);
                    $listName = lcfirst(Str::studly($v['name']));
                    switch ($v['type']) {
                        case '_id':
                            if($this->joinTable and in_array($v['name'],$this->joinForeignKey)){ //
                                $this->jsCols .= "                    {field:'{$v['name']}',search: true,title: __('{$name}'),selectList:{$listName}List,sort:true,templet: Table.templet.tags},".PHP_EOL;;
                            }else{
                                $this->jsCols .= "                    {field:'{$v['name']}', title: __('{$name}'),align: 'center',sort:true},".PHP_EOL;
                            }
                            break;
                        case 'image':
                            $this->jsCols .= "                    {field:'{$v['name']}',title: __('{$name}'),templet: Table.templet.image},".PHP_EOL;;
                            break;
                        case 'images':
                            $this->jsCols .= "                    {field:'{$v['name']}',title: __('{$name}'),templet: Table.templet.images},".PHP_EOL;;
                            break;
                        case 'file':
                            $this->jsCols .= "                    {field:'{$v['name']}',title: __('{$name}'),templet: Table.templet.url},".PHP_EOL;;
                            break;
                        case 'files':
                            $this->jsCols .= "                    {field:'{$v['name']}',title: __('{$name}'),templet: Table.templet.url},".PHP_EOL;;
                            break;
                        case 'checkbox':
                            $this->jsCols .= "                    {field:'{$v['name']}',search: 'select',title: __('{$name}'),filter: '{$v['name']}',selectList:{$listName}List,templet: Table.templet.tags},".PHP_EOL;;
                            break;
                        case 'select':
                        case 'radio':
                            $this->jsCols .= "                    {field:'{$v['name']}',search: 'select',title: __('{$name}'),filter: '{$v['name']}',selectList:{$listName}List,templet: Table.templet.select},".PHP_EOL;;
                            break;
                        case 'switch':
                            $this->jsCols .= "                    {field:'{$v['name']}',search: 'select',title: __('{$name}'), filter: '{$v['name']}', selectList:{$listName}List,templet: Table.templet.switch},".PHP_EOL;;
                            break;
                        case 'number':
                            if ($this->hasSuffix($v['name'], ['sort'])) {
                                $this->jsCols .= "                    {field:'{$v['name']}',title: __('{$name}'),align: 'center',edit:'text'},".PHP_EOL;;
                                break;
                            }else{
                                $this->jsCols .= "                    {field:'{$v['name']}',title: __('{$name}'),align: 'center'},".PHP_EOL;;
                                break;
                            }
                        case 'date':
                            $this->jsCols .= "                    {field:'{$v['name']}',title: __('{$name}'),align: 'center',dateformat:'yyyy-MM-dd',searchdateformat:'yyyy-MM-dd',search:'time',templet: Table.templet.time,sort:true},".PHP_EOL;;
                            break;
                        case 'timestamp':
                        case 'datetime':
                                if(in_array($v['name'],['update_time','delete_time'])){
                                    break;
                                }
                                $this->jsCols .= "                    {field:'{$v['name']}',title: __('{$name}'),align: 'center',timeType:'datetime',dateformat:'yyyy-MM-dd HH:mm:ss',searchdateformat:'yyyy-MM-dd HH:mm:ss',search:'time',templet: Table.templet.time,sort:true},".PHP_EOL;;
                            break;
                        case 'year':
                            $this->jsCols .= "                    {field:'{$v['name']}',title: __('{$name}'),align: 'center',dateformat:'yyyy',searchdateformat:'yyyy',timeType:'year',search:'time',templet: Table.templet.time,sort:true},".PHP_EOL;;
                            break;
                        case 'time':
                            $this->jsCols .= "                    {field:'{$v['name']}',title: __('{$name}'),align: 'center',dateformat:'HH:mm:ss',searchdateformat:'HH:mm:ss',timeType:'time',search:'time',templet: Table.templet.time,sort:true},".PHP_EOL;;
                            break;
                        default :
                            $this->jsCols .= "                    {field:'{$v['name']}', title: __('{$name}'),align: 'center'},".PHP_EOL;
                            break;
                    }
                }
            }
        }
        $this->jsColsRecycle =$this->jsCols . '                    {
                        minWidth: 250,
                        align: "center",
                        title: __("Operat"),
                        init: Table.init,
                        templet: Table.templet.operat,
                        operat: ["restore","delete"]
                    },';
        $operat = ' ["edit", "destroy","delete"]';
        if(!$this->softDelete){
            $this->jsColsRecycle = '';
            $operat = '["edit","delete"]';
        }
        $this->jsCols .= '                    {
                        minWidth: 250,
                        align: "center",
                        title: __("Operat"),
                        init: Table.init,
                        templet: Table.templet.operat,
                        operat:' .$operat.'
                    },';
        return [$this->jsCols,$this->jsColsRecycle];
    }

    /**
     * 获取字段数据
     * @param $table
     */
    protected function getFieldList($field = '*')
    {
        $assign = [];
        $lang = '';
        $softDelete = '';
        $sql = "show tables like '{$this->tablePrefix}{$this->table}'";
        $table = Db::connect($this->driver)->query($sql);
        if(!$table){
            throw new \Exception($this->table.'表不存在');
        }
        $sql = "select $field from information_schema . columns  where table_name = '" . $this->tablePrefix . $this->table . "' and table_schema = '" . $this->database . "'";
        $tableField = Db::connect($this->driver)->query($sql);
        $tableComment = Db::connect($this->driver)->query(' SELECT TABLE_COMMENT FROM INFORMATION_SCHEMA.TABLES  WHERE TABLE_NAME =  "'.$this->tablePrefix . $this->table.'";');
        $this->tableComment =$tableComment[0]['TABLE_COMMENT'];
        foreach ($tableField as $k => &$v) {
            $v['required'] = $v['IS_NULLABLE'] == 'NO' ? 'required' : "";
            $v['comment'] = trim($v['COLUMN_COMMENT'], ' ');
            $v['comment'] = str_replace(array("\r\n", "\r", "\n"), "", $v['COLUMN_COMMENT']);
            $v['comment'] = str_replace('：',':',$v['comment']);
            $v['name'] = $v['COLUMN_NAME'];
            $v['value'] = $v['COLUMN_DEFAULT'];
            if (!$v['COLUMN_COMMENT'] and $v['COLUMN_KEY'] != 'PRI' and !in_array($v['name'], $this->config['ignoreFields'])) {
                $v['comment'] = $v['name'];
            }
            $v['type'] = 'text';
            if (in_array($v['DATA_TYPE'], ['tinyint', 'smallint', 'int', 'mediumint', 'bigint'])) {
                $v['type'] = 'number';
            }
            if (in_array($v['DATA_TYPE'], ['decimal', 'double', 'float'])) {
                $v['type'] = 'number';
            }
            if (in_array($v['DATA_TYPE'], ['enum', 'set'])) {
                $v['type'] = 'select';
            }
            if (in_array($v['DATA_TYPE'], ['tinytext', 'smalltext', 'text', 'mediumtext', 'longtext', 'json'])) {
                $v['type'] = 'textarea';
            }
            if (in_array($v['DATA_TYPE'], ['timestamp', 'datetime'])) {
                $v['type'] = 'datetime';
            }
            if (in_array($v['DATA_TYPE'], ['date'])) {
                $v['type'] = 'date';
            }
            if (in_array($v['DATA_TYPE'], ['year'])) {
                $v['type'] = 'year';
            }
            if (in_array($v['DATA_TYPE'], ['time'])) {
                $v['type'] = 'time';
            }
            $fieldsName = $v['COLUMN_NAME'];
            // 指定后缀说明也是个时间字段
            if ($this->hasSuffix($fieldsName, $this->config['fileSuffix'])) {
                $comment = explode('=', $v['comment']);
                $v['comment'] = $comment[0];
                $v['type'] = "file";
                if (isset($comment[1]) and $comment[1] > 1) {
                    $v['type'] = "files";
                }
            }
            // 指定后缀结尾且类型为varchar || char,文件上传
            if ($this->hasSuffix($fieldsName, $this->config['imageSuffix']) &&
                (($v['DATA_TYPE'] == 'varchar') || $v['DATA_TYPE'] == 'char')) {
                $comment = explode('=', $v['comment']);
                $v['comment'] = $comment[0];
                $v['type'] = "image";
                if (isset($comment[1]) and $comment[1] > 1) {
                    $v['type'] = "images";
                }
            }
            // 指定后缀说明也是个排序字段
            if ($this->hasSuffix($fieldsName, $this->config['sortSuffix'])) {
                $v['type'] = "number";
            }
            // 指定后缀说明也是个tags字段
            if ($this->hasSuffix($fieldsName, $this->config['tagsSuffix'])) {
                $v['type'] = "tags";
            }
            //指定后缀结尾 且类型为text系列的字段 为富文本编辑器
            if ($this->hasSuffix($fieldsName, $this->config['editorSuffix'])
                && in_array($v['DATA_TYPE'], ['longtext', 'mediumtext', 'text', 'smalltext', 'tinytext'])) {
                $v['type'] = "editor";
            }
            //指定后缀结尾 下来
            if ($this->hasSuffix($fieldsName, $this->config['selectSuffix'])
                && in_array($v['DATA_TYPE'], ['enum', 'set','varchar','char'])) {
                $v['type'] = "select";
            }
            // 指定后缀说明也是个时间字段
            if ($this->hasSuffix($fieldsName, $this->config['timeSuffix'])
                and $v['type'] != 'time' and $v['type'] != 'year'
                and $v['type'] != 'date'
            ) {
                $v['type'] = 'datetime';
            }
            // 指定后缀结尾且类型为enum,单选框
            if ($this->hasSuffix($fieldsName, $this->config['enumRadioSuffix']) && $v['DATA_TYPE'] == 'enum'
                &&
                $v['COLUMN_DEFAULT'] !== '' && $v['COLUMN_DEFAULT'] !== null
            ) {
                $v['type'] = "radio";
            }
            // 指定后缀结尾且类型为int,说明是radio
            if ($this->hasSuffix($fieldsName, $this->config['enumRadioSuffix']) && $v['DATA_TYPE'] == 'tinyint'
                &&
                $v['COLUMN_DEFAULT'] !== '' && $v['COLUMN_DEFAULT'] !== null
            ) {
                $v['type'] = "radio";
            }
            // 指定后缀结尾且类型为icon 颜色选择
            if ($this->hasSuffix($fieldsName, $this->config['iconSuffix']) && $v['DATA_TYPE'] == 'char') {
                $v['type'] = "icon";
            }
            // 指定后缀结尾且类型为set,说明是个复选框
            if ($this->hasSuffix($fieldsName, $this->config['setCheckboxSuffix']) && $v['DATA_TYPE'] == 'set') {
                $v['type'] = "checkbox";
            }
            // 指定后缀结尾且类型为char或tinyint且长度为1,说明是个Switch复选框
            if ($this->hasSuffix($fieldsName, $this->config['switchSuffix']) &&
                ($v['DATA_TYPE'] == 'tinyint' || $v['DATA_TYPE'] == 'int' || $v['COLUMN_TYPE'] == 'char(1)') &&
                $v['COLUMN_DEFAULT'] !== '' && $v['COLUMN_DEFAULT'] !== null) {
                $v['type'] = "switch";
            }
            //指定后缀结尾 且类型为input系列的字段 为颜色选择器
            if ($this->hasSuffix($fieldsName, $this->config['colorSuffix'])
                &&
                (($v['DATA_TYPE'] == 'varchar'
                    || $v['DATA_TYPE'] == 'char'))) {
                $v['type'] = "color";
            }
            //指定后缀结尾 且类型为number系列的字段 为其他表主键
            if ($this->hasSuffix($fieldsName, $this->config['priSuffix']) && (in_array($v['DATA_TYPE'], ['tinyint', 'smallint', 'mediumint', 'int', 'bigint','varchar','char']))) {
                $v['type'] = "_id";
                $assign[$v['name']. 'List']='';
            }
            $lang .= $this->getlangStr($v);
            if (in_array($v['DATA_TYPE'], ['tinyint','set', 'enum']) and $v['type']!='_id') {
                $comment = explode('=', $v['comment']);
                if (!in_array($v['name'], $this->config['ignoreFields'])) {
                    if (count($comment) != 2) {
                        $v['type'] = 'text';
                    }else{
                        if($v['DATA_TYPE']=='tinyint') $v['type'] = 'radio';
                        $v['comment'] = $comment[0];
                        list($assign[$v['name'] . 'List'],$v['option']) = $this->getOptionStr($v['name'],$comment[1]);
                    }
                }else{
                    if($v['name']=='status'){
                        $assign[$v['name'] . 'List'] = '[0=>"disabled",1=>"enabled"]';
                        $v['option'] = '{0:__("disabled"),1:__("enabled")}';
                        if(isset($comment[1])) list($assign[$v['name'] . 'List'],$v['option']) = $this->getOptionStr($v['name'],$comment[1]);
                    }
                    $v['comment'] = $comment[0];
                }
            }
            if($v['name']=='delete_time'){

                $softDelete = $this->getSoftDelete($v);
            }

        }
        unset($v);
        $this->fieldsList = $tableField;
        $this->assign = $assign;
        $this->lang = $lang;
        $this->softDelete = $softDelete;
        return $this;
    }

    /**
     * 获取软删除
     */
    protected function getSoftDelete($value){
        $default =  $value['value']==''?'null':$value['value'];
        $str = 'use SoftDelete;'.PHP_EOL;
        $str .= '    protected $defaultSoftDelete = '.$default.';'.PHP_EOL;
        return $str;

    }


    /**
     * 设置属性
     * @return string
     */
    protected function modifyAttr(){
        $fieldAttrData = '';
        $tpl = [
            $this->tplPath.'attrtimeget.tpl',
            $this->tplPath.'attrtimeset.tpl',
            $this->tplPath.'attrmutiget.tpl',
            $this->tplPath.'attrmutiset.tpl',
        ];
        foreach ($this->fieldsList as $k => $vo) {
            if ($vo['COLUMN_KEY'] == 'PRI') continue;
            if (in_array($vo['name'], $this->config['ignoreFields']) and $vo['name']!='status') continue;
            $name = Str::studly($vo['name']);
            $method = ucfirst($name);
            switch ($vo['type']) {
                case "checkbox":
                case "_id":
                case "select":
                    if(strpos($vo['name'],'_ids')!==false ||
                        $vo['type']=='checkbox'
                        ||   ($vo['type']=='select' && in_array($vo['DATA_TYPE'],['set','varchar','char'])) ){
                        //生成关联表的模型
                        $getTpl = str_replace([
                            '{{$methodName}}',
                        ],
                            [$method,
                            ],
                            file_get_contents($tpl[2]));
                        $setTpl = str_replace([
                            '{{$methodName}}',
                        ],
                            [$method,
                            ],
                            file_get_contents($tpl[3]));
                        $fieldAttrData.=$getTpl.PHP_EOL.$setTpl;
                    }
                    break;
                case "timestamp":
                case "datetime":
                case "range":
                case "year":
                case "date":
                case "time":
                    if($vo['DATA_TYPE']=='int'){
                        //生成关联表的模型
                        $getTpl = str_replace([
                            '{{$methodName}}',
                        ],
                            [$method,
                            ],
                            file_get_contents($tpl[0]));
                        $setTpl = str_replace([
                            '{{$methodName}}',
                        ],
                            [$method,
                            ],
                            file_get_contents($tpl[1]));
                        $fieldAttrData.=$getTpl.PHP_EOL.$setTpl;
                    }
                    break;

            }
        }
        return $fieldAttrData;
    }
    /**
     * @param $v
     * @return string
     */
    protected function getOptionStr($name,$op)
    {
        $name = Str::studly($name);
        $op = trim(trim($op, '('), ')');
        $option = explode(',', (trim(trim($op, '['), ']')));
        $optionsArrStr = "[";
        $optionObjStr = '{';
        foreach ($option as $k => $v) {
            $ops = explode(":", $v);
            $optionsArrStr .= "'" . $ops[0] . "'=>'" . $name.' '.$ops[0] . "',";
            $optionObjStr .= "'" . $ops[0] . "':'" . $name.' '. $ops[0] . "',";
        }
        $optionsArrStr .= "]";
        $optionObjStr .= "}";
        return [$optionsArrStr,$optionObjStr];
    }

    /**
     * 获取翻译字段
     * @param $v
     * @return string[]
     */
    protected function getLangStr($v)
    {
        $optionsLangStr = "";
        $comment = explode('=', $v['comment']);
        $optionsLangStr .= "'" . Str::studly($v['name']) . "'=>'" . $comment[0] . "',".PHP_EOL;
        if(isset($comment[1])){
            if(strpos($comment[1],':') !== false){ //判断是否是枚举等类型
                $op = trim(trim($comment[1], '('), ')');
                $option = explode(',', (trim(trim($op, '['), ']')));
                foreach($option as $kk=>$vv){
                    $vv = str_replace("：",':',$vv);
                    $opArr = explode(':',$vv);
//                    isset($opArr[1])?$optionsLangStr.="'" . Str::studly($v['name']). ' '. $opArr[0]. "'=>'" . $opArr[1] . "',".PHP_EOL:'';
                    $optionsLangStr.="'" . Str::studly($v['name']). ' '. $opArr[0]. "'=>'" . $opArr[1] . "',".PHP_EOL;
                }
            }
        }
        $optionsLangStr .= "";
        return $optionsLangStr;
    }
    /**
     * @param $menu
     * @return string
     */
    protected function getMenuStr($menu)
    {
        $menuStr = "[
        'is_nav'=>1,".PHP_EOL."        'menu'=>[";
        foreach ($menu['menu'] as $k => $v) {
            if(is_string($v) || is_int($v)){
                $menuStr.="            '" .$k. "'=>'" .$v . "',".PHP_EOL;
            }else{
                $menuStr.="            '".$k."'=>[".PHP_EOL;
                foreach ($v as $kk=>$vv){
                    if(is_string($vv) || is_int($vv)){
                        $menuStr.="                " .$kk. "'=>'" .$vv . ",".PHP_EOL;
                    }else{
                        $menuStr.="                [".PHP_EOL;
                        foreach ($vv as $kkk=>$vvv){
                            if(is_string($vvv) || is_int($vvv)){
                                $menuStr.="                '" .$kkk. "'=>'" .$vvv . "',".PHP_EOL;
                            }else{
                                $menuStr.="                '".$kkk."'=>[".PHP_EOL;
                                foreach ($vvv as $kkkk=>$vvvv){
                                    if(is_string($vvvv) || is_int($vvvv)){
                                        $menuStr.="                '" .$kkkk. "'=>'" .$vvvv . "',".PHP_EOL;
                                    }else{
                                        $menuStr.="                    [".PHP_EOL;
                                        foreach ($vvvv as $kkkkk=>$vvvvv){
                                            if(is_string($vvvvv) || is_int($vvvvv)){
                                                $menuStr.="                        '" .$kkkkk. "'=>'" .$vvvvv . "',".PHP_EOL;
                                            }
                                        }
                                        $menuStr.="                    ],".PHP_EOL;
                                    }
                                }
                                $menuStr.="                ],".PHP_EOL;
                            }
                        }
                        $menuStr.="                ],".PHP_EOL;
                    }
                }
                $menuStr.="            ],".PHP_EOL;
            }
        }
        $menuStr .= "        ]]";
        return $menuStr;
    }

    /**
     * 否符合指定后缀
     * @param $field
     * @param $suffixArr
     * @return bool
     */
    protected function hasSuffix($field, $suffix)
    {
        $suffix = is_array($suffix) ? $suffix : explode(',', $suffix);
        foreach ($suffix as $v) {
            if (strpos($field, $v) !== false) {
                return true;
            }
        }
        return false;
    }
}
