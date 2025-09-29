<?php
class Installment_Service
{
    protected $merchant_id;
    protected $merchant_key;
    protected $merchant_salt;
    protected $test_mode;

    public function __construct($gateway)
    {
        $this->merchant_id   = $gateway->get_option('merchant_id');
        $this->merchant_key  = $gateway->get_option('merchant_key');
        $this->merchant_salt = $gateway->get_option('merchant_salt');
        $this->test_mode     = $gateway->get_option('test_mode') === 'yes' ? 1 : 0;
    }

    /**
     * PayTR taksit oranlarını getir
     */
    public function fetch_installments()
    {
        $request_id = time();
        $token = base64_encode(
            hash_hmac(
                'sha256',
                $this->merchant_id . $request_id . $this->merchant_salt,
                $this->merchant_key,
                true
            )
        );

        $body = [
            'merchant_id' => $this->merchant_id,
            'request_id'  => $request_id,
            'paytr_token' => $token,
        ];

        $response = wp_remote_post(
            'https://www.paytr.com/odeme/taksit-oranlari',
            [
                'method'      => 'POST',
                'timeout'     => 90,
                'redirection' => 5,
                'httpversion' => '1.1',
                'blocking'    => true,
                'headers'     => [],
                'body'        => $body,
                'sslverify'   => $this->test_mode ? false : true,
            ]
        );

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Bağlantı hatası: ' . $response->get_error_message(),
            ];
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($result['status']) && $result['status'] === 'success') {
            return [
                'success' => true,
                'data'    => $result,
            ];
        }

        return [
            'success' => false,
            'message' => $result['err_msg'] ?? 'Bilinmeyen hata',
            'data'    => $result,
        ];
    }
}