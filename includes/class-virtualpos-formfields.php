<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class VirtualPOS_FormFields 
{
    public static function get() 
    {
        return [
            'enabled' => [
                'title'   => 'Etkinleştir',
                'type'    => 'checkbox',
                'label'   => 'Bu ödeme yöntemini etkinleştir',
                'default' => 'yes'
            ],
            'title' => [
                'title'       => 'Başlık',
                'type'        => 'text',
                'description' => 'Ödeme sayfasında görülecek ad',
                'default'     => 'Sanal POS',
                'desc_tip'    => true,
            ],                
            'description' => [
                'title'       => 'Açıklama',
                'type'        => 'textarea',
                'description' => 'Ödeme sayfasında görünecek açıklama',
                'default'     => 'Kredi kartı ile güvenli ödeme.',
            ],            
            'merchant_id' => [
                'title'   => 'Merchant ID',
                'type'    => 'number',
                //'default'     => '432054',
                'default'     => '616668',
            ],
            'merchant_key' => [
                'title'   => 'Merchant Key',
                'type'    => 'text',
                //'default'     => 'KgYu7pwwHiSFn9nF',
                'default'     => 'HEWyrpG4P4wDYmLM',
            ],
            'merchant_salt' => [
                'title'   => 'Merchant Salt',
                'type'    => 'text',
                //'default'     => 'kw4Z12zCEqB1cNDx',
                'default'     => 'T7rcYEhePrX2E7Mr',
            ],
            'test_mode' => [
                'title'   => 'Test Modu',
                'type'    => 'checkbox',
                'label'   => 'Test modunu etkinleştir',
                'default' => 'yes',
            ]
        ];
    }
}