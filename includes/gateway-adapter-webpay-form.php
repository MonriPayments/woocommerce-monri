<?php

class Monri_WC_Gateway_Adapter_Webpay_Form {

	/**
	 * Adapter ID
	 */
	public const ADAPTER_ID = 'webpay_form';

	public const ENDPOINT_TEST = 'https://ipgtest.monri.com/v2/form';
	public const ENDPOINT = 'https://ipg.monri.com/v2/form';

	/**
	 * @var Monri_WC_Gateway
	 */
	private $payment;

	/**
	 * @param Monri_WC_Gateway $payment
	 *
	 * @return void
	 */
	public function init( $payment ) {
		$this->payment = $payment;

		//$this->check_response();
		add_action( 'woocommerce_receipt_' . $this->payment->id, [ $this, 'process_redirect' ] );
		add_action( 'woocommerce_thankyou', [ $this, 'process_return' ] );

		// load installments fee calculations if installments enabled
		if ( $this->payment->get_option( 'paying_in_installments' ) ) {
			require_once __DIR__ . '/installments-fee.php';
			( new Monri_WC_Installments_Fee() )->init();
		}

	}

	/**
	 * Redirect to receipt page
	 *
	 * @param $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		return [
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true )
		];
	}

	/**
	 * Validate checkout fields for Monri
	 *
	 * @return true
	 * @throws Exception
	 */
	public function validate_fields() {

        $post_data = wc()->checkout()->get_posted_data();
        $domain = "monri";

		if ( empty( $post_data['billing_first_name'] ) || strlen( $post_data['billing_first_name'] ) < 3 || strlen( $post_data['billing_first_name'] ) > 11 ) {
			throw new Exception( __('First name must have between 3 and 11 characters', $domain) );
		}
		if ( empty( $post_data['billing_last_name'] ) || strlen( $post_data['billing_last_name'] ) < 3 || strlen( $post_data['billing_last_name'] ) > 18 ) {
			throw new Exception( __("Last name must have between 3 and 28 characters", $domain) );
		}
		if ( empty( $post_data['billing_address_1'] ) || strlen( $post_data['billing_address_1'] ) < 3 || strlen( $post_data['billing_address_1'] ) > 300 ) {
			throw new Exception( __('Address must have between 3 and 300 characters', $domain) );
		}
		if ( empty( $post_data['billing_city'] ) || strlen( $post_data['billing_city'] ) < 3 || strlen( $post_data['billing_city'] ) > 30 ) {
			throw new Exception( __('City must have between 3 and 30 characters', $domain) );
		}
		if ( empty( $post_data['billing_postcode'] ) || strlen( $post_data['billing_postcode'] ) < 3 || strlen( $post_data['billing_postcode'] ) > 9 ) {
			throw new Exception( __('ZIP must have between 3 and 30 characters', $domain) );
		}
		if ( empty( $post_data['billing_phone'] ) || strlen( $post_data['billing_phone'] ) < 3 || strlen( $post_data['billing_phone'] ) > 30 ) {
			throw new Exception( __('Phone must have between 3 and 30 characters', $domain) );
		}
		if ( empty( $post_data['billing_email'] ) || strlen( $post_data['billing_email'] ) < 3 || strlen( $post_data['billing_email'] ) > 100 ) {
			throw new Exception( __('Email must have between 3 and 30 characters', $domain) );
		}

		return true;
	}

	/**
	 * Redirect form on receipt page
	 *
	 * @param int $order_id
	 *
	 * @return void
	 */
	function process_redirect( $order_id ) {

		$order = wc_get_order( $order_id );

		$key   = $this->payment->get_option( 'monri_merchant_key' );
		$token = $this->payment->get_option( 'monri_authenticity_token' );

		//Convert order amount to number without decimals
		$order_total = $order->get_total() * 100;

		$currency = $order->get_currency();
		if ( $currency === 'KM' ) {
			$currency = 'BAM';
		}

		//Generate digest key
		$digest = hash( 'sha512', $key . $order_id . $order_total . $currency );

		//Combine first and last name in one string
		$full_name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();

		//Array of order information
		$args = array(
			'ch_full_name' => $full_name,
			'ch_address'   => $order->get_billing_address_1(),
			'ch_city'      => $order->get_billing_city(),
			'ch_zip'       => $order->get_billing_postcode(),
			'ch_country'   => $order->get_billing_country(),
			'ch_phone'     => $order->get_billing_phone(),
			'ch_email'     => $order->get_billing_email(),

			'order_info'      => $order_id . '_' . date( 'dmy' ),
			'order_number'    => $order_id,
			'amount'          => $order_total,
			'currency'        => $currency,
			'original_amount' => $order->get_total(),

			'language'              => $this->payment->get_option( 'form_language' ),
			'transaction_type'      => $this->payment->get_option_bool( 'transaction_type' ) ? 'authorize' : 'purchase',
			'authenticity_token'    => $token,
			'digest'                => $digest,
			'success_url_override'  => $this->payment->get_return_url( $order ), // from
			'cancel_url_override'   => $order->get_cancel_order_url(),
			'callback_url_override' => add_query_arg( 'wc-api', 'monri_callback', get_home_url() )
		);

		Monri_WC_Logger::log( "Request data: " . print_r( $args, true ), __METHOD__ );

		wc_get_template( 'redirect-form.php', [
			'action'  => $this->payment->get_option_bool( 'test_mode' ) ? self::ENDPOINT_TEST : self::ENDPOINT,
			'options' => $args,
			'order'   => $order
		], basename( MONRI_WC_PLUGIN_PATH ), MONRI_WC_PLUGIN_PATH . 'templates/' );
	}

