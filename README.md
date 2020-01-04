# payment-integration
支付集成包
# 安装
```php
composer require payment-integration
```
# 使用教程
## 简介
#### 1.目前微信支持的支付有公众号支付，H5支付， 扫码支付，小程序支付和App支付五种支付方式，支付宝支付将在后续版本中添加；
#### 2.同时支持普通商户模式和服务商模式，common文件下为普通商户模式相应的支付类， service文件夹下为服务商模式相应的支付类;
#### 3.不同的支付实例化对应的支付类，何种支付一目了然，不需要设定过多参数，避免犯错误；
#### 4.所有支付的过程为：1.实例化支付类；2.按照微信官方的要求进行参数设定；3.支付则调用类中的doPay方法，查询订单调用doOrderQuery，退款调用doRefund，退款查询用doRefundQuery；
#### 5.所有支付参数中的sign_type（默认MD5）,trade_type,nonce_str不需要传递，内部已经自动处理，必传参数内部会进行验证; 
#### 6.为便于调试，内置了基于monolog的日志记录，可用setLogPath方法设置日志路径(务必保证目录有写入权限)，同时可用closeLogSwitch方法关闭日志，具体请根据需求自定义设置；
#### 7.所有错误均以异常的形式抛出，请在实际逻辑中自行处理异常;
#### 8.使用过程中发现问题，欢迎批评指正，QQ：421718630。


