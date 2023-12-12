<?php
require 'vendor/autoload.php';
require 'lib.php';

use GuzzleHttp\Client;

class WC_EasyLink_Payment_Gateway extends WC_Payment_Gateway
{

	private $order_status;
	private $accountType;
	private $pin;
	private $secPin;
	private $scretKey;
	private $channelID;
	private $channelURL;
	private $channelType;
	private $remark;
	private $merchantsName;
	private $apiDomain;
	private $frontendUrl;
	public function __construct()
	{
		$this->id = 'other_payment';
		$this->method_title = __('EasyLink Payment', 'woocommerce-other-payment-gateway');
		$this->title = __('EasyLink Payment', 'woocommerce-other-payment-gateway');
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->enabled = $this->get_option('enabled');
		$this->title = 'EasyLink';
		$this->order_status = $this->get_option('order_status');
		$this->pin = $this->get_option('pin');
		$this->secPin = $this->get_option('secPin');
		$this->scretKey = $this->get_option('scretKey');
		$this->channelID = $this->get_option('channelID');
		$this->channelURL = $this->get_option('channelURL');
		$this->remark = $this->get_option('remark');
		$this->accountType = $this->get_option('accountType');
		$this->merchantsName = $this->get_option('merchantsName');
		$this->channelType = $this->get_option('channelType');
		$this->apiDomain = $this->get_option('apiDomain');
		$this->frontendUrl = $this->get_option('frontendUrl');
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
	}

	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled' => array(
				'title' 		=> __('Enable/Disable', 'woocommerce-other-payment-gateway'),
				'type' 			=> 'checkbox',
				'label' 		=> __('Enable EasyLink', 'woocommerce-other-payment-gateway'),
				'default' 		=> 'yes'
			),
			'order_status' => array(
				'title' => __('Order Status After The Checkout', 'woocommerce-other-payment-gateway'),
				'type' => 'select',
				'options' => wc_get_order_statuses(),
				'default' => 'wc-on-hold',
				'description' 	=> __('The default order status if this gateway used in payment.', 'woocommerce-other-payment-gateway'),
			),
			'pin' => array(
				'title' => __('Pin', 'woocommerce-other-payment-gateway'),
				'type' => 'text',
				'description' 	=> __('The default order status if this gateway used in payment.', 'woocommerce-other-payment-gateway'),
			),
			'secPin' => array(
				'title' => __('Sec Pin', 'woocommerce-other-payment-gateway'),
				'type' => 'text',
				'description' 	=> __('The default order status if this gateway used in payment.', 'woocommerce-other-payment-gateway'),
			),
			'scretKey' => array(
				'title' => __('Secret Key', 'woocommerce-other-payment-gateway'),
				'type' => 'text',
				'description' 	=> __('The default order status if this gateway used in payment.', 'woocommerce-other-payment-gateway'),
			),
			'channelID' => array(
				'title' => __('Channel ID', 'woocommerce-other-payment-gateway'),
				'type' => 'text',
				'description' 	=> __('The default order status if this gateway used in payment.', 'woocommerce-other-payment-gateway'),
			),
			'channelURL' => array(
				'title' => __('Channel URL', 'woocommerce-other-payment-gateway'),
				'type' => 'text',
				'description' 	=> __('The default order status if this gateway used in payment.', 'woocommerce-other-payment-gateway'),
			),
			'remark' => array(
				'title' => __('Remark', 'woocommerce-other-payment-gateway'),
				'default' => 'UPOP-HKD',
				'type' => 'text',
				'description' 	=> __('The default order status if this gateway used in payment.', 'woocommerce-other-payment-gateway'),
			),
			'accountType' => array(
				'title' => __('Account type', 'woocommerce-other-payment-gateway'),
				'type' => 'select',
				'options' => array('Test', 'Live'),
				'default' => 'Test',
			),
			'channelType' => array(
				'title' => __('Channel type', 'woocommerce-other-payment-gateway'),
				'type' => 'select',
				'options' => array('CNY', 'HKD'),
				'default' => 'HKD',
			),
			'merchantsName' => array(
				'title' => __('Merchant\'s Name', 'woocommerce-other-payment-gateway'),
				'type' => 'text',
				'description' 	=> __('The default order status if this gateway used in payment.', 'woocommerce-other-payment-gateway'),
			),
			'apiDomain' => array(
				'title' => __('API Domain', 'woocommerce-other-payment-gateway'),
				'type' => 'text',
				'description' 	=> __('The default order status if this gateway used in payment.', 'woocommerce-other-payment-gateway'),
			),
			'frontendUrl' => array(
				'title' => __('Frontend URL', 'woocommerce-other-payment-gateway'),
				'type' => 'text',
				'description' 	=> __('The default order status if this gateway used in payment.', 'woocommerce-other-payment-gateway'),
			)
		);
	}

	public function process_payment($order_id)
	{
		global $woocommerce;
		$order = new WC_Order($order_id);
		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status($this->order_status, __('Awaiting payment', 'woocommerce-other-payment-gateway'));
		// Reduce stock levels
		wc_reduce_stock_levels($order_id);
		if (isset($_POST[$this->id . '-admin-note']) && trim($_POST[$this->id . '-admin-note']) != '') {
			$order->add_order_note(esc_html($_POST[$this->id . '-admin-note']));
		}
		// Remove cart
		$woocommerce->cart->empty_cart();
		// Return thankyou redirect
		$apiUrl = $this->channelURL;
		$payload = array(
			"pin" => $this->pin,
			"secPin" => $this->secPin,
			"amount" => $order->get_total(),
			"orderCreateTime" => $order->get_date_created()->format('Y-m-d H:i:s'),
			"orderId" => "warmyellow-" . $order->get_id(),
			"frontendUrl" => $this->frontendUrl,
			"channel" => $this->channelID,
			"customerIp" => "127.0.0.1",
			"callbackUrl" => "https://shop.warmyellow.com",
			"frontendUrl" => $this->get_return_url()
		);
		$accessKey = sign($payload, $this->scretKey);
		$payload["accessKey"] = $accessKey;
		$payload["merchantCardNumber"] = "6250947000000014";
		$client = new Client([
			'base_uri' => $apiUrl,
			'timeout'  => 5.0,
		]);
		$response = $client->request('POST', $apiUrl, [
			'form_params' => $payload
		]);
		$body = $response->getBody();
		$j = json_decode($body, true);
		return array(
			'result' => 'success',
			'redirect' => $j['url']
		);
	}
}
