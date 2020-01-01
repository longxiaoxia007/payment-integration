<?php
/**
 * Created by PhpStorm.
 * User: fanglongji
 * Date: 2020/1/1
 * Time: 17:48
 */

namespace PaymentIntegration\Wechat\Common;


use PaymentIntegration\Lib\MultiplePayException;
use PaymentIntegration\Wechat\WechatMultiplePay;

class WechatAppPay extends WechatMultiplePay
{
    public function __construct()
    {
        parent::__construct();
        $this->trade_type = 'APP';
    }

    /**
     * @throws MultiplePayException
     * 支付
     */
    public function doPay()
    {
        $request_data = $this->payRequest($this->trade_type);
        if(!isset($request_data['prepay_id']) || empty($request_data['prepay_id'])) throw new MultiplePayException('支付标识缺失');
        $prepay_id = $request_data['prepay_id'];

        $sign_array = [
            'appid' => $request_data['appid'],
            'partnerid' => $request_data['mch_id'],
            'timeStamp' => strval(time()),
            'prepayid' => $prepay_id,
            'noncestr' => $this->utils->createNoncestr(),
            'package' => 'Sign=WXPay',
        ];
        $pay_sign = $this->createSign($sign_array);
        $sign_array['sign'] = $pay_sign;
        return $sign_array;
    }

    /**
     * @return mixed
     * @throws MultiplePayException
     * 退款
     */
    public function doRefund()
    {
        return $this->refundRequest($this->trade_type);
    }

    /**
     * @return mixed
     * @throws MultiplePayException
     * 订单查询
     */
    public function doOrderQuery()
    {
        return $this->orderQueryRequest($this->trade_type);
    }
}