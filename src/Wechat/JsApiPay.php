<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2019/12/31
 * Time: 15:21
 */

namespace PaymentIntegration\Wechat;


use GuzzleHttp\Client;

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
    /**
     * @var string
     * 下单url
     */
    protected $unifiedorder_url;
    /**
     * @var string
     * 退款url
     */
    protected $refund_url;
    public function __construct()
    {
        $this->unifiedorder_url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $this->refund_url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
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
        $this->params['sign'] = $this->createSign($this->params);

        $utils = new Utils();
        $xml = $utils->arrayToXml($this->params);

        $http = new Client();
        $response = $http->post($this->unifiedorder_url, [
            'body' => $xml
        ]);
        $result = $utils->xmlToArray($response->getbody());

        if($result['return_code'] !== 'SUCCESS') {
            throw new \Exception($result['return_msg']);
        }
        if($result['result_code'] !== 'SUCCESS') {
            throw new \Exception($result['err_code_des']);
        }
        $prepay_id    = (!isset($result['prepay_id']) || empty($result['prepay_id'])) ? $result['prepay_id'] : '';
        $sign_array = [
            'appId'     => $result['appid'],
            'timeStamp' => strval(time()),
            'nonceStr'  => $result['nonce_str'],
            'package'   => 'prepay_id='.$prepay_id,
            'signType'  => 'MD5',
        ];
        $paySign = $this->createSign($sign_array);
        $sign_array['paySign'] = $paySign;
        $sign_array['payment_id'] = $params['payment_id'];
        return $sign_array;
    }

    /**
     * @return string
     * 创建签名字符串
     */
    public function createSign($params)
    {
        ksort($params); //签名步骤一：按字典序排序参数
        $buff = "";
        foreach ($params as $k => $v)
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