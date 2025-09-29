<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
};

class CheckoutCallback 
{
	public static function callback( $post, $gateway, $order ) 
    {	
		$hash = base64_encode( 
            hash_hmac( 
                'sha256', 
                sanitize_text_field( $post['merchant_oid'] ) . $gateway->get_option('merchant_salt') . sanitize_text_field( $post['status'] ) . sanitize_text_field( $post['total_amount'] ), 
                $gateway->get_option('merchant_key'), 
                true 
            ) 
        );

		if ( $hash != sanitize_text_field( $post['hash'] ) ) {
			die( 'PAYTR notification failed: bad hash' );
		}		

		$post_status = $order->get_status();

		if ( $post_status == 'wc-pending' or $post_status == 'wc-failed' ) {
			if ( sanitize_text_field( $post['status'] ) == 'success' ) {

				// Reduce Stock Levels
				wc_reduce_stock_levels( $order_id[1] );

				$total_amount    = round( sanitize_text_field( $post['total_amount'] ) / 100, 2 );
				$payment_amount  = round( sanitize_text_field( $post['payment_amount'] ) / 100, 2 );
				$installment_dif = $total_amount - $payment_amount;

				// Note Start
				$note = "PAYTR - Ödeme Onaylandı\n";
                $note .= "Toplam: " . sanitize_text_field( wc_price( $total_amount, array( 'currency' => $order->get_currency() ) ) ) . "\n";
                $note .= "Ödenen: " . sanitize_text_field( wc_price( $payment_amount, array( 'currency' => $order->get_currency() ) ) ) . "\n";

				if ( $installment_dif > 0 ) {					
                    $installment_fee = new WC_Order_Item_Fee();
                    $installment_fee->set_name( __( "Taksit Farkı" ) );
                    $installment_fee->set_tax_status( 'none' );
                    $installment_fee->set_total( $installment_dif );
                    $order->add_item( $installment_fee );

                    $order->calculate_totals();					

					$note .= 'Taksit Farkı: ' . wc_price( $installment_dif, array( 'currency' => $order->get_currency() ) ) . "\n";
				}

				if ( array_key_exists( 'installment_count', $post ) ) {
                    $note .= 'Taksit Sayısı: ' . 
                            ( sanitize_text_field( $post['installment_count'] ) == 1 ? 
                            'Tek Çekim' : 
                            sanitize_text_field( $post['installment_count'] ) . ' Taksit' ) . "\n";
                }

                $note .= 'PayTR Sipariş ID: <a href="https://www.paytr.com/magaza/islemler?merchant_oid=' . 
                        sanitize_text_field( $post['merchant_oid'] ) . 
                        '" target="_blank">' . 
                        sanitize_text_field( $post['merchant_oid'] ) . 
                        '</a>';

                
                /*
				global $wpdb, $table_prefix;

				$data  = [
					'total_paid'     => $total_amount,
					'status'         => 'success',
					'status_message' => 'completed',
					'is_completed'   => 1,
					'is_failed'      => 0,
					'date_updated'   => current_time('mysql')
				];
				$where = [ 'merchant_oid' => sanitize_text_field( $post['merchant_oid'] ) ];
				$wpdb->update( $table_prefix . 'paytr_iframe_transaction', $data, $where );
                */
				$order->add_order_note( nl2br( $note ) );
				$order->update_status( 'processing' );
			} else {
				// Not Başlangıcı
                $note = "PAYTR BİLDİRİM - Ödeme Başarısız\n";
                $note .= "Hata: " . sanitize_text_field( $post['failed_reason_code'] ) . ' - ' . sanitize_text_field( $post['failed_reason_msg'] ) . "\n";
                $note .= "PayTR İşlem No: <a href='https://www.paytr.com/magaza/islemler?merchant_oid=" . sanitize_text_field( $post['merchant_oid'] ) . "' target='_blank'>" . sanitize_text_field( $post['merchant_oid'] ) . "</a>";
				
                /*
                global $wpdb, $table_prefix;

				$data  = [
					'total_paid'     => 0,
					'status'         => 'failed',
					'status_message' => sanitize_text_field( $post['failed_reason_code'] ) . ' - ' . sanitize_text_field( $post['failed_reason_msg'] ),
					'is_completed'   => 1,
					'is_failed'      => 1,
					'date_updated'   => current_time('mysql')
				];
				$where = [ 'merchant_oid' => sanitize_text_field( $post['merchant_oid'] ) ];
				$wpdb->update( $table_prefix . 'paytr_iframe_transaction', $data, $where );
                */
				$order->add_order_note( nl2br( $note ) );
				$order->update_status( 'failed' );
			}
		}

        //Tahsilat Sistemi
		//do_action('dtahsilat_payment_commit', $post);
		
		echo 'OK';
		exit;
	}
}