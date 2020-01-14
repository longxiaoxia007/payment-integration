<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2019/12/31
 * Time: 15:21
 */

namespace PaymentIntegration\Alipay;

use GuzzleHttp\Client;
use PaymentIntegration\Lib\Log;
use PaymentIntegration\Lib\MultiplePayException;
use PaymentIntegration\Lib\Utils;
use PaymentIntegration\Lib\MultipleValidate;

class AliMultiplePay
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
    /**
     * @var
     * 日志路径
     */
    protected $log_path = null;

    public function __construct()
    {
        $this->unifiedorder_url = 'https://openapi.alipay.com/gateway.do';
        $this->order_query_url = 'https://openapi.alipay.com/gateway.do';
        $this->refund_url = 'https://openapi.alipay.com/gateway.do';
        $this->refund_query_url = 'https://openapi.alipay.com/gateway.do';
        $this->utils = new Utils();
    }

    /**
     * @param $path
     */
    public function setLogPath($path)
    {
        $this->log_path = $path;
        Log::useDailyFiles($path);
    }

    /**
     * 关闭日志
     */
    public function openLogSwitch()
    {
        if(is_null($this->log_path)) throw new \Exception('请先设置好日志路径再打开日志！');
        Log::openLogSwitch();
    }

    /**
     * @param $parameter
     * @param $parameterValue
     * 设置支付参数
     */
    public function setParam($param, $value)
    {
        $this->params[$param] = $value;
    }

    /**
     * @param $biz_content
     * @return string
     * 获取处理后的请求参数
     */
    public function getBizContent($biz_content)
    {
        return json_encode($biz_content,JSON_UNESCAPED_UNICODE);
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
     * @param $cert_path
     * 设置同步通知地址
     */
    public function setRefundCertPath($cert_path)
    {
        $this->cert_path = $cert_path;
    }

    /**
     * @param $key_path
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
        $this->params['sign_type'] = 'RSA2';
        $this->params['timestamp'] = date('Y-m-d H:i:s',time());
    }

    /**
     * @param bool $is_direct 是否是前后端分离
     * @throws MultiplePayException
     * 统一下单接口
     */
    protected function payRequest()
    {
        MultipleValidate::check('wechat', 'pay', $this->pay_model, $this->trade_type, $this->params);
        $this->setMustParams();
        $this->params['method'] = $this->trade_type;
        $this->params['sign'] = $this->createSign($this->params);
        Log::info('支付宝支付接口（'.$this->trade_type.'）请求前的信息：'.var_export($this->params, 1));
        $xml = $this->utils->arrayToXml($this->params);
        $http = new Client();
        $response = $http->post($this->unifiedorder_url, [
            'body' => $xml
        ]);
        $result = $this->utils->xmlToArray($response->getbody());
        Log::info('微信支付（'.$this->trade_type.'）响应解码后的信息：'.var_export($result, 1));
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
        $notify_data = $this->utils->xmlToArray($back_str);
        Log::info('微信支付（'.$this->trade_type.'）异步回调接收的参数：'.var_export($notify_data, 1));
        if ($notify_data['return_code'] != 'SUCCESS') {
            throw new MultiplePayException('返回结果不正确:' . $notify_data['return_msg']);
        }

        $call_back_sign = $this->createSign($notify_data);
        if ($call_back_sign != $notify_data['sign']) {
            Log::info('微信支付（'.$this->trade_type.'）异步回调签名验证不通过');
            throw new MultiplePayException('签名验证不通过');
        }
        return $notify_data;
    }

    /**
     * @return mixed
     * @throws MultiplePayException
     * 订单查询
     */
    protected function orderQueryRequest()
    {
        MultipleValidate::check('wechat', 'query_order',  $this->pay_model, $this->trade_type, $this->params);
        $this->setMustParams();
        $this->params['sign'] = $this->createSign($this->params);
        Log::info('微信查询订单（'.$this->trade_type.'）请求前的信息：'.var_export($this->params, 1));
        $xml = $this->utils->arrayToXml($this->params);
        $http = new Client();
        $response = $http->post($this->order_query_url, [
            'body' => $xml
        ]);
        $result = $this->utils->xmlToArray($response->getbody());
        Log::info('微信查询订单（'.$this->trade_type.'）响应解码后的信息：'.var_export($result, 1));
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
    protected function refundRequest()
    {
        if(empty($this->cert_path) || empty($this->ssl_key_path)) throw new MultiplePayException('证书或者秘钥路径未配置');
        if(!file_exists($this->cert_path)) throw new MultiplePayException('证书路径不存在');
        if(!file_exists($this->ssl_key_path)) throw new MultiplePayException('证书秘钥路径不存在');

        MultipleValidate::check('wechat', 'refund',  $this->pay_model, $this->trade_type, $this->params);
        $this->setMustParams();
        $this->params['sign'] = $this->createSign($this->params);
        Log::info('微信申请退款（'.$this->trade_type.'）请求前的信息：'.var_export($this->params, 1));

        $xml = $this->utils->arrayToXml($this->params);
        $http = new Client();
        $response = $http->post($this->refund_url, [
            'verify' => true,
            'cert' => $this->cert_path,
            'ssl_key' => $this->ssl_key_path,
            'body' => $xml
        ]);
        $result = $this->utils->xmlToArray($response->getbody());
        Log::info('微信申请退款（'.$this->trade_type.'）响应解码后的信息：'.var_export($result, 1));
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
     * 退款查询
     */
    protected function refundQueryRequest()
    {
        MultipleValidate::check('wechat', 'query_refund',  $this->pay_model, $this->trade_type, $this->params);
        $this->setMustParams();
        $this->params['sign'] = $this->createSign($this->params);
        Log::info('微信退款查询（'.$this->trade_type.'）请求前的信息：'.var_export($this->params, 1));
        $xml = $this->utils->arrayToXml($this->params);
        $http = new Client();
        $response = $http->post($this->refund_query_url, [
            'body' => $xml
        ]);
        $result = $this->utils->xmlToArray($response->getbody());
        Log::info('微信退款查询（'.$this->trade_type.'）响应解码后的信息：'.var_export($result, 1));
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
     * 转化短链接
     */
    protected function shortUrlRequest($long_url)
    {
        $request_params['long_url'] = $long_url;
        $request_params['appid'] = $this->params['appid'];
        $request_params['mch_id'] = $this->params['mch_id'];
        $request_params['sign_type'] = 'MD5';
        $request_params['nonce_str'] = $this->utils->createNoncestr();
        $request_params['sign'] = $this->createSign($request_params);
        $xml = $this->utils->arrayToXml($this->params);
        $http = new Client();
        $response = $http->post($this->refund_query_url, [
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
        Log::info('微信退款（'.$this->trade_type.'）异步回调接收的参数：'.var_export($refund_notify_info, 1));
        $req_info = $refund_notify_info['req_info'];
        // 对加密信息进行解密,需要用到商户秘钥
        $req_info_xml = openssl_decrypt(base64_decode($req_info), 'aes-256-ecb', md5($this->key), OPENSSL_RAW_DATA);
        $req_info_data = $this->utils->xmlToArray($req_info_xml);
        Log::info('微信退款（'.$this->trade_type.'）异步回调解密的参数：'.var_export($req_info_data, 1));
        return $req_info_data;
    }

    /**
     * @return string
     * 创建签名字符串
     */
    protected function createSign($params)
    {
        $rsa_str = $this->getHandleString($params);
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->key, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        openssl_sign($rsa_str, $sign, $res, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 获取签名字符串
     * @param mixed $form 包含签名数据的数组
     * @access private
     * @return string
     */
    protected function getHandleString($params)
    {
        ksort($params);
        $string_to_be_signed = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->utils->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                $v = $this->utils->characet($v, 'UTF-8');
                if ($i == 0) {
                    $string_to_be_signed .= "$k" . "=" . "$v";
                } else {
                    $string_to_be_signed .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $string_to_be_signed;
    }

    /**
     * 返回给微信成功的信息
     */
    public function retResult()
    {
        $ret = array('return_code'=>'SUCCESS', 'return_msg'=>'OK');
        $ret = $this->utils->arrayToXml($ret);
        echo $ret;exit;
    }
}