<?php
/**
 * Created by PhpStorm.
 * User: fanglongji
 * Date: 2020/1/1
 * Time: 22:25
 */

namespace PaymentIntegration\Tests;


use PaymentIntegration\Wechat\Common\WechatAppPay;

class Example
{
    public function index()
    {
        //调用示例  目前支持的支付有公众号支付，H5支付， 扫码支付，小程序支付和App支付，
        //同时支持普通商户模式和服务商模式，common文件下为普通商户模式相应的支付类， service文件夹下为服务商模式相应的支付类

        $appPayObject = new WechatAppPay();
        $appPayObject->doPay();
    }
}