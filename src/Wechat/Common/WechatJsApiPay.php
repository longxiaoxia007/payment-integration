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

final class WechatJsApiPay extends WechatMultiplePay
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
    public function doPay($is_direct = true)
    {
        $request_data = $this->payRequest();
        if(!isset($request_data['prepay_id']) || empty($request_data['prepay_id'])) throw new MultiplePayException('支付标识缺失');
        $prepay_id = $request_data['prepay_id'];

        $sign_array = [
            'appId' => $request_data['appid'],
            'timeStamp' => strval(time()),
            'nonceStr' => $this->utils->createNoncestr(),
            'package' => 'prepay_id=' . $prepay_id,
            'signType' => 'MD5',
        ];
        $pay_sign = $this->createSign($sign_array);
        $sign_array['paySign'] = $pay_sign;
        //如果是前后端分离，直接返回参数
        if ($is_direct) return $sign_array;
        echo $this->getHtml($sign_array);
        exit;
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

    /**
     * @param $data
     * @return string
     * @throws MultiplePayException
     */
    private function getHtml($data)
    {
        if(empty($this->return_url)) throw new MultiplePayException('没有设定同步跳转地址');
        header("Content-Type: text/html;charset=UTF-8");
        $strHtml = '
                <html>
                    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
                    <title>微信安全支付</title>
                    <script language="javascript">
                                //调用微信JS api 支付
                                function jsApiCall()
                                {
                                    WeixinJSBridge.invoke(
                                        "getBrandWCPayRequest",
                                        ' . json_encode($data) . ',
                                        function(res){
                                            // WeixinJSBridge.log(res.err_msg);
                                            if(res.err_msg=="get_brand_wcpay_requst:ok"){
                                                window.location.href = "' . $this->return_url . '";
                                            }else{
                                            //  alert(res.err_msg);
                                                window.location.href = "' . $this->return_url . '";
                                            }
                                            // alert(res.err_code+res.err_desc+res.err_msg);
                                        }
                                    );
                                }

                                function callpay()
                                {
                                    if (typeof WeixinJSBridge == "undefined"){
                                        if( document.addEventListener ){
                                            document.addEventListener("WeixinJSBridgeReady", jsApiCall, false);
                                        }else if (document.attachEvent){
                                            document.attachEvent("WeixinJSBridgeReady", jsApiCall);
                                            document.attachEvent("onWeixinJSBridgeReady", jsApiCall);
                                        }
                                    }else{
                                        jsApiCall();
                                    }
                                }
                                callpay();
                    </script>
                    <body>
                 <button type="button" onclick="callpay()" style="display:none;">微信支付</button>
                 </body>
                </html>
            ';
        return $strHtml;
    }
}