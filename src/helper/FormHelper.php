<?php

/**
 * FunAdmin
 * ============================================================================
 * 版权所有 2017-2028 FunAdmin，并保留所有权利。
 * 网站地址: http://www.funadmin.com
 * ----------------------------------------------------------------------------
 * 采用最新Thinkphp6 + layui 实现
 * ============================================================================
 * Author: yuege
 * Date: 2019/9/22
 */

namespace fun\helper;


use Symfony\Component\VarDumper\VarDumper;
use think\helper\Str;

class FormHelper
{

    public static function token($name = '__token__', $type = 'md5')
    {
        if (function_exists('token')) {
            return token($name, $type);
        }
        return '';
    }

    /**
     * 生成文本框(按类型) password .text
     * @param string $name
     * @param string $type
     * @param array $options
     * @return string
     */
    public static function input($name = '', $type = 'text', $options = [], $value = '')
    {
        $label = $options['label'] ?? $name;
        $tips = $options['tips'] ?? $label;
        $placeholder = $options['placeholder'] ?? $tips;
        $value = !is_null($value) ? 'value="' . $value . '"' : '';
        $disorread = self::readonlyOrdisabled($options) ? self::readonlyOrdisabled($options) : self::readonlyOrdisabled($options);
        $disorread  = $disorread ? 'layui-disabled' : '';
        if ($type == 'hidden') {
            return '<input  type="' . $type . '" name="' . $name . '"  ' . self::verify($options) . self::filter($options) . self::readonlyOrdisabled($options) . ' autocomplete="off"         placeholder="' . $placeholder . '" class="layui-input ' . self::addClass($options) . ' ' . $disorread . '" ' . $value . '/>';
        }
        $str = '<div class="layui-form-item ' . self::addClass($options) . '">'.self::label($label,$options). '<div class="layui-input-block">
         <input ' . self::addextend($options) . '  type="' . $type . '" name="' . $name . '"  ' . self::verify($options) . self::filter($options) . self::readonlyOrdisabled($options) . ' autocomplete="off"
         placeholder="' . lang($placeholder) . '" ' . self::addstyle($options) . ' class="layui-input ' . self::addClass($options) . ' ' . $disorread . '"' . $value . '/>
         ' . self::tips($options) . '
         </div></div>';
        return $str;
    }

    /**
     * @param string $name
     * @param array $options
     * @param mixed|null $value
     * @return string
     */
    public function text(string $name,array $options = [], mixed $value = null)
    {
        return $this->input( $name,'text',$options, $value);
    }

    /**
     * 创建一个密码输入字段
     *
     * @param  string  $name
     * @param  array   $options
     *
     * @return string
     */
    public function password(string $name, array $options = [])
    {
        return $this->input($name, 'password', $options);
    }

    /**
     * 创建一个范围输入选择器
     *
     * @param  string  $name
     * @param  null    $value
     * @param  array   $options
     *
     * @return string
     */
    public function range($name, $options = [], $value = null)
    {
        return $this->input($name,'range', $options,$value);
    }

    /**
     * 创建一个隐藏的输入字段
     *
     * @param  string  $name
     * @param  null    $value
     * @param  array   $options
     *
     * @return string
     */
    public function hidden($name,  $options = [],$value = null)
    {
        return $this->input( $name,'hidden', $options, $value);
    }

    /**
     * 创建一个电子邮件输入字段
     *
     * @param  string  $name
     * @param  null    $value
     * @param  array   $options
     *
     * @return string
     */
    public function email($name,  $options = [],$value = null)
    {
        return $this->input( $name,'email', $options, $value);
    }

    /**
     * 创建一个tel输入字段
     *
     * @param  string  $name
     * @param  null    $value
     * @param  array   $options
     *
     * @return string
     */
    public function tel($name,  $options = [],$value = null)
    {
        return $this->input( $name,'tel', $options, $value);
    }

    /**
     * 创建一个数字输入字段
     *
     * @param  string  $name
     * @param  null    $value
     * @param  array   $options
     *
     * @return string
     */
    public function number($name,  $options = [],$value = null)
    {
        return $this->input( $name,'number', $options, $value);
    }

    /**
     * 创建一个url输入字段
     *
     * @param  string  $name
     * @param  null    $value
     * @param  array   $options
     *
     * @return string
     */
    public function url($name,  $options = [],$value = null)
    {
        return $this->input( $name,'url', $options, $value);
    }

