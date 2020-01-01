<?php
/**
 * Created by PhpStorm.
 * User: fanglongji
 * Date: 2020/1/1
 * Time: 17:06
 */

namespace PaymentIntegration\Wechat\Common;


use PaymentIntegration\Lib\MultiplePayException;
use PaymentIntegration\Wechat\WechatMultiplePay;

class WechatNativePay extends WechatMultiplePay
{
    public function __construct()
    {
        parent::__construct();
        $this->trade_type = 'NATIVE';
    }

    /**
     * @throws MultiplePayException
     * 支付
     */
    public function doPay()
    {
        $request_data = $this->payRequest($this->trade_type);
        if(empty($request_data['code_url'])) throw new MultiplePayException('二维码链接缺失');
        return $request_data['code_url'];
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