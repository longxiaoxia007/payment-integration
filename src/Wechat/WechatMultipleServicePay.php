<?php
/**
 * Created by PhpStorm.
 * User: fanglongji
 * Date: 2020/1/1
 * Time: 22:13
 */

namespace PaymentIntegration\Wechat;


abstract class WechatMultipleServicePay extends WechatPay implements WechatPayInterface
{
    public function __construct()
    {
        parent::__construct();
        $this->pay_model = 'service';
    }
}