    /**
     * 评分
     * @param $name
     * @param $options
     * @param $value
     * @return string
     */
    public static function rate($name = '', $options = [], $value = '')
    {
        $label = $options['label'] ?? $name;
        $id = ($options['id']) ?? $name;
        $value = !is_null($value) ? $value  : '';
        $data_value = '';
        foreach ($options as $key => $val) {
            $data_value .= ' data-'.$key.'="'.$val.'" ';
        }
        $disorread = self::readonlyOrdisabled($options) ? self::readonlyOrdisabled($options) : self::readonlyOrdisabled($options);
        $disorread  = $disorread ? 'layui-disabled' : '';
        $op = json_encode($options,JSON_UNESCAPED_UNICODE);
        $str = "<div class='layui-form-item " . self::addClass($options) . "'> 
        <label class='layui-form-label " . self::labelRequire($options) . "'>" . lang(Str::title($label)) . "</label>
        <div class='layui-input-block'>
        <input  type='hidden' name='" . $name . "' class='layui-input' value='" . $value . "'>
        <div ". $data_value . self::addextend($options) . self::addstyle($options)  ." data-name='" . $name . "' data-value ='" . $value . "' id='" . $id . "'  lay-filter='rate' class='" . self::addClass($options) . "' data-options='" . $op . "'>
        " . self::tips($options) . "</div></div></div>";
        return $str;
    }
    /**
     * 滑块
     * @param $name
     * @param $options
     * @param $value
     * @return string
     */
    public static function slider($name = '', $options = [], $value = '')
    {
        $label = $options['label'] ?? $name;
        $id = ($options['id']) ?? $name;
        $value = !is_null($value) ? $value  : '';
        $data_value = '';
        foreach ($options as $key => $val) {
            $data_value .= ' data-'.$key.'="'.$val.'" ';
        }
        $disorread = self::readonlyOrdisabled($options) ? self::readonlyOrdisabled($options) : self::readonlyOrdisabled($options);
        $disorread  = $disorread ? 'layui-disabled' : '';
        $op = json_encode($options,JSON_UNESCAPED_UNICODE);
        $str = "<div class='layui-form-item " . self::addClass($options) . "'> 
        <label class='layui-form-label " . self::labelRequire($options) . "'>" . lang(Str::title($label)) . "</label>
        <div class='layui-input-block' >
        <input  type='hidden'  name='" . $name . "' class='layui-input layui-input-inline' value='" . $value . "'>
        <div " .$data_value . self::addextend($options) ." style='top:16px' data-name='" . $name . "' data-value ='" . $value . "' id='" . $id . "'  lay-filter='slider' class='" . self::addClass($options) . "' data-options='" . $op . "'>
        " . self::tips($options) . "
        </div></div></div>";
        return $str;
    }
    /**
     * @param $name
     * @param $radiolist
     * @param array $options
     * @param string $value
     * @return string
     */
    public static function radio($name = '', $radiolist=[], $options = [], $value = '')
    {
        if (is_null($radiolist)) {
            $radiolist = $name;
        }
        $label = $options['label'] ?? $name;
        $input = '';
        if (is_string($radiolist) && strpos($radiolist, "\n") !== false) $radiolist = explode("\n", $radiolist);
        if (is_array($radiolist)) {
            foreach ($radiolist as $k => $v) {
                if (is_string($v) && strpos($v, ':') !== false) {
                    $v = explode(":", $v);
                    $input .= '<input ' . self::addextend($options) . '  ' . self::addstyle($options) . ' class="' . self::addClass($options) . '" type="radio"' . self::selectedOrchecked($value, $v[0], 2) . ' name="' . $name . '" ' . self::verify($options) . self::filter($options) . self::readonlyOrdisabled($options) . ' value="' . $v[0] . '" title="' . lang($v[1]) . '" />';
                } else {
                    $input .= '<input ' . self::addextend($options) . '  ' . self::addstyle($options) . ' class="' . self::addClass($options) . '" type="radio"' . self::selectedOrchecked($value, $k, 2) . ' name="' . $name . '" ' . self::verify($options) . self::filter($options) . self::readonlyOrdisabled($options) . ' value="' . $k . '" title="' . lang($v) . '" />';
                }
            }
        } else {
            $input .= '<input ' . self::addextend($options) . '  ' . self::addstyle($options) . ' class="' . self::addClass($options) . '" type="radio" name="' . $name . '" ' . self::verify($options) . self::filter($options) . ' value="' . $radiolist . '" title="' . lang($radiolist) . '" />';
        }

        $str = ' <div class="layui-form-item">' .self::label($label,$options) . '
            <div class="layui-input-block">
            ' . $input . '
            ' . self::tips($options) . '
            </div>
        </div>';
        return $str;
    }

    /**
     * 生成开关
     * @param $name
     * @param $value
     * @param array $options
     * @return string
     * switch是关键字不能用
     */

    public static function switchs($name = '', $switch=[], $options = [], $value = '')
    {
        $label = $options['label'] ?? $name;
        $switchArr = $switch;
        if (is_string($switch) && strpos($switch, '|')) {
            $switchArr = implode('|', $switch);
        }
        $switchStr = $switchArr ? lang($switchArr[1]) . '|' . lang($switchArr[0]) : lang('open') . '|' . 'close';
        $str = '<div class="layui-form-item">' .self::label($label,$optons) . '
        <div class="layui-input-block">
        <input ' . self::addextend($options) . '  ' . self::addstyle($options) . '  class="' . self::addClass($options) . '" type="checkbox" value="' . $value . '" checked="" name="' . $name . '" ' . self::verify($options) . self::filter($options) . self::readonlyOrdisabled($options) . ' lay-skin="switch" lay-text="' . $switchStr . '"  data-text="' . lang($value) . '"/>
        ' . self::tips($options) . '
        </div>
        </div>';

        return $str;
    }

