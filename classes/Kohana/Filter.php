<?php defined('SYSPATH') OR die('No direct script access.');
/**
 * Filtration rules.
 * Based on Kohana Valid helper
 * Derivative work by Dariusz Rorat
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2008-2012 Kohana Team
 * @license    http://kohanaframework.org/license
 */
class Kohana_Filter
{
    public static function url($str, $replacement = '#')
    {
        $regex = '/[a-zA-Z]*[:\/\/]*[A-Za-z0-9\-_]+\.+[A-Za-z0-9\.\/%&=\?\-_]+/i';
        return Filter::regex($str, $regex, $replacement);
    }

    public static function email($str, $replacement = '#')
    {
        $regex = '/([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)/';
        return Filter::regex($str, $regex, $replacement);
    }

    public static function phone($str, $replacement = '#')
    {
        $regex = '/\+?[0-9][0-9()-\s+]{4,20}[0-9]/';
        return Filter::regex($str, $regex, $replacement);
    }

    public static function regex($str, $regex, $replacement = '#')
    {
        if (UTF8::strlen($replacement) == 1)
        {
            return preg_replace_callback($regex, function($matches) use ($replacement) {
                return str_repeat($replacement, UTF8::strlen($matches[0]));
            }, $str);
        }

        return preg_replace($regex, $replacement, $str);
    }

}
