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

final class WechatMiniServicePay extends WechatMultipleServicePay
{
    public function __construct()
    {
        parent::__construct();
        $this->trade_type = 'JSAPI';
    }

    /**
     * @throws MultiplePayException
     * 支付
     */
    public function doPay()
    {
        $request_data = $this->payRequest();
        if(!isset($request_data['prepay_id']) || empty($request_data['prepay_id'])) throw new MultiplePayException('支付标识缺失');
        $prepay_id = $request_data['prepay_id'];

        $sign_array = [
            'appId' => $request_data['sub_appid'],
            'timeStamp' => strval(time()),
            'nonceStr' => $this->utils->createNoncestr(),
            'package' => 'prepay_id=' . $prepay_id,
            'signType' => 'MD5',
        ];
        $pay_sign = $this->createSign($sign_array);
        $sign_array['paySign'] = $pay_sign;
        return $sign_array;
    }

    /**
     * @return mixed
     * @throws MultiplePayException
     * 退款
     */
    public function doRefund()
    {
        return $this->refundRequest();
    }

    /**
     * @return mixed
     * @throws MultiplePayException
     * 订单查询
     */
    public function doOrderQuery()
    {
        return $this->orderQueryRequest();
    }
    /**
     * @return mixed
     * @throws MultiplePayException
     * 退款查询
     */
    public function doRefundQuery()
    {
        return $this->refundQueryRequest();
    }
}