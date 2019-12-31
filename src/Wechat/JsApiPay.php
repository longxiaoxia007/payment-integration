<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2019/12/31
 * Time: 15:21
 */

namespace PaymentIntegration\Wechat;


class JsApiPay
{
    /**
     * @var
     * 支付相关参数;
     */
    protected $params;
    /**
     * @var
     * 秘钥
     */
    protected $key;
    public function __construct()
    {

    }

    /**
     * @param $parameter
     * @param $parameterValue
     * 设置支付参数
     */
    public function setParams($param, $value) {
        $this->params[$param] = $value;
    }

    /**
     * @param $key
     * 设置秘钥
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    public function doPay()
    {

    }

    /**
     * @return string
     * 创建签名字符串
     */
    public function createSign()
    {
        ksort($this->params); //签名步骤一：按字典序排序参数
        $buff = "";
        foreach ($this->params as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v))
            {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $string = trim($buff, "&");
        $string = $string."&key=".$this->key; //签名步骤二：在string后加入KEY
        $string = md5($string); //签名步骤三：MD5加密
        $result = strtoupper($string); //签名步骤四：所有字符转为大写
        return $result;
    }
}