## 代码示例
#### 微信公众号支付
#### 统一下单
##### 实例化支付类
```php
$PayObject = new WechatJsApiPay();//公众号支付
//            $PayObject = new WechatAppPay();//app支付
//            $PayObject = new WechatH5Pay();//H5支付
//            $PayObject = new WechatMiniPay();//小程序支付
//            $PayObject = new WechatNativePay();//扫码支付
```
##### 以下设置支付参数（以公众号支付的必选参数为例，其他支付参阅官方文档）
```php
$PayObject->setLogPath('path/log.txt');//通过此参数设置日志记录路径，如果不设置 会默认在项目根目录下
$PayObject->closeLogSwitch();//此参数可关闭日志记录，默认为打开状态
$PayObject->setParam('appid', 'wx2421b1c4370ec43b');
$PayObject->setParam('mch_id', '10000100');
$PayObject->setParam('openid', 'oUpF8uMuAJO_M2pxb1Q9zNjWeS6o');
$PayObject->setParam('out_trade_no', '1415659990');
$PayObject->setParam('spbill_create_ip', '14.23.150.211');
$PayObject->setParam('total_fee', 1);
$PayObject->setParam('notify_url', 'http://wxpay.wxutil.com/pub_v2/pay/notify.v2.php');
$PayObject->setParam('body', 'JSAPI支付测试');

$PayObject->setKey('sfsfsddfgs2343453534gdfgdfsgdfg');
//非前后端分离的直接支付需要设定支付后同步跳转地址
$PayObject->setReturnUrl('www.baidu.com');
```
##### 调用支付函数，默认为true，参数true代表为前后端分离的方式，false代表微信公众号内直接发起支付，true返回需要的支付参数，false会直接发起支付
##### 除需要直接发起的公众号支付需要传递false以外，其他支付方式使用默认值true即可
```php
$return_data = $PayObject->doPay(true);
//返回参数示例
//1.公众号支付
$return_data = [
    'appId' => 'wx2421b1c4370ec43b',
    'timeStamp' => '20091227091010',
    'nonceStr' => '5K8264ILTKCH16CQ2502SI8ZNMTM67VS',
    'package' => 'prepay_id=123',
    'signType' => 'MD5',
    'paySign' => '0CB01533B8C1EF103065174F50BCA001',
];
//2.app支付
$return_data = [
    'appId' => 'wx2421b1c4370ec43b',
    'partnerid' => '10000100',
    'timeStamp' => '20091227091010',
    'nonceStr' => '5K8264ILTKCH16CQ2502SI8ZNMTM67VS',
    'package' => 'Sign=WXPay',
    'prepayid' => 1222,
    'paySign' => '0CB01533B8C1EF103065174F50BCA001',
];
//3.H5支付
$return_data = [
    'mweb_url' => 'https://wx.tenpay.com/cgi-bin/mmpayweb-bin/checkmweb?prepay_id=wx2016121516420242444321ca0631331346&package=1405458241'
];
//4.小程序支付
$return_data = [
    'appId' => 'wx2421b1c4370ec43b',
    'timeStamp' => '20091227091010',
    'nonceStr' => '5K8264ILTKCH16CQ2502SI8ZNMTM67VS',
    'package' => 'prepay_id=123',
    'signType' => 'MD5',
    'paySign' => '0CB01533B8C1EF103065174F50BCA001',
];
//5.扫码支付
$return_data = [
    'short_url' => 'weixin：//wxpay/s/XXXXXX'
];
```
##### **********************************************************************************************/
##### 支付异步回调数据处理
##### 实例化公众号支付类
```php
$PayObject = new WechatJsApiPay();
```
##### 该包内封装了 自动接收异步参数并进行处理验证, 调用函数前必须首先设定key
```php
$PayObject->setKey('sfsfsddfgs2343453534gdfgdfsgdfg');
```
##### 返回官方原样参数
```php
$return_data = $PayObject->payCallBack();
```
#### 查询订单部分
##### 实例化公众号支付类
```php
$PayObject = new WechatJsApiPay();
```
##### 以下设置支付参数（只设置了必选参数，其他参数根据需要按照官方文档要求设置即可）
```php
$PayObject->setParam('appid', 'wx2421b1c4370ec43b');
$PayObject->setParam('mch_id', '10000100');
$PayObject->setParam('transaction_id', '1008450740201411110005820873');

$PayObject->setKey('sfsfsddfgs2343453534gdfgdfsgdfg');
```
##### 调用查询订单函数,参数按照微信官方文档返回
```php
$return_data = $PayObject->doOrderQuery();
```
#### 申请退款部分
##### 实例化公众号支付类
```php
$PayObject = new WechatJsApiPay();
```
##### 设置退款参数
```php
$PayObject->setParam('appid', 'wx2421b1c4370ec43b');
$PayObject->setParam('mch_id', '10000100');
$PayObject->setParam('out_refund_no', '1415701182');
$PayObject->setParam('out_trade_no', '1415757673');
$PayObject->setParam('refund_fee', 1);
$PayObject->setParam('transaction_id', '4006252001201705123297353072');

$PayObject->setKey('sfsfsddfgs2343453534gdfgdfsgdfg');
$PayObject->setRefundCertPath('data/cert/key_path');
$PayObject->setRefundKyePath('data/cert/key_path');
```
##### 调用申请退款函数,参数按照微信官方文档返回
```php
$return_data = $PayObject->doRefund();
```
###### **********************************************************************************************/
##### 退款异步回调数据处理
##### 实例化公众号支付类
```php
$PayObject = new WechatJsApiPay();
```
##### 该包内封装了 自动接收异步参数并进行处理验证, 调用函数前必须首先设定key
```php
$PayObject->setKey('sfsfsddfgs2343453534gdfgdfsgdfg');
```
##### 返回官方原样参数
```php
$return_data = $PayObject->refundCallBack();
```
###### **********************************************************************************************/
#### 退款查询部分
##### 实例化公众号支付类
```php
$PayObject = new WechatJsApiPay();
```
##### 以下设置支付参数（只设置了必选参数，其他参数根据需要按照官方文档要求设置即可）
```php
$PayObject->setParam('appid', 'wx2421b1c4370ec43b');
$PayObject->setParam('mch_id', '10000100');
$PayObject->setParam('transaction_id', '1008450740201411110005820873');

$PayObject->setKey('sfsfsddfgs2343453534gdfgdfsgdfg');
```
##### 调用查询订单函数,参数按照微信官方文档返回
```php
$return_data = $PayObject->doRefundQuery();
```