    /**
     * 多选
     * @param null $name
     * @param array $list
     * @param array $options
     * @param $value
     * @return string
     */
    public static function checkbox($name = '', $list = [], $options = [], $value = '')
    {
        if (empty($value)) $value = $name;
        if (is_string($value) && strpos($value, "\n") !== false) $value = explode("\n", $value);
        if (is_string($value) && strpos($value, ",") !== false) $value = explode(",", $value);
        if (is_string($value) && strpos($value, "|") !== false) $value = explode("|", $value);
        if (is_string($list) && strpos($list, "\n") !== false) $list = explode("\n", $list);
        if (is_string($list) && strpos($list, ",") !== false) $list = explode(",", $list);
        if (is_string($list) && strpos($list, "|") !== false) $list = explode("|", $list);
        if (
            is_string($value)
            && strpos($value, "\n") === false
            && strpos($value, ",") === false
            && strpos($value, "|") === false
        ) $value = explode(",", $value);
        $input = '';$skin = '';
        if (isset($options['skin'])) $skin = 'lay-skin="' . $options['skin'] . '"';
        if (is_array($list) && $list) {
            foreach ($list as $k => $v) {
                if (is_string($v) && strpos($v, ':') !== false) {
                    $v = explode(":", $v);
                    $check = '';
                    if (is_array($value) && in_array($v[0], $value) || $value == $v[0]) {
                        $check = 'checked';
                    }
                    $input .= '<input ' . self::addextend($options) . '  ' . self::addstyle($options) . '  class="' . self::addClass($options) . '" type="checkbox" ' . $check . ' value="' . $k . '"  name="' . $name . '[' . $v[0] . ']" ' . $skin . self::verify($options) . self::filter($options) . self::readonlyOrdisabled($options) . ' title="' . lang($v[1]) . '"/>';
                } else {
                    $check = '';
                    if ((is_array($value) &&  is_array($v) && in_array($v[0], $value)) || $value == $v) {
                        $check = 'checked';
                    } elseif ((is_array($value) &&  is_string($v) && in_array($k, $value)) || $value == $v) {
                        $check = 'checked';
                    }
                    $input .= '<input ' . self::addextend($options) . '  ' . self::addstyle($options) . '  class="' . self::addClass($options) . '" type="checkbox" ' . $check .  '  value="' . $k . '" name="' . $name . '[' . $k . ']" ' . $skin . self::verify($options) . self::filter($options) . self::readonlyOrdisabled($options) . ' title="' . lang($v) . '"/>';
                }
            }
        } else {
            $input .= '<input ' . self::addextend($options) . '  ' . self::addstyle($options) . '  class="' . self::addClass($options) . '" type="checkbox" name="' . $name . '[]"  ' . $skin . self::verify($options) . self::filter($options) . self::readonlyOrdisabled($options) . '  title="' . lang($value) . '"/>';
        }
        $label = $options['label'] ?? $name;
        $str = '<div class="layui-form-item">' .self::label($label,$options) . '
        <div class="layui-input-block">
        ' . $input . self::tips($options) . '
        </div></div>';
        return $str;
    }

    /**
     * 数组表单
     * @param null $name
     * @param array $options
     * @param array $list
     * @return string
     */
    public static function arrays($name = '', $list = [], $options = [])
    {
        $label = $options['label'] ?? $name;
        $arr = '';
        $i = 0;
        if (empty($list)) {
            $arr .= '<div class="layui-form-item" >' .self::label($label,$options) . '<div class="layui-input-inline">
                <input '. self::verify($options) . '  type="text"  name="' . $name . '[key][]"  value="" placeholder="' . lang('key') . '" autocomplete="off" class="layui-input input-double-width">
            </div>
            <div class="layui-input-inline">
                <input '. self::verify($options) . '  type="text"  name="' . $name . '[value][]"  value="" placeholder="' . lang('value') . '" autocomplete="off" class="layui-input input-double-width">
            </div><div class="layui-input-inline" >
                <button  data-name="' . $name . '" type="button" class="layui-btn layui-btn-warm layui-btn-sm addInput" lay-event="addInput">
                    <i class="layui-icon">&#xe654;</i>
                </button>
            </div></div>';
        }
        foreach ($list as $key => $value) {
            if ($i == 0) {
                $arr .= '<div class="layui-form-item" >' .self::label($label,$options) . '<div class="layui-input-inline">
                <input '. self::verify($options) . ' type="text"  name="' . $name . '[key][]"  value="' . $key . '" placeholder="' . lang('key') . '" autocomplete="off" class="layui-input input-double-width">
            </div>
            <div class="layui-input-inline">
                <input '. self::verify($options) . ' type="text"  name="' . $name . '[value][]"  value="' . $value . '" placeholder="' . lang('value') . '" autocomplete="off" class="layui-input input-double-width">
            </div><div class="layui-input-inline" >
                <button  data-name="' . $name . '" type="button" class="layui-btn layui-btn-warm layui-btn-sm addInput" lay-event="addInput">
                    <i class="layui-icon">&#xe654;</i>
                </button>
            </div></div>';;
            } else {
                $arr .= '<div class="layui-form-item">' .self::label($label,$options) . '<div class="layui-input-inline">
                <input '. self::verify($options) . ' type="text"  name="' . $name . '[key][]" value="' . $key . '"  placeholder="' . lang('key') . '" autocomplete="off" class="layui-input input-double-width">
                </div><div class="layui-input-inline">
                <input '. self::verify($options) . ' type="text"  name="' . $name . '[value][]" value="' . $value . '" placeholder="' . lang('value') . '" autocomplete="off" class="layui-input input-double-width">
            </div><div class="layui-input-inline">
                <button  data-name="' . $name . '" type="button" class="layui-btn layui-btn-danger layui-btn-sm removeInupt" lay-event="removeInupt">
                    <i class="layui-icon">&#xe67e;</i>
                </button>
            </div></div>';
            }
            $i++;
        }
        $str = '<div id="' . $name . '">' . $arr . '</div>';
        return $str;
    }

