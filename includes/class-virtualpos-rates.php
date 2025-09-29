<?php
class Rates {
    
    private $merchant_id;
    private $merchant_key;
    private $merchant_salt;
    
    public function __construct($gateway) {
        $this->merchant_id = $gateway->get_option('merchant_id');
        $this->merchant_key = $gateway->get_option('merchant_key');
        $this->merchant_salt = $gateway->get_option('merchant_salt');
    }
    
    public function get_rates($max_installment = null) {
        $request_id = time();
        $paytr_token = $this->generate_token($request_id);

        $post_vals = array(
            'merchant_id' => $this->merchant_id,
            'request_id' => $request_id,
            'paytr_token' => $paytr_token
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/taksit-oranlari");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vals);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 90);

        $result = @curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return array('status' => 'error', 'err_msg' => $error);
        }

        curl_close($ch);
        $result = json_decode($result, true);

        if ($result['status'] == 'success' && $max_installment !== null) {
            $result = $this->filter_installments($result, $max_installment);
        }

        return $result;
    }
    
    private function generate_token($request_id) {
        $hash_string = $this->merchant_id . $request_id . $this->merchant_salt;
        $hash = hash_hmac('sha256', $hash_string, $this->merchant_key, true);
        return base64_encode($hash);
    }
    
    private function filter_installments($rates_data, $max_installment) {
        foreach ($rates_data['oranlar'] as $bank => &$installments) {
            foreach ($installments as $key => $value) {
                $installment_num = (int) str_replace('taksit_', '', $key);
                if ($installment_num > $max_installment) {
                    unset($installments[$key]);
                }
            }
        }
        
        if (isset($rates_data['max_inst_non_bus'])) {
            $rates_data['max_inst_non_bus'] = min($rates_data['max_inst_non_bus'], $max_installment);
        }
        
        return $rates_data;
    }
}