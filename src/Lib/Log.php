<?php
/**
 * Created by PhpStorm.
 * User: fanglongji
 * Date: 2020/1/4
 * Time: 14:56
 */

namespace PaymentIntegration\Lib;


class Log
{
    public static $instance = null;
    public static function __callStatic($method, $args)
    {
        if(is_null(self::$instance)) {
            self::$instance = new LogWriter();
        }
        return call_user_func_array(array(self::$instance, $method), $args);
    }
}