    /**
     * 文本
     * @param null $name
     * @param array $options
     * @param $value
     * @return string
     */
    public static function textarea($name = '', $options = [], $value = '')
    {
        $label = $options['label'] ?? $name;
        $tips = $options['tips'] ?? $name;
        $placeholder = $options['placeholder'] ?? $tips;
        $str = ' <div class="layui-form-item layui-form-text">' .self::label($label,$options) . '            <div class="layui-input-block">
            <textarea '. self::addextend($options) . ' ' . self::addstyle($options) . '  placeholder="' . lang($placeholder) . '" class="layui-textarea ' . self::addClass($options) . '" 
            ' . self::filter($options) . self::verify($options) . ' name="' . $name . '"
            value="' . $value . '">' . $value . '</textarea>
            ' . self::tips($options) . '
            </div></div>';
        return $str;
    }

    /**
     * @param $name
     * @param $select
     * @param $options
     * @param $attr
     * @param $value
     * @return string
     */
    public static function selectn($name = '', $select= [], $options=[], $attr=[], $value='')
    {
        $label = $options['label'] ?? $name;
        $options['url'] =  $options['url'] ?? '';
        $options['delimiter'] =   $options['delimiter'] ?? '';
        $options['search']=   isset($options['search']) ? true : '';
        $options['num'] =   $options['num'] ?? 3;
        $options['last'] =   $options['last'] ?? '';
        if ($attr) {
            $attr = is_array($attr) ? implode(',', $attr) : $attr;
        }
        $op = '';
        foreach ($options as $key => $val) {
            $op .= ' data-'.$key.'="'.$val.'" ';
        }
        $op .='data-value="' . $value . '" data-attr="' . $attr . '"';
        if (is_array($select)) {
            $op .= ' data-data="' . json_encode($select, JSON_UNESCAPED_UNICODE) . '"';
        }
        if (is_object($select)) {
            $op .= ' data-data="' . json_encode((array)$select, JSON_UNESCAPED_UNICODE) .'"';
        }
        $str = '<div class="layui-form-item layui-form" lay-filter="' . $name . '">' .self::label($label,$options) . '
                <div class="layui-input-block">
                  <div  data-verify ="'.self::labelRequire($options).'"' . self::addextend($options) . '  id="' . $name . '"' . $op . ' lay-filter="selectN" ' . self::addClass($options) . ' name="' . $name . '" '   . ' ' . self::search($options) . ' ' . self::readonlyOrdisabled($options) . ' >
                  </div>
                  ' . self::tips($options) . '
                </div>
                </div>';
        return $str;
    }
    /**
     * @param $name
     * @param $select
     * @param $options
     * @param $attr
     * @param $value
     * @return string
     */
    public static function selectplus($name = '', $select= [], $options=[], $attr=[], $value='')
    {
        $options['url']  = $options['url'] ?? '';
        $id = $options['id'] ?? $name;
        $label = $options['label'] ?? $name;
        $options['delimiter'] =   $options['delimiter'] ?? '';
        $options['fielddelimiter'] =   $options['fielddelimiter'] ?? '';
        $multiple = isset($options['multiple']) ? 'multiple="multiple"' : '';
        $options['multiple'] = $multiple?1:'';
        if ($attr) {
            $attr = is_array($attr) ? implode(',', $attr) : $attr;
        }
        $op = '';
        foreach ($options as $key => $val) {
            $op .= ' data-'.$key.'="'.$val.'" ';
        }
        $op .=  ' data-value="' . $value . '" data-attr="' . $attr . '" ';
        if (is_array($select)) {
            $op .= " data-data='" . json_encode($select, JSON_UNESCAPED_UNICODE) . "'";
        }
        if (is_object($select)) {
            $op .= ' data-data="' . json_encode((array)$select, JSON_UNESCAPED_UNICODE) . '"';
        }
        $str = '<div class="layui-form-item">' .self::label($label,$options) . '
                <div class="layui-input-block">
                  <div id="' . $id . '"  data-verify ="'.self::labelRequire($options).'"' . self::addextend($options) .  $op . ' lay-filter="selectPlus" ' . self::addClass($options) . ' name="' . $name . '" ' . $multiple . ' ' . self::search($options) . ' ' . self::readonlyOrdisabled($options) . ' >
                  
                  </div>
                  ' . self::tips($options) . '
                </div>
                </div>';
        return $str;
    }
    /**
     * @param $name
     * @param $select
     * @param $options
     * @param $attr
     * @param $value
     * @return string
     */
    public static function multiselect($name = '', $select=[], $options=[], $attr=[], $value='')
    {
        $op = '';
        if ($select) {
            foreach ($select as $k => $v) {
                $selected = '';
                if (is_array($value) && is_array($attr) && !empty($attr) && in_array($v[$attr[0]], $value) || (is_array($attr) && !empty($attr)  && $v[$attr[0]] == $value)) {
                    $selected = 'selected';
                }
                if ($value != null && $value &&  in_array($k, $value) && !$attr) {
                    $selected = 'selected';
                }
                if (!empty($attr)) {
                    $op .= '<option ' . $selected . ' value="' . $v[$attr[0]] . '">' . lang($v[$attr[1]]) . '</option>';
                } else {
                    $op .= '<option ' . $selected . ' value="' . $k . '">' . lang($v) . '</option>';
                }
            }
        }
        $label = $options['label'] ?? $name;
        $url = $options['url'] ?? '';
        $multiple = '';
        if (isset($options['multiple'])) {
            $multiple = 'multiple="multiple"';
        }
        if (isset($options['default'])) {
            $default = lang($options['default']);
        } else {
            $default = lang('Default');
        }
        $attr = is_array($attr) ? implode(',', $attr) : $attr;
        $str = '<div class="layui-form-item">' .self::label($label,$options) . '
                <div class="layui-input-block">
                  <select data-attr="' . $attr . '" data-url="' . $url . '" ' .  self::addextend($options) . ' ' . self::addstyle($options) . '  class="layui-select-url layui-select' . self::addClass($options) . '" name="' . $name . '" ' . $multiple . ' ' . self::filter($options) . ' ' . self::verify($options) . ' ' . self::search($options) . ' ' . self::readonlyOrdisabled($options) . ' >
                    <option value="">' . lang($default) . '</option>
                    ' . $op . '
                  </select>
                  ' . self::tips($options) . '
                </div>
                </div>';
        return $str;
    }
    /**
     * @param $name
     * @param $select
     * @param $options
     * @param $attr
     * @param $value
     * @return string
     */
    public static function xmselect($name = '', $select=[], $options=[], $attr=[], $value='')
    {
        $op = '';
        if (is_array($select)) {
            $op .= " data-data='" . json_encode($select, JSON_UNESCAPED_UNICODE) . "'";
        }
        if (is_object($select)) {
            $op .= " data-data='" . json_encode((array)$select, JSON_UNESCAPED_UNICODE) . "'";
        }
        $attr = is_array($attr) ? implode(',', $attr):$attr;
        $attr ? $op .= ' data-attr="' . $attr . '"' : "";
        $value = is_array($value) ? implode($value) : $value;
        $value ? $op .= ' data-value="' . $value . '"' : "";
        $options['lang'] = $options['lang'] ?? '';
        $options['tips'] = $options['tips']?? '';
        $options['empty'] =  $options['empty'] ?? '';
        $options['repeat'] = $options['repeat'] ??'';
        $options['content'] =  $options['content'] ?? '';
        $options['searchTips'] = $options['searchTips'] ?? '';
        $options['style'] = $options['style'] ?? '';
        $options['filterable'] = $options['filterable'] ?? '';
        $options['remoteSearch'] = $options['remoteSearch']  ??  '';
        $options['remoteMethod'] =  $options['remoteMethod']  ??  '';
        $options['height'] = $options['height'] ??'';
        $options['paging'] =  $options['paging'] ??'';
        $options['size'] =   $options['size'] ??'';
        $options['pageSize'] = $options['pageSize'] ??'';
        $options['pageRemote'] = $options['pageRemote'] ??'';
        $options['clickClose'] =  $options['clickClose'] ??'';
        $options['reqext'] =  $options['reqtext'] ??'';
        $options['radio'] =  $options['radio'] ?? '';
        $options['url'] =  $options['url'] ??'';
        $options['tree'] =  $options['tree'] ??'';
        $options['prop'] = $options['prop'] ??'';
        $options['parentField'] =  $options['parentField'] ??'pid';
        $options['max'] =  $options['max'] ??'';
        $options['verify'] = $options['verify'] ??'';
        $options['disabled'] =  $options['disabled'] ??'';
        $options['create'] =  $options['create'] ??'';
        $options['theme'] =  $options['theme'] ??'';
        $options['value'] = $options['value'] ??'';
        $options['autorow'] =  $options['autorow'] ??'';
        $options['toolbar'] = isset($options['toolbar'])?json_encode($options['toolbar'],JSON_UNESCAPED_UNICODE)  : '';
        foreach($options as $key=>$val){
            $op .= ' data-'.$key.'="'.$val.'" ';
        }
        $label = $options['label'] ?? $name;
        $str = '<div class="layui-form-item">' .self::label($label,$options) . '      
                <div ' . self::addextend($options) . '  ' . self::addstyle($options) . '  id="' . $name . '" name="' . $name . '" class="layui-input-block ' . self::addClass($options) . '" ' . $op . ' lay-filter="xmSelect">
                ' . self::tips($options) . '
                </div>
                </div>';
        return $str;
    }
    /**
     * @param $name
     * @param $value
     * @param array $options
     * @return string
     * 颜色选择
     */
    public static function tags($name = '', $options = [], $value = '')
    {
        $label = $options['label'] ?? $name;
        $id = $options['id'] ?? $name;
        $str = '<div class="layui-form-item">' .self::label($label,$options) . '
                    <div class="layui-input-block">
                    <div class="tags" >
                        <input type="hidden" name="' . $name . '" value="' . $value . '" />
                        <input ' . self::verify($options) . self::addextend($options) . ' ' . self::addstyle($options) . '  class="' . self::addClass($options) . '" id="' . $id . '" lay-filter="tags" type="text" placeholder="' . lang("Space To Generate Tags") . '" ' . self::filter($options) . self::readonlyOrdisabled($options) . '/>
                    </div>
                    </div>
                </div>';
        return $str;
    }

