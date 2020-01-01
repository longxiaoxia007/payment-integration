<?php
/**
 *
 * 支付API异常类
 * @author widyhu
 *
 */
namespace PaymentIntegration\Lib;


class MultiplePayException extends \Exception
{
    public function errorMessage()
    {
        return $this->getMessage();
    }
}