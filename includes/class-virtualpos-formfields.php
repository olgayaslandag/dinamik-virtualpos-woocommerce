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
                'default'     => '',
            ],
            'merchant_key' => [
                'title'   => 'Merchant Key',
                'type'    => 'text',
                'default'     => '',
            ],
            'merchant_salt' => [
                'title'   => 'Merchant Salt',
                'type'    => 'text',
                'default'     => '',
            ],
            'test_mode' => [
                'title'   => 'Test Modu',
                'type'    => 'checkbox',
                'label'   => 'Test modunu etkinleştir',
                'default' => 'yes',
            ],
            'reflect' => [
                'title'   => 'Vade Farkı Yansıt',
                'type'    => 'checkbox',
                'label'   => 'Vade farkı yansıt',
                'default' => 'yes',
            ]
        ];
    }
}