    /**
     * @param $name
     * @param $value
     * @param array $options
     * @return string
     * 颜色选择
     */
    public static function color($name = '', $options = [], $value = '')
    {

        $id = $options['id'] ?? $name;$label = $options['label'] ?? $name;$format = $options['format'] ?? 'hex';
        $str = '<div class="layui-form-item">' .self::label($label,$options) . '
                    <div class="layui-input-block">
                        <input ' . self::verify($options) . self::addstyle($options) . '  class="layui-input layui-input-inline' . self::addClass($options) . '" type="text" name="' . $name . '"  value="' . $value . '"' . self::filter($options) . self::readonlyOrdisabled($options) . '/>
                        <div ' . self::addextend($options) . '  id="' . $id . '" lay-filter="colorPicker" data-name="' . $name . '" data-format = "' . $format . '"   ></div>
                    </div>
                </div>';
        return $str;
    }

    /**
     * @param $name
     * @param $value
     * @param array $options
     * @return string
     * 图标，有点小问题
     */
    public static function icon($name = '', $options = [], $value = '')
    {
        $name = $name ? $name : 'icon';
        $value = $value ? $value : 'layui-icon-rate';
        $id = $options['id'] ?? $name;
        $str = '<div class="layui-form-item">' .self::label($label,$options) . '
                    <div class="layui-input-block">
                        <input ' . self::verify($options) . self::addextend($options) . ' type="hidden" name="' . $name . '"  id="' . $id . '" value="' . $value . '" 
                        lay-filter="iconPickers"  class="hide ' . self::addClass($options) . '" />
                    </div>
                </div>';
        return $str;
    }

