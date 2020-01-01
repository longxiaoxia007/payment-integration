<?php
/**
 * Created by PhpStorm.
 * User: fanglongji
 * Date: 2020/1/1
 * Time: 17:06
 */

namespace PaymentIntegration\Wechat\Common;


use PaymentIntegration\Lib\MultiplePayException;
use PaymentIntegration\Wechat\WechatMultipleServicePay;

class WechatH5ServicePay extends WechatMultipleServicePay
{
    public function __construct()
    {
        parent::__construct();
        $this->trade_type = 'MWEB';
    }

    /**
     * @throws MultiplePayException
     * 支付
     */
    public function doPay()
    {
        $request_data = $this->payRequest($this->trade_type);
        if(empty($request_data['mweb_url'])) throw new MultiplePayException('支付跳转链接缺失');
        header('Location:'. $request_data['mweb_url']);
        exit;
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