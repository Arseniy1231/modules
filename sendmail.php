<?php
class ControllerInformationSendMail extends Controller {
	private $error = array();

  	public function index() {
		$this->language->load('information/sendmail');

		$error = array();
    	if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {			
			$subject = sprintf($this->language->get('email_subject'), $this->config->get('config_name'));
			$message = "";

			foreach($this->request->post as $key => $post_variable){				$message .= sprintf($this->language->get('email_message_'.$key), $this->request->post[$key]);			}

			if(array_key_exists('phone', $this->request->post)){
				$subject = sprintf($this->language->get('email_tel_subject'), $this->config->get('config_name'));
			}
			
			if(array_key_exists('product', $this->request->post)){
				$subject = sprintf($this->language->get('email_product_subject'), $this->request->post['product']);
			}
			
			// create order if user is logged
			if(isset($this->request->post['product_id'])){
				$this->addOrder();
			}else{
				$mail = new Mail();
				$mail->protocol = $this->config->get('config_mail_protocol');
				$mail->parameter = $this->config->get('config_mail_parameter');
				$mail->hostname = $this->config->get('config_smtp_host');
				$mail->username = $this->config->get('config_smtp_username');
				$mail->password = $this->config->get('config_smtp_password');
				$mail->port = $this->config->get('config_smtp_port');
				$mail->timeout = $this->config->get('config_smtp_timeout');
				$mail->setTo($this->config->get('config_email'));
				$mail->setFrom("order@deesse.com.ua");
				$mail->setSender($this->config->get('config_name'));
				$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));				$mail->setText(strip_tags(html_entity_decode($message, ENT_QUOTES, 'UTF-8')));				$mail->send();
			}

