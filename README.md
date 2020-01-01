# payment-integration
支付集成包

# 使用教程
## 目前支持的支付有公众号支付，H5支付， 扫码支付，小程序支付和App支付，同时支持普通商户模式和服务商模式，common文件下为普通商户模式相应的支付类， service文件夹下为服务商模式相应的支付类
## 
```
$appPayObject = new WechatAppPay();
$appPayObject->doPay();
```

