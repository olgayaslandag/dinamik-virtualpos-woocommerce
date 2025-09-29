<?php
class Bin_Service
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
     * Kartın ilk 6 hanesine göre BIN detaylarını getir
     */
    public function fetch_bin_details($bin_number)
    {
        if (strlen($bin_number) < 6) {
            return [
                'success' => false,
                'message' => 'Geçerli bir BIN numarası girilmedi.',
            ];
        }

        $hash_str = $bin_number . $this->merchant_id . $this->merchant_salt;
        $token = base64_encode(
            hash_hmac('sha256', $hash_str, $this->merchant_key, true)
        );
        
        $body = [
            'merchant_id' => $this->merchant_id,
            'bin_number'  => $bin_number,
            'paytr_token' => $token,
        ];

        $response = wp_remote_post(
            'https://www.paytr.com/odeme/api/bin-detail',
            [
                'method'    => 'POST',
                'timeout'   => 20,
                'body'      => $body,
                'sslverify' => $this->test_mode === "yes" ? false : true,
            ]
        );

        

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Bağlantı hatası: ' . $response->get_error_message(),
            ];
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($result)) {
            return [
                'success' => false,
                'message' => 'Geçersiz JSON cevabı alındı.',
            ];
        }

        if ($result['status'] === 'error') {
            return [
                'success' => false,
                'message' => $result['err_msg'] ?? 'Bilinmeyen hata',
            ];
        } elseif ($result['status'] === 'failed') {
            return [
                'success' => false,
                'message' => 'BIN tanımlı değil (örn. yurtdışı kartı).',
            ];
        }

        return [
            'success' => true,
            'data'    => $result,
        ];
    }    
}