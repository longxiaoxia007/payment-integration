<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2019/12/31
 * Time: 15:21
 */

namespace PaymentIntegration\Wechat;

use GuzzleHttp\Client;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PaymentIntegration\Lib\Log;
use PaymentIntegration\Lib\LogWriter;
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
        $this->refund_query_url = 'https://api.mch.weixin.qq.com/pay/refundquery';
        $this->utils = new Utils();
//        $log_path = $_SERVER['DOCUMENT_ROOT'].'/payment-integration.txt';
//        Log::useDailyFiles($log_path);
    }

    /**
     * @param $path
     */
    public function setLogPath($path)
    {
        Log::useDailyFiles($path);
    }

    /**
     * 关闭日志
     */
    public function closeLogSwitch()
    {
        Log::closeLogSwitch();
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
        $this->params['trade_type'] = $this->trade_type;
        $this->params['sign'] = $this->createSign($this->params);
        Log::info('微信支付（'.$trade_type.'）请求前的信息：'.var_export($this->params, 1));
        $xml = $this->utils->arrayToXml($this->params);
        $http = new Client();
        $response = $http->post($this->unifiedorder_url, [
            'body' => $xml
        ]);
        $result = $this->utils->xmlToArray($response->getbody());
        Log::info('微信支付（'.$trade_type.'）响应解码后的信息：'.var_export($result, 1));
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
    protected function orderQueryRequest($trade_type)
    {
        MultipleValidate::check('wechat', 'query_order',  $this->pay_model, $trade_type, $this->params);
        $this->setMustParams();
        $this->params['sign'] = $this->createSign($this->params);
        Log::info('微信查询订单（'.$trade_type.'）请求前的信息：'.var_export($this->params, 1));
        $xml = $this->utils->arrayToXml($this->params);
        $http = new Client();
        $response = $http->post($this->order_query_url, [
            'body' => $xml
        ]);
        $result = $this->utils->xmlToArray($response->getbody());
        Log::info('微信查询订单（'.$trade_type.'）响应解码后的信息：'.var_export($result, 1));
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
        if(empty($this->cert_path) || empty($this->ssl_key_path)) throw new MultiplePayException('证书或者秘钥路径未配置');
        if(!file_exists($this->cert_path)) throw new MultiplePayException('证书路径不存在');
        if(!file_exists($this->ssl_key_path)) throw new MultiplePayException('证书秘钥路径不存在');

        MultipleValidate::check('wechat', 'refund',  $this->pay_model, $trade_type, $this->params);
        $this->setMustParams();
        $this->params['sign'] = $this->createSign($this->params);
        Log::info('微信申请退款（'.$trade_type.'）请求前的信息：'.var_export($this->params, 1));

        $xml = $this->utils->arrayToXml($this->params);
        $http = new Client();
        $response = $http->post($this->refund_url, [
            'verify' => true,
            'cert' => $this->cert_path,
            'ssl_key' => $this->ssl_key_path,
            'body' => $xml
        ]);
        $result = $this->utils->xmlToArray($response->getbody());
        Log::info('微信申请退款（'.$trade_type.'）响应解码后的信息：'.var_export($result, 1));
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
    protected function refundQueryRequest($trade_type)
    {
        MultipleValidate::check('wechat', 'query_refund',  $this->pay_model, $trade_type, $this->params);
        $this->setMustParams();
        $this->params['sign'] = $this->createSign($this->params);
        Log::info('微信退款查询（'.$trade_type.'）请求前的信息：'.var_export($this->params, 1));
        $xml = $this->utils->arrayToXml($this->params);
        $http = new Client();
        $response = $http->post($this->refund_query_url, [
            'body' => $xml
        ]);
        $result = $this->utils->xmlToArray($response->getbody());
        Log::info('微信退款查询（'.$trade_type.'）响应解码后的信息：'.var_export($result, 1));
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