<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2019/12/31
 * Time: 15:21
 */

namespace PaymentIntegration\Wechat;


abstract class WechatMultiplePay extends WechatPay implements WechatPayInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->pay_model = 'common';
    }
}