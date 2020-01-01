<?php
/**
 * Created by PhpStorm.
 * User: fanglongji
 * Date: 2020/1/1
 * Time: 17:53
 */

namespace PaymentIntegration\Wechat;


interface WechatPayInterface
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