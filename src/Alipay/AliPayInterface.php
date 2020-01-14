<?php
namespace PaymentIntegration\Alipay;


interface AliPayInterface
{
    /**
     * @return mixed
     * 下单
     */
    public function doPay();
    /**
     * @return mixed
     * 退款
     */
    public function doRefund();

    /**
     * @return mixed
     * 订单查询
     */
    public function doOrderQuery();
}