    /**
     * @param null $name
     * @param array $options
     * @return string
     * 日期
     */
    public static function date($name='', $options=[], $value='')
    {
        $op = '';
        if (isset($options['range'])) {
            $op .= 'data-range="' . $options['range'] . '"';
        }
        if (isset($options['type'])) {
            $op .= 'data-type="' . $options['type'] . '"';
        }
        if (isset($options['format'])) {
            $op .= 'data-format="' . $options['format'] . '"';
        }
        $label = $options['label'] ?? $name;
        $str = '<div class="layui-form-item">' .self::label($label,$options) . '       
        <div class="layui-input-block">
         <input ' . self::verify($options) . self::addextend($options) . ' ' . self::addstyle($options) . '  class="layui-input ' . self::addClass($options) . '" type="text" name="' . $name . '" value="' . $value . '" lay-filter="date" ' . $op . ' placeholder="yyyy-MM-dd HH:mm:ss"/>
         <i class="layui-icon layui-icon-date"></i></div>
        </div>';
        return $str;
    }
    /**
     * 城市选择
     * @param string $name
     * @param $options
     * @return string
     */
    public static function city($name = 'cityPicker', $options = [])
    {
        $id =  $options['id'] ?? $name;
        $options['provinceId'] = $options['provinceId'] ?? 'province_id';
        $options['cityId'] = $options['cityId'] ?? 'city_id';
        $options['districtId'] = $options['districtId'] ?? 'area_id';
        $attr = 'data-districtid="' . $options['districtId'] . '" data-cityid="' . $options['cityId'] . '" data-provinceid="' . $options['provinceId'] . '"';
        $str = ' <div class="layui-form-item">
                    <label class="layui-form-label width_auto text-r" style="margin-top:2px">省市县：</label>
                    <div class="layui-input-block">
                        <input ' . self::verify($options) . self::addextend($options) . ' type="hidden" autocomplete="on" class="layui-input ' . self::addClass($options) . '" ' . $attr . ' lay-filter="cityPicker" id="' . $id . '" name="' . $name . '" readonly="readonly" data-toggle="city-picker" placeholder="请选择"/>
                    </div>
                    </div>';
        return $str;
    }

    /**
     * 城市选择
     * @param string $name
     * @param $options
     * @return string
     */
    public static function region($name = 'regionCheck',  $options = [])
    {
        $label = $options['label'] ?? $name;
        $id = $options['id'] ?? $name;
        $str = ' <div class="layui-form-item">' .self::label($label,$options) . '
                    <div class="layui-input-block">
                        <input type="hidden" name="' . $name . '" value="" />
                        <div ' . self::verify($options) . self::addextend($options) . ' ' . self::addstyle($options) . '  class="' . self::addClass($options) . '" id="' . $id . '" name="' . $name . '" lay-filter="regionCheck">
                        </div>
                    </div>
                </div>';
        return $str;
    }