			$error['success'] = true;
			$error['response'] = $this->language->get('email_done');
    	}else{

	 		if (isset($this->error) && count($this->error) > 0) {
				$error = $this->error;
			}
  		}
  		$this->response->setOutput(json_encode($error));

  	}

  	private function validate() {       	if($this->request->post['phone'] == '') {
      		$this->error['phone'] = $this->language->get('error_phone');
    	}
		if (!$this->error) {
	  		return true;
		} else {
	  		return false;
		}
  	}
	
	private function addOrder() {
		$data = array();

		$data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
		$data['store_id'] = $this->config->get('config_store_id');
		$data['store_name'] = $this->config->get('config_name');

		if ($data['store_id']) {
			$data['store_url'] = $this->config->get('config_url');
		} else {
			$data['store_url'] = HTTP_SERVER;
		}
		
		if($this->customer->isLogged()){
			$data['customer_id'] = $this->customer->getId();
			$data['customer_group_id'] = $this->customer->getGroupId();
			$data['firstname'] = $this->customer->getFirstName();
			$data['lastname'] = $this->customer->getLastName();
			$data['email'] = $this->customer->getEmail();
			$data['telephone'] = $this->customer->getTelephone();
			$data['fax'] = $this->customer->getFax();
		}else{
			// try to get customer by telephone
			$this->load->model('account/customer');
			$customer = $this->model_account_customer->getCustomerByTelephone($this->request->post['phone']);

			if($customer){
				$data['customer_id'] = $customer['customer_id'];
				$data['customer_group_id'] = $customer['customer_group_id'];
				$data['firstname'] = $customer['firstname'];
				$data['lastname'] = $customer['lastname'];
				$data['email'] = $customer['email'];
				$data['telephone'] = $customer['telephone'];
				$data['fax'] = $customer['fax'];
			}else{
				$data['customer_id'] = 0;
				$data['customer_group_id'] = 0;
				$data['firstname'] = 'Уважаемый';
				$data['lastname'] = 'покупатель';
				$data['email'] = 'order@deesse.com.ua';
				$data['telephone'] = $this->request->post['phone'];
				$data['fax'] = '';
			}
		}
		
		$data['payment_firstname'] = '';
		$data['payment_lastname'] = '';
		$data['payment_company'] = '';
		$data['payment_address_1'] = '';
		$data['payment_address_2'] = '';
		$data['payment_city'] = '';
		$data['payment_postcode'] = '';
		$data['payment_country'] = '';
		$data['payment_country_id'] = '';
		$data['payment_zone'] = '';
		$data['payment_zone_id'] = '';
		$data['payment_address_format'] = '';

		$data['payment_method'] = '';
		$data['payment_code'] = '';

		$data['shipping_firstname'] =  '';
		$data['shipping_lastname'] =  '';
		$data['shipping_company'] =  '';
		$data['shipping_address_1'] =  '';
		$data['shipping_address_2'] =  '';
		$data['shipping_city'] =  '';
		$data['shipping_postcode'] =  '';
		$data['shipping_country'] =  '';
		$data['shipping_country_id'] =  '';
		$data['shipping_zone'] =  '';
		$data['shipping_zone_id'] =  '';
		$data['shipping_address_format'] =  '';

		$data['shipping_code'] = '';
		$data['shipping_method'] = '';
		
		$data['comment'] = '';

		$product_data = array();
		if (isset($this->request->post['product_id'])) {
			$this->load->model('catalog/product');
			$product = $this->model_catalog_product->getProduct((int)$this->request->post['product_id']);

			if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
				$price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'), $format = false);
			} else {
				$price = false;
			}
			
			if ((float)$product['special']) {
				$special = $this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax'));
			} else {
				$special = false;
			}
			
			if (!$special) {
				$lastprice = $price;
			} else {
				$lastprice = $special;
			}
			
			$order_product_quantity = ($product['minimum'] > 1) ? $product['minimum'] : 1;
		}

		$option_data = array();

		/*
		foreach ($product['option'] as $option) {
			if ($option['type'] != 'file') {
				$value = $option['option_value'];
			} else {
				$value = $this->encryption->decrypt($option['option_value']);
			}

			$option_data[] = array(
				'product_option_id'       => $option['product_option_id'],
				'product_option_value_id' => $option['product_option_value_id'],
				'option_id'               => $option['option_id'],
				'option_value_id'         => $option['option_value_id'],
				'name'                    => $option['name'],
				'value'                   => $value,
				'type'                    => $option['type']
			);
		}
		*/

		$download_data = array();

		$product_data[] = array(
			'product_id' => $product['product_id'],
			'name'       => $product['name'],
			'model'      => $product['model'],
			'quantity'   => $order_product_quantity,
			'price'      => $lastprice,
			'total'      => $lastprice * $order_product_quantity,
			'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
			'reward'     => $product['reward'],
			'option'     => $option_data,
		);

		$data['products'] = $product_data;

		$voucher_data = array();
		$data['vouchers'] = $voucher_data;

		$total_data = array();
		$data['totals'] = $total_data;

		$total = $lastprice * $order_product_quantity;
		$data['total'] = $total;  //

		if (isset($this->request->cookie['tracking'])) {
			$this->load->model('affiliate/affiliate');

			$affiliate_info = $this->model_affiliate_affiliate->getAffiliateByCode($this->request->cookie['tracking']);

			if ($affiliate_info) {
				$data['affiliate_id'] = $affiliate_info['affiliate_id'];
				$data['commission'] = ($total / 100) * $affiliate_info['commission'];
			} else {
				$data['affiliate_id'] = 0;
				$data['commission'] = 0;
			}
		} else {
			$data['affiliate_id'] = 0;
			$data['commission'] = 0;
		}

		$data['marketing_id'] = 0;
		$data['tracking'] = '';

		$data['language_id'] = $this->config->get('config_language_id');
		
		$data['currency_code'] = $this->session->data['currency'];
		
		$data['currency_id'] = $this->currency->getId($this->session->data['currency']);
		$data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
		
		$data['ip'] = $this->request->server['REMOTE_ADDR'];

		if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
			$data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
		} elseif(!empty($this->request->server['HTTP_CLIENT_IP'])) {
			$data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
		} else {
			$data['forwarded_ip'] = '';
		}

		if (isset($this->request->server['HTTP_USER_AGENT'])) {
			$data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
		} else {
			$data['user_agent'] = '';
		}

		if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
			$data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
		} else {
			$data['accept_language'] = '';
		}

		$this->load->model('checkout/order');

		$this->session->data['order_id'] = $this->model_checkout_order->addOrder($data);

		$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('cod_order_status_id'));

		// OCU TurboSMS [BEGIN]
        if (true === $this->ocu_turbo_sms_init() && $this->config->get('ocu_turbo_sms_admin_new_order')) {
            $this->ocu_turbo_sms_gateway->send(preg_replace("/[^0-9]/", "", $this->config->get('config_telephone')),
                                              strip_tags(sprintf($this->language->get('ocu_turbo_sms_message_admin_new_order'), $this->session->data['order_id'], $data['firstname'] .' ' . $data['lastname'], $data['telephone'], $this->currency->format($data['total'], $this->session->data['currency']), $product['name'] )));
        }

        if (true === $this->ocu_turbo_sms_init() && $this->config->get('ocu_turbo_sms_customer_new_order')) {
            $this->ocu_turbo_sms_gateway->send(preg_replace("/[^0-9]/", "", $data['telephone']),
                                              strip_tags(sprintf($this->language->get('ocu_turbo_sms_message_customer_new_order'), $data['firstname']. ' ' .$data['lastname'], $this->session->data['order_id'], $this->currency->format($data['total'], $this->session->data['currency']) )));
        }
  		// OCU TurboSMS [END]

        // to not rewrited order_id if was previous usual order (start)
        if (isset($this->session->data['order_id'])) {
            $this->cart->clear();
            unset($this->session->data['order_id']);
        }
        // to not rewrited order_id if was previous usual order (end)
	}

}