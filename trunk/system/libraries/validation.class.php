<?php
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://www.atomcode.cn
 * @link http://www.atomcode.cn
 * @author Eachcan <eachcan@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0 2009-10-8
 * @filesource 
 */

/**
 * 数据验证类
 * 
 */
class Validation
{//类定义开始

    /**
     * 预定义验证格式
     * 
     * @var integer
     * @access protected
     */
    static $regex = array(
            'require'=> '/.+/', //匹配任意字符，除了空和断行符
            'email' => '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/',
            'phone' => '/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/',
            'mobile' => '/^((\(\d{2,3}\))|(\d{3}\-))?(13|15)\d{9}$/',
            'url' => '/^http:\/\/[A-Za-z0-9]+\.[A-Za-z0-9]+[\/=\?%\-&_~`@[\]\':+!]*([^<>\"\"])*$/',
            'currency' => '/^\d+(\.\d+)?$/',
            'number' => '/\d+$/',
            'zip' => '/^[1-9]\d{5}$/',
            'qq' => '/^[1-9]\d{4,12}$/',
            'integer' => '/^[-\+]?\d+$/',
            'double' => '/^[-\+]?\d+(\.\d+)?$/',
            'english' => '/^[A-Za-z]+$/',
    );

    /**
     * 验证数据项
     *
     * @access public
     * @param string $checkName 验证的数据类型名或正则式
     * @param string $value  要验证的数据
     * @return boolean
     * @throws AC_Execption
     */
    static function check($value,$checkName)
    {
        $matchRegex = self::getRegex($checkName);
        return preg_match($matchRegex,trim($value));
    }

    /**
     +----------------------------------------------------------
     * 取得验证类型的正则表达式
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 验证类型名称
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    static function getRegex($name)
    {
        if(isset(self::$regex[strtolower($name)])) {
            return self::$regex[strtolower($name)];
        }else {
            return $name;
        }
    }

}//类定义结束