    /**
     * @param string $name
     * @param $id
     * @param int $type
     * @param array $options
     * @return string
     * 编辑器
     */
    public static function editor($name = 'container', $type = 1, $options = [], $value = '')
    {
        $id = $options['id'] ?? $name;
        $height = $options['height'] ?? '400px';
        $path = $options['path'] ?? 'upload';
        $label = $options['label'] ?? $name;
        $str = '<div class="layui-form-item">' .self::label($label,$options) . '
         <div class="layui-input-block">';
        if ($type == 1) {
            //百度。quill wangeditor ckeditor,editormd
            $textarea = '';
            if (!empty($options['textarea'])) {
                $textarea = '<textarea '  . self::addextend($options) . '  name="' . $name . '" data-path="' . $path . '" >'   .  $value  . '</textarea>';
            }
            //百度。quill wangeditor ckeditor
            $str .= '<div ' . self::addextend($options) . '  data-value="' . htmlentities($value) . '" id="' . $id . '" name="' . $name . '" 
            data-editor="' . $type . '" lay-filter="editor"  lay-editor data-path="' . $path . '" data-height="' . $height . '" type="text/plain" >
          ' .    $textarea   . '  </div>';
        } else {
            //LAYEDIT  tinyedit
            $str .= '<textarea ' . self::addextend($options) . '  id="' . $id . '" name="' . $name . '" data-path="' . $path . '"   data-editor="' . $type . '"  lay-filter="editor" lay-editor type="text/plain">' . $value . '</textarea>';
        }
        $str .= '</div></div>';
        return $str;
    }
    /**
     * 上传
     * @param string $name
     * @param string $formData
     * @param array $options
     * @return string
     */
    public static function upload($name = 'avatar', $formData = '', $options = [], $value = '')
    {
        if (!isset($options['type'])) $options['type'] = 'radio';
        if (!isset($options['mime'])) $options['mime'] = 'images';
        if (!isset($options['num'])) $options['num'] = 1;
        if (isset($options['num']) && $options['num'] == '*') $options['num'] = 100;
        if (!isset($options['path'])) $options['path'] = 'upload'; //上传路劲
        $id = $options['id']??$name;
        $css = isset($options['css']) ? $options['css'] : 'display:inline-block;';
        $label = $options['label'] ?? $name;
        $li = '';
        $croper_container = '';
        if (isset($options['cropper'])) {
            $width = $options['width'] ?? '300';
            $height = $options['height'] ?? '300';
            $mark =  $options['mark'] ?? '1';
            $area = $options['area'] ?? '800px';
            $cops = ['name'=>$name,'path' => $options['path'], 'width' => $width, 'height' => $height, 'mark' => $mark, 'area' => $area];
            $crpperops = 'data-value="' . json_encode($cops, true) . '"';
            $data_value = '';
            foreach ($cops as $key => $val) {
                $data_value .= ' data-'.$key.'="'.$val.'" ';
            }
            $croper_container = '<button type="button" '. $data_value  . $crpperops . '
                class="layui-btn"  lay-filter="cropper" id="' .$id .'">'
                . lang('Cropper') .
                '</button>';
            $options['num'] = 1;
            $options['type'] = 'radio';
            $css .= 'width:53%!important;';
        }
        $values = [];
        $formData = is_object($formData) ? ($formData->toArray()) : $formData;
        if ($formData && is_array($formData) && array_key_exists($name, $formData)) {
            $values = explode(',', $formData[$name]);
        } elseif ($formData && is_string($formData)) {
            $values = explode(',', $formData);
        }
        $values = $value ? explode(',', $value) : $values;
        if ($value) $values = explode(',', $value);
        if (!empty(array_filter($values))) {
            foreach ($values as $k => $v) {
                if ($k + 1 <= $options['num']) {
                    switch ($options['mime']) {
                        case 'video':
                            $li .= '<li><video lay-event="" class="layui-upload-img fl"  width="150" src="' . $v . '"></video>
                    <i class="layui-icon layui-icon-close" lay-event="upfileDelete"
                    data-fileurl="' . $v . '"></i></li>';
                            break;
                        case 'audio':
                            $li .= '<li><audio lay-event="" class="layui-upload-img fl"  width="150" src="' . $v . '"></audio>
                    <i class="layui-icon layui-icon-close" lay-event="upfileDelete"
                    data-fileurl="' . $v . '"></i></li>';
                            break;
                        case 'images':
                            $li .= '<li><img lay-event="photos" class="layui-upload-img fl"  width="150" src="' . $v . '"></img>
                    <i class="layui-icon layui-icon-close" lay-event="upfileDelete"
                    data-fileurl="' . $v . '"></i></li>';
                            break;
                        case 'zip':
                            $li .= '<li><img lay-event="" class="layui-upload-img fl"  width="150" src="/static/backend/images/filetype/zip.jpg"></img>
                    <i class="layui-icon layui-icon-close" lay-event="upfileDelete"
                    data-fileurl="' . $v . '"></i></li>';
                            break;
                        case 'office':
                            $li .= '<li><img lay-event="" class="layui-upload-img fl"  width="150" src="/static/backend/images/filetype/office.jpg"></img>
                    <i class="layui-icon layui-icon-close" lay-event="upfileDelete"
                    data-fileurl="' . $v . '"></i></li>';
                            break;
                        default:
                            $li .= '<li><img lay-event="photos" class="layui-upload-img fl"  width="150" src="/static/backend/images/filetype/file.jpg">
                    <i class="layui-icon layui-icon-close" lay-event="upfileDelete"
                    data-fileurl="' . $v . '"></i></li>';
                            break;
                    }
                }
            }
            $value = implode(',', $values);
        }
        $op = [
            'name' => $name,
            'path' => $options['path'] ?? 'upload',
            'mime' => $options['mime'] ?? '*',
            'num' => $options['num'] ?? '',
            'type' => $options['type'] ?? '',
            'size' => $options['size'] ?? '',
            'exts' =>  $options['exts'] ?? '*',
            'accept' =>  $options['accept'] ?? 'file',
            'multiple' =>  $options['multiple'] ?? '',
            'selecturl' =>  $options['selecturl'] ?? '',
            'tableurl' =>  $options['tableurl'] ?? '',
        ];
        $data_value = '';
        foreach ($op as $key => $val) {
            $data_value .= ' data-'.$key.'="'.$val.'" ';
        }
        $op = " data-value='" . json_encode($op, true) . "'";
        $select_container = '';
        if ((isset($options['select']) && $options['select']) || !isset($options['select'])) {
            $options['select'] = $options['select'] ?? 'upload-select'; //可选upload-choose
            $select_container =  '<button id="' . $name . '" type="button" class="layui-btn layui-btn-danger ' . $options['select'] . '" ' .$data_value . $op . '  lay-filter="' . $options['select'] . '"><i class="layui-icon layui-icon-radio"></i>' . lang('Choose') . '</button>';
        }
        $str = ' <div class="layui-form-item">' .self::label($label,$options) . '
                <div class="layui-input-block">
                    <div class="layui-upload">
                        <input '  . self::addextend($options) . ' ' . self::addstyle($options) . '  value="' . $value . '" style="' . $css . ' ;width:65% " type="text" name="' . $name . '" class="layui-input attach ' . self::addClass($options) . '"' . self::verify($options) . '/>
                       ' . $croper_container . '
                        <button type="button" ' .$data_value .' style="margin-left:0px" class="layui-btn layui-btn-normal"  ' . $op . ' lay-filter="upload"><i class="layui-icon layui-icon-upload-drag"></i>' . lang('Uploads') . '</button>
                        ' . $select_container . '
                        <div class="layui-upload-list">'
            . $li . '
                        </div>
                    </div>
                    ' . self::tips($options) . '
                </div>
            </div>';
        return $str;
    }
    /**
     * @param bool $reset
     * @param array $options
     * @return string
     */
    public static function closebtn($reset = true, $options = [])
    {
        $show = '';
        if (!isset($options['show'])) {
            $show = 'layui-hide';
        }
        $str = '<div class="layui-form-item layui-btn-center ' . $show . '">
                <button ' . self::addstyle($options) . '  type="close" class="layui-btn layui-btn-sm ' . self::addClass($options) . '" onclick="parent.layui.layer.closeAll();">' . lang('Close') .
            '</button>
            </div>';

        return $str;
    }