	/**
	 * In some cases page url is rewritten and it contains page path and query string.
	 *
	 * @return string
	 */
	private function get_query_string() {
		$arr = explode( '?', $_SERVER['REQUEST_URI'] );
		// If there's more than one '?' shift and join with ?, it's special case of having '?' in success url
		// eg http://testiranjeintegracija.net/?page_id=6order-recieved?

		if ( count( $arr ) > 2 ) {
			array_shift( $arr );

			return implode( '?', $arr );
		}

		return end( $arr );
	}

	/**
	 * Monri server callback on thankyou page
	 *
	 * @return void
	 */
	public function process_return() {

		Monri_WC_Logger::log( "Response data: " . print_r( $_REQUEST, true ), __METHOD__ );
		$order_id = $_REQUEST['order_number'];

        $domain = 'monri';

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( $order->get_payment_method() !== $this->payment->id ) {
			return;
		}

		if ( $order->get_status() === 'completed' ) {
			return;
		}

		if ( empty( $_REQUEST['approval_code'] ) || empty( $_REQUEST['digest'] ) ) {
			$order->update_status( 'failed' );

			return;
		}

		//wp_enqueue_style('thankyou-page', plugins_url() . '/woocommerce-monri/assets/style/thankyou-page.css');

		try {

			$digest        = $_REQUEST['digest'];
			$response_code = $_REQUEST['response_code'];

			$thankyou_page = $this->payment->get_return_url( $order );
			$url           = strtok( $thankyou_page, '?' );

			$query_string = $this->get_query_string();
			$full_url     = $url . '?' . $query_string;

			$calculated_url = preg_replace( '/&digest=[^&]*/', '', $full_url );
			//Generate digest
			$check_digest = hash( 'sha512', $this->payment->get_option( 'monri_merchant_key' ) . $calculated_url );

			if ( $digest !== $check_digest ) {
				$order->update_status( 'failed', 'Mismatch between digest and calculated digest' );

				return;
			}

			if ( $response_code === "0000" ) {

				if ( $order->get_status() !== 'processing' ) {
					$order->payment_complete();
					$order->add_order_note( __("Monri payment successful<br/>Approval code: ", $domain) . $_REQUEST['approval_code'] );
					$order->add_order_note( __('Thank you for shopping with us. Your account has been charged and your transaction is successful. We will be shipping your order to you soon.', $domain) );
					$order->add_order_note( "Issuer: " . $_REQUEST['issuer'] );

					if ( $_REQUEST['number_of_installments'] > 1 ) {
						$order->add_order_note( __('Number of installments: ') . $_REQUEST['number_of_installments'] );
					}

                    WC()->cart->empty_cart();
                }

            } else if ( $response_code === "pending" ) {
                $order->add_order_note(__("Monri payment status is pending<br/>Approval code: ", $domain) . $_REQUEST['approval_code']);
                $order->add_order_note(__('Thank you for shopping with us. Right now your payment status is pending, We will keep you posted regarding the status of your order through e-mail', $domain));
                $order->add_order_note("Issuer: " . $_REQUEST['issuer']);

                if ($_REQUEST['number_of_installments'] > 1) {
                    $order->add_order_note(__('Number of installments:', $domain) . ": " . $_REQUEST['number_of_installments']);
                }

                $order->update_status( 'on-hold' );
                WC()->cart->empty_cart();

            }  else {
                $order->update_status('failed', 'Response not authorized');
                $order->add_order_note(__('Transaction Declined: ', $domain) . $_REQUEST['Error']);
			}

		} catch ( Exception $e ) {
			Monri_WC_Logger::log( "Error while processing response for order $order_id: " . $e->getMessage(),
				__METHOD__
			);

			$order->update_status( 'failed', 'Error while checking form response' );
		}

	}
}
