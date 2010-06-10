<?php
/**
 * AtomCode
 * 
 * A open source application,welcome to join us to develop it.
 *
 * @copyright (c)  2009 http://www.cncms.com.cn
 * @link http://www.cncms.com.cn
 * @author Eachcan <eachcan@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @version 1.0 2010-6-8
 * @filesource 
 */
if(!defined('HELPER_COMMON')){
define('HELPER_COMMON', 1);

//test
#test
/**
 * 
 * @param $string
 * @return unknown_type
 */
function ac_capitalize_ucfirst($string)
{
    if(substr($string[0],0,1) != "'" && !preg_match("!\d!",$string[0]))
        return ucfirst($string[0]);
    else
        return $string[0];
}
function ac_capitalize($string)
{
    return preg_replace_callback('!\'?\b\w(\w|\')*\b!', 'ac_capitalize_ucfirst', $string);
}

function ac_cat($string, $cat)
{
    return $string . $cat;
}

function ac_count_characters($string, $include_spaces = false)
{
    if ($include_spaces)
       return(strlen($string));

    return preg_match_all("/[^\s]/",$string, $match);
}

function ac_count_paragraphs($string)
{
    // count \r or \n characters
    return count(preg_split('/[\r\n]+/', $string));
}

function ac_count_sentences($string)
{
    // find periods with a word before but not after.
    return preg_match_all('/[^\s]\.(?!\w)/', $string, $match);
}

function ac_count_words($string)
{
    // split text by ' ',\r,\n,\f,\t
    $split_array = preg_split('/\s+/',$string);
    // count matches that contain alphanumerics
    $word_count = preg_grep('/[a-zA-Z0-9\\x80-\\xff]/', $split_array);

    return count($word_count);
}

function ac_date($string, $format = 'Y-m-d H:i:s')
{
	return date($format, $string);
}

function ac_default($string, $default = '')
{
    if (!isset($string) || $string === '')
        return $default;
    else
        return $string;
}

function ac_escape($string, $esc_type = 'html')
{
	global $var;
    switch ($esc_type) {
        case 'html':
            return htmlspecialchars($string, ENT_QUOTES, $var->config['CHARSET']);

        case 'htmlall':
            return htmlentities($string, ENT_QUOTES, $var->config['CHARSET']);

        case 'url':
            return rawurlencode($string);

        case 'urlpathinfo':
            return str_replace('%2F','/',rawurlencode($string));
            
        case 'quotes':
            // escape unescaped single quotes
            return preg_replace("%(?<!\\\\)'%", "\\'", $string);

        case 'hex':
            // escape every character into hex
            $return = '';
            for ($x=0; $x < strlen($string); $x++) {
                $return .= '%' . bin2hex($string[$x]);
            }
            return $return;
            
        case 'hexentity':
            $return = '';
            for ($x=0; $x < strlen($string); $x++) {
                $return .= '&#x' . bin2hex($string[$x]) . ';';
            }
            return $return;

        case 'decentity':
            $return = '';
            for ($x=0; $x < strlen($string); $x++) {
                $return .= '&#' . ord($string[$x]) . ';';
            }
            return $return;

        case 'javascript':
            // escape quotes and backslashes, newlines, etc.
            return strtr($string, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'));
            
        case 'mail':
            // safe way to display e-mail address on a web page
            return str_replace(array('@', '.'),array(' [AT] ', ' [DOT] '), $string);
            
        case 'nonstd':
           // escape non-standard chars, such as ms document quotes
           $_res = '';
           for($_i = 0, $_len = strlen($string); $_i < $_len; $_i++) {
               $_ord = ord(substr($string, $_i, 1));
               // non-standard char, escape it
               if($_ord >= 126){
                   $_res .= '&#' . $_ord . ';';
               }
               else {
                   $_res .= substr($string, $_i, 1);
               }
           }
           return $_res;

        default:
            return $string;
    }
}

function ac_indent($string,$chars=4,$char=" ")
{
    return preg_replace('!^!m',str_repeat($char,$chars),$string);
}

function ac_lower($string)
{
    return strtolower($string);
}

function ac_nl2br($string)
{
    return nl2br($string);
}

function ac_replace($string, $search, $replace)
{
    return str_replace($search, $replace, $string);
}

function ac_spacify($string, $spacify_char = ' ')
{
    return implode($spacify_char,
                   preg_split('//', $string, -1, PREG_SPLIT_NO_EMPTY));
}

function ac_string_format($string, $format)
{
    return sprintf($format, $string);
}

function ac_strip($text, $replace = ' ')
{
    return preg_replace('!\s+!', $replace, $text);
}

function ac_strip_tags($string, $replace_with_space = true)
{
    if ($replace_with_space)
        return preg_replace('!<[^>]*?>!', ' ', $string);
    else
        return strip_tags($string);
}

function ac_upper($string)
{
    return strtoupper($string);
}

function ac_wordwrap($string,$length=80,$break="\n",$cut=false)
{
    return wordwrap($string,$length,$break,$cut);
}

function ac_export($string, &$ref)
{
	$ref = $string;
	return '';
}

function ac_substr($string, $start, $length)
{
	global $var;
	return function_exists('mb_substr') ? mb_substr($string, $start, $length, $var->config['CHARSET']) : substr($string, $start, $length);
}

function ac_strlen($string)
{
	global $var;
	return function_exists('mb_strlen') ? mb_strlen($string, $var->config['CHARSET']) : strlen($string);
}
}
?>