    /**
     * @param bool $reset
     * @param array $options
     * @return string
     */
    public static function submitbtn($reset=true, $options=[])
    {
        $show = '';
        if (!isset($options['show'])) {
            $show = 'layui-hide';
        }
        $str = '<input type="hidden" name="__token__" value="' . self::token() . '"><div class=" layui-btn-submit layui-form-item layui-btn-center ' . $show . '" />
            <button type="submit" class="layui-btn layui-btn-sm submit" lay-fitler="submit" lay-submit>' . lang('Submit') .
            '</button>';
        if ($reset) {
            $str .= '<button type="reset" class="layui-btn layui-btn-sm layui-btn-primary reset">' . lang('Reset') . '</button>';
        }
        $str .= '</div>';
        return $str;
    }

    /**
     * @param $options
     * @return string
     * 提示
     */
    protected static function tips($options = [])
    {
        $tips = '';
        if (isset($options['tips'])) {
            $tips = '<div class="layui-form-mid layui-word-aux">' . lang($options['tips']) . '</div>';
        }
        return $tips;
    }

    /**
     * @ 验证
     * @return string
     */
    protected static function verify($options = [])
    {
        $verify = '';
        if (isset($options['verify'])) {
            $verify .= ' lay-verify="' . $options['verify'] . '"';
        }
        $type ='tips';
        if (isset($options['verType']) && $options['verType']) {
            $type = $options['verType'];
        }
        $verify.= ' lay-verType="' . $type . '" ';
        if (isset($options['reqText']) && $options['reqText']) {
            $verify.= ' lay-reqText="' . $options['reqText'] . '" ';
        }
        return $verify;
    }

    /** 过滤
     * @param $options
     * @return string
     */
    protected static function filter($options = [])
    {
        $filter = '';
        if (isset($options['filter'])) {
            $filter = 'lay-filter="' . $options['filter'] . '"';
        }
        return $filter;
    }

    /**搜索
     * @return string
     */
    protected static function search($options = [])
    {
        $search = '';
        if (!isset($options['search']) || $options['search'] == true) {
            $search = 'lay-search';
        }
        return $search;
    }
    /**
     * @param $ops
     * @param $val
     * @param int $type
     * @return string
     * 是否选中
     */
    protected static function selectedOrchecked($select=[], $val='', $type = 1)
    {
        if ($select == $val) {
            if ($type == 1) return 'selected';
            return 'checked';
        } else {
            return '';
        }
    }
    protected static function label($label,$options= []){
        
        return '<label class="layui-form-label ' . self::labelRequire($options) . '">' . lang(Str::title($label)) . '</label>';
    }
    protected static function labelRequire($options=[])
    {

        if (isset($options['verify']) && ($options['verify'] == 'required' || strpos($options['verify'], 'required') !== false)) {
            return 'required';
        }
        return '';
    }

    protected static function readonlyOrdisabled($options=[])
    {

        if (isset($options['readonly'])  && $options['readonly']) {
            return 'readonly';
        }
        if (isset($options['disabled']) && $options['disabled']) {
            return 'disabled';
        }
        return '';
    }
    //自定义class属性
    protected static function addClass($options=[])
    {
        if (isset($options['class']) && $options['class']) {
            $classArr = is_array($options['class']) ? $options['class'] : explode(',', $options['class']);
            return ' ' .implode(' ', $classArr).' ';
        }
        return '';
    }
    protected static function addstyle( $options=[])
    {
        if (isset($options['style']) && $options['style']) {
            return ' style="' . $options['style'] . '" ';
        }
        return ' ';
    }
    protected static function addextend($options=[])
    {
        if (isset($options['extend']) && $options['extend']) {
            return ' ' . $options['extend'].' ';
        }
        return ' ';
    }

}
