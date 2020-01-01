<?php
/**
 * Created by PhpStorm.
 * User: fanglongji
 * Date: 2020/1/1
 * Time: 14:12
 */

namespace PaymentIntegration\Lib;


class MultipleValidate
{
    private static $params = [
        'wechat' => [
            'common' => [
                'pay' => [
                    'JSAPI' => ['appid', 'mch_id', 'body', 'out_trade_no', 'total_fee', 'notify_url', 'trade_type', 'spbill_create_ip', 'openid'],
                    'NATIVE' => ['appid', 'mch_id', 'body', 'out_trade_no', 'total_fee', 'notify_url', 'trade_type', 'spbill_create_ip',  'product_id'],
                    'APP' => ['appid', 'mch_id', 'body', 'out_trade_no', 'total_fee', 'notify_url', 'trade_type', 'spbill_create_ip'],
                    'MWEB' => ['appid', 'mch_id', 'body', 'out_trade_no', 'total_fee', 'notify_url', 'trade_type', 'spbill_create_ip',  'scene_info']
                ],
                'refund' => [
                    'JSAPI' => ['appid', 'mch_id', 'out_trade_no|transaction_id', 'out_refund_no', 'total_fee', 'refund_fee'],
                    'NATIVE' => ['appid', 'mch_id', 'out_trade_no|transaction_id', 'out_refund_no', 'total_fee', 'refund_fee'],
                    'APP' => ['appid', 'mch_id', 'out_trade_no|transaction_id', 'out_refund_no', 'total_fee', 'refund_fee'],
                    'MWEB' => ['appid', 'mch_id', 'out_trade_no|transaction_id', 'out_refund_no', 'total_fee', 'refund_fee']
                ],
                'query_order' => [
                    'JSAPI' => ['appid', 'mch_id', 'out_trade_no|transaction_id'],
                    'NATIVE' => ['appid', 'mch_id', 'out_trade_no|transaction_id'],
                    'APP' => ['appid', 'mch_id', 'out_trade_no|transaction_id'],
                    'MWEB' => ['appid', 'mch_id', 'out_trade_no|transaction_id']
                ],
            ],
            'service' => [
                'pay' => [
                    'JSAPI' => ['appid', 'mch_id', 'sub_mch_id&sub_openid', 'body', 'out_trade_no', 'total_fee', 'notify_url', 'trade_type', 'spbill_create_ip', 'openid'],
                    'NATIVE' => ['appid', 'mch_id', 'sub_mch_id', 'body', 'out_trade_no', 'total_fee', 'notify_url', 'trade_type', 'spbill_create_ip',  'product_id'],
                    'APP' => ['appid', 'mch_id', 'sub_mch_id', 'sub_appid', 'body', 'out_trade_no', 'total_fee', 'notify_url', 'trade_type', 'spbill_create_ip'],
                    'MWEB' => ['appid', 'mch_id', 'sub_mch_id', 'body', 'out_trade_no', 'total_fee', 'notify_url', 'trade_type', 'spbill_create_ip', 'scene_info']
                ],
                'refund' => [
                    'JSAPI' => ['appid', 'mch_id', 'sub_mch_id', 'out_trade_no|transaction_id', 'out_refund_no', 'total_fee', 'refund_fee'],
                    'NATIVE' => ['appid', 'mch_id', 'sub_mch_id', 'out_trade_no|transaction_id', 'out_refund_no', 'total_fee', 'refund_fee'],
                    'APP' => ['appid', 'mch_id', 'sub_mch_id', 'out_trade_no|transaction_id', 'out_refund_no', 'total_fee', 'refund_fee'],
                    'MWEB' => ['appid', 'mch_id', 'sub_mch_id', 'out_trade_no|transaction_id', 'out_refund_no', 'total_fee', 'refund_fee']
                ],
                'query_order' => [
                    'JSAPI' => ['appid', 'mch_id',  'sub_mch_id', 'out_trade_no|transaction_id'],
                    'NATIVE' => ['appid', 'mch_id',  'sub_mch_id', 'out_trade_no|transaction_id'],
                    'APP' => ['appid', 'mch_id',  'sub_mch_id', 'out_trade_no|transaction_id'],
                    'MWEB' => ['appid', 'mch_id',  'sub_mch_id', 'out_trade_no|transaction_id']
                ],
            ]
        ],

    ];

    /**
     * @param $pay_way
     * @param $action_type
     * @param $trade_type
     * @param $data
     * @return bool
     * @throws MultiplePayException
     * 检查传入的参数
     */
    public static function check($pay_way, $action_type, $trade_type, $data)
    {
        if(!isset(self::$params[$pay_way][$action_type][$trade_type]) || empty(self::$params[$pay_way][$action_type][$trade_type])) throw new MultiplePayException('没有此种交易方式');
        $trade_type_params = self::$params[$pay_way][$action_type][$trade_type];
        foreach($trade_type_params as $typ) {
            if(strpos($typ, '&') !== FALSE) {
                $temp = explode('&', $typ);
                $has_all = true;
                foreach($temp as $t) {
                    if(!array_key_exists($t, $data)) {
                        $has_all = false;
                        continue;
                    }
                }
                if(!$has_all) throw new MultiplePayException('参数' . $typ . '缺一不可');
            } else if(strpos($typ, '|') !== FALSE) {
                $temp = explode('|', $typ);
                $has_one = false;
                foreach($temp as $t) {
                    if(array_key_exists($t, $data)) {
                        $has_one = true;
                        continue;
                    }
                }
                if(!$has_one) throw new MultiplePayException('参数' . $typ . '最少要有一个');
            } else {
                if(!array_key_exists($typ, $data) || empty($data[$typ])) {
                    throw new MultiplePayException('参数' . $typ . '缺失');
                }
            }

        }
        return true;
    }


}