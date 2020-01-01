<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2019/12/31
 * Time: 15:46
 */

namespace PaymentIntegration\Lib;

class Utils
{
    /**
     * @param $array
     * @return string
     * 数组转化为xml
     */
    public function arrayToXml($array)
    {
        $xml = '<xml>';
        forEach($array as $k=>$v){
            $xml.='<'.$k.'><![CDATA['.$v.']]></'.$k.'>';
        }
        $xml.='</xml>';
        return $xml;
    }

    /**
     * @param $xml
     * @return mixed
     * XML转数组
     */
    public function xmlToArray($xml)
    {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    /**
     * @param int $length
     * @return string
     * 创建随机字符串
     */
    public function createNoncestr( $length = 16 )
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
}