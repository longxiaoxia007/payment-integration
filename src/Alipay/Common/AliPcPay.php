<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2020/1/14
 * Time: 17:05
 */

namespace PaymentIntegration\Alipay\Common;

use PaymentIntegration\Lib\MultiplePayException;
use PaymentIntegration\Alipay\AliMultiplePay;
use PaymentIntegration\Alipay\AliPayInterface;

final class AliPcPay extends AliMultiplePay implements AliPayInterface
{
    /**
     * @return mixed|void
     * @throws MultiplePayException
     */
    public function doPay($is_direct = true)
    {
        $request_data = $this->payRequest();
        if ($is_direct) return $request_data;
        echo $this->getHtml($request_data);
        exit;
    }

    /**
     * @return mixed
     * 退款
     */
    public function doRefund()
    {
        // TODO: Implement doRefund() method.
    }

    /**
     * @return mixed
     * 订单查询
     */
    public function doOrderQuery()
    {
        // TODO: Implement doOrderQuery() method.
    }

    /**
     * @param $data
     * 提交支付参数
     */
    private function getHtml($data)
    {
        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->unifiedorder_url."?charset=UTF-8' method='POST'>";
        foreach($this->params as $key=>$val)
        {
            if (false === $this->utils->checkEmpty($val))
            {
                $val = str_replace("'","&apos;",$val);
                $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
            }
        }
        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit' value='ok' style='display:none;''></form>";

        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";

        return $sHtml;
    }
}