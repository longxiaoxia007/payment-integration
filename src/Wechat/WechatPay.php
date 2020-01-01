<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2019/12/31
 * Time: 15:21
 */

namespace PaymentIntegration\Wechat;

use GuzzleHttp\Client;
use PaymentIntegration\Lib\MultiplePayException;
use PaymentIntegration\Lib\Utils;
use PaymentIntegration\Lib\MultipleValidate;

class WechatPay
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
     * 订单查询url
     */
    protected $order_query_url;
    /**
     * @var string
     * 退款url
     */
    protected $refund_url;
    /**
     * @var string
     * 同步通知url
     */
    protected $return_url;
    /**
     * @var string
     * 退款证书路径
     */
    protected $cert_path;
    /**
     * @var string
     * 退款证书秘钥
     */
    protected $ssl_key_path;
    /**
     * @var Utils
     * 工具类
     */
    protected $utils;
    /**
     * @var $trade_type
     * 交易类型
     */
    protected $trade_type;
    /**
     * @var $trade_type
     * 支付模式
     */
    protected $pay_model;

    public function __construct()
    {
        $this->unifiedorder_url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $this->order_query_url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $this->refund_url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $this->utils = new Utils();
    }

    /**
     * @param $parameter
     * @param $parameterValue
     * 设置支付参数
     */
    public function setParams($param, $value)
    {
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

    /**
     * @param $return_url
     * 设置同步通知地址
     */
    public function setRefundCertPath($cert_path)
    {
        $this->cert_path = $cert_path;
    }

    /**
     * @param $return_url
     * 设置同步通知地址
     */
    public function setRefundKyePath($key_path)
    {
        $this->ssl_key_path = $key_path;
    }

    /**
     * @param $return_url
     * 设置同步通知地址
     */
    public function setReturnUrl($return_url)
    {
        $this->return_url = $return_url;
    }

    /**
     * 设置必传参数
     */
    protected function setMustParams()
    {
        $this->params['sign_type'] = 'MD5';
        $this->params['trade_type'] = $this->trade_type;
        $this->params['nonce_str'] = $this->utils->createNoncestr();
    }

    /**
     * @param bool $is_direct 是否是前后端分离
     * @throws MultiplePayException
     * 统一下单接口
     */
    protected function payRequest($trade_type)
    {
        MultipleValidate::check('wechat', 'pay', $this->pay_model, $trade_type, $this->params);
        $this->setMustParams();
        $this->params['sign'] = $this->createSign($this->params);

        $xml = $this->utils->arrayToXml($this->params);
        $http = new Client();
        $response = $http->post($this->unifiedorder_url, [
            'body' => $xml
        ]);
        $result = $this->utils->xmlToArray($response->getbody());
        if ($result['return_code'] !== 'SUCCESS') {
            throw new MultiplePayException($result['return_msg']);
        }
        if ($result['result_code'] !== 'SUCCESS') {
            throw new MultiplePayException($result['err_code'] . '：' . $result['err_code_des']);
        }
        return $result;
    }

    /**
     * @return mixed
     * @throws MultiplePayException
     * 支付异步通知参数处理
     */
    public function payCallBack()
    {
        $back_str = file_get_contents("php://input");
        $back_array = $this->utils->xmlToArray($back_str);
        if ($back_array['return_code'] != 'SUCCESS') {
            throw new MultiplePayException('返回结果不正确:' . $back_array['return_msg']);
        }
        $call_back_sign = $this->createSign($back_array);
        if ($call_back_sign != $back_array['sign']) {
            throw new MultiplePayException('签名验证不通过');
        }
        return $back_array;
    }

    /**
     * @return mixed
     * @throws MultiplePayException
     * 订单查询
     */
    protected function orderQueryRequest($trade_type)
    {
        MultipleValidate::check('wechat', 'query_order',  $this->pay_model, $trade_type, $this->params);
        $this->setMustParams();
        $this->params['sign'] = $this->createSign($this->params);

        $xml = $this->utils->arrayToXml($this->params);
        $http = new Client();
        $response = $http->post($this->order_query_url, [
            'body' => $xml
        ]);
        $result = $this->utils->xmlToArray($response->getbody());
        if ($result['return_code'] !== 'SUCCESS') {
            throw new MultiplePayException($result['return_msg']);
        }
        if ($result['result_code'] !== 'SUCCESS') {
            throw new MultiplePayException($result['err_code'] . '：' . $result['err_code_des']);
        }
        return $result;
    }

    /**
     * @return mixed
     * @throws MultiplePayException
     * 申请退款
     */
    protected function refundRequest($trade_type)
    {
        MultipleValidate::check('wechat', 'refund',  $this->pay_model, $trade_type, $this->params);
        $this->setMustParams();
        $this->params['sign'] = $this->createSign($this->params);

        $xml = $this->utils->arrayToXml($this->params);
        $http = new Client();
        $response = $http->post($this->unifiedorder_url, [
            'verify' => true,
            'cert' => $this->cert_path,
            'ssl_key' => $this->ssl_key_path,
            'body' => $xml
        ]);
        $result = $this->utils->xmlToArray($response->getbody());
        if ($result['return_code'] !== 'SUCCESS') {
            throw new MultiplePayException($result['return_msg']);
        }
        if ($result['result_code'] !== 'SUCCESS') {
            throw new MultiplePayException($result['err_code'] . '：' . $result['err_code_des']);
        }
        return $result;
    }

    /**
     * @return mixed
     * 退款异步通知参数处理
     */
    public function refundCallBack()
    {
        $back_str = file_get_contents("php://input");
        $refund_notify_info = $this->utils->xmlToArray($back_str);
        $req_info = $refund_notify_info['req_info'];
        // 对加密信息进行解密,需要用到商户秘钥
        $req_info_xml = openssl_decrypt(base64_decode($req_info), 'aes-256-ecb', md5($this->key), OPENSSL_RAW_DATA);
        $req_info_data = $this->utils->xmlToArray($req_info_xml);
        return $req_info_data;
    }

    /**
     * @return string
     * 创建签名字符串
     */
    protected function createSign($params)
    {
        ksort($params); //签名步骤一：按字典序排序参数
        $buff = "";
        foreach ($params as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $string = trim($buff, "&");
        $string = $string . "&key=" . $this->key; //签名步骤二：在string后加入KEY
        $string = md5($string); //签名步骤三：MD5加密
        $result = strtoupper($string); //签名步骤四：所有字符转为大写
        return $result;
    }
}