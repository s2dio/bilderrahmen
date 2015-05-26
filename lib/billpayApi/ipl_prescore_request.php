<?php

require_once(dirname(__FILE__).'/ipl_xml_request.php');

/**
 * @author Jan Wehrs (jan.wehrs@billpay.de)
 * @copyright Copyright 2010 Billpay GmbH
 * @license commercial
 */
class ipl_prescore_request extends ipl_xml_request {
	private $_customer_details 		= array();
	private $_shippping_details 	= array();
	private $_totals 				= array();
//	private $_bank_account 			= array();

	private $_article_data 			= array();
	private $_order_history_data 	= array();
	private $_company_details		= array();

	private $_payment_info_params	= array();
	private $_fraud_detection		= array();


	private $_payment_type;

	private $status;
	private $bptid;

	private $corrected_street;
	private $corrected_street_no;
	private $corrected_zip;
	private $corrected_city;
	private $corrected_country;

	// parameters needed for auto-capture
//	private $account_holder;
//	private $account_number;
//	private $bank_code;
//	private $bank_name;
	private $invoice_reference;
	private $invoice_duedate;

	private $_terms_accepted = false;
	private $_capture_request_necessary = true;
	private $_expected_days_till_shipping = 0;

	private $standard_information_pdf;
	private $email_attachment_pdf;

	private $payment_info_html;
	private $payment_info_plain;

	private $_payments_allowed	= array();
	private $_rate_info	= array();
	private $_payments_allowed_all	= array();


	// ctr
	function __construct($ipl_request_url) {
		//$this->_payment_type = $payment_type;
		parent::__construct($ipl_request_url);
	}

	public function get_terms_accepted() {
		return $this->_terms_accepted;
	}
	public function set_terms_accepted($val) {
		$this->_terms_accepted = $val;
	}
	public function set_expected_days_till_shipping($val) {
		$this->_expected_days_till_shipping = $val;
	}
	public function set_capture_request_necessary($val) {
		$this->_capture_request_necessary = $val;
	}

	public function get_expected_days_till_shipping() {
		return $this->_expected_days_till_shipping;
	}
	public function get_capture_request_nesessary() {
		return $this->_capture_request_necessary;
	}
	public function get_payment_type() {
		return $this->_payment_type;
	}
	public function get_status() {
		return $this->status;
	}
	public function get_bptid() {
		return $this->bptid;
	}
	public function get_corrected_street() {
		return $this->corrected_street;
	}
	public function get_corrected_street_no() {
		return $this->corrected_street_no;
	}
	public function get_corrected_zip() {
		return $this->corrected_zip;
	}
	public function get_corrected_city() {
		return $this->corrected_city;
	}
	public function get_corrected_country() {
		return $this->corrected_country;
	}
	public function get_account_holder() {
		return $this->account_holder;
	}
	public function get_account_number() {
		return $this->account_number;
	}
	public function get_bank_code() {
		return $this->bank_code;
	}
	public function get_bank_name() {
		return $this->bank_name;
	}
	public function get_invoice_reference() {
		return $this->invoice_reference;
	}
	public function get_invoice_duedate() {
		return $this->invoice_duedate;
	}
	public function get_standard_information_pdf() {
		return $this->standard_information_pdf;
	}
	public function get_email_attachment_pdf() {
		return $this->email_attachment_pdf;
	}
	public function get_payment_info_html() {
		return $this->payment_info_html;
	}
	public function get_payment_info_plain() {
		return $this->payment_info_plain;
	}

	public function get_payments_allowed_all() {
	    return $this->_payments_allowed_all;
	}

	public function get_payments_allowed() {
	    return $this->_payments_allowed;
	}

	public function get_rate_info() {
	    return $this->_rate_info;
	}

	public function get_terms() {
	    return $this->_terms;
	}


	public function set_customer_details($customer_id, $customer_type, $salutation, $title,
		$first_name, $last_name, $street, $street_no, $address_addition, $zip,
		$city, $country, $email, $phone, $cell_phone, $birthday, $language, $ip, $customerGroup) {

			$this->_customer_details['customerid'] 			= $customer_id;
			$this->_customer_details['customertype'] 		= $customer_type;
			$this->_customer_details['salutation'] 			= $salutation;
			$this->_customer_details['title'] 				= $title;
			$this->_customer_details['firstName']			= $first_name;
			$this->_customer_details['lastName'] 			= $last_name;
			$this->_customer_details['street'] 				= $street;
			$this->_customer_details['streetNo'] 			= $street_no;
			$this->_customer_details['addressAddition'] 	= $address_addition;
			$this->_customer_details['zip'] 				= $zip;
			$this->_customer_details['city'] 				= $city;
			$this->_customer_details['country'] 			= $country;
			$this->_customer_details['email'] 				= $email;
			$this->_customer_details['phone'] 				= $phone;
			$this->_customer_details['cellPhone'] 			= $cell_phone;
			$this->_customer_details['birthday'] 			= $birthday;
			$this->_customer_details['language'] 			= $language;
			$this->_customer_details['ip'] 					= $ip;
			$this->_customer_details['customerGroup']		= $customerGroup;
	}


	public function set_shipping_details($use_billing_address, $salutation=null, $title=null, $first_name=null, $last_name=null,
		$street=null, $street_no=null, $address_addition=null, $zip=null, $city=null, $country=null, $phone=null, $cell_phone=null) {

			$this->_shippping_details['useBillingAddress'] 	= $use_billing_address ? '1' : '0';
			$this->_shippping_details['salutation'] 		= $salutation;
			$this->_shippping_details['title'] 				= $title;
			$this->_shippping_details['firstName'] 			= $first_name;
			$this->_shippping_details['lastName'] 			= $last_name;
			$this->_shippping_details['street'] 			= $street;
			$this->_shippping_details['streetNo'] 			= $street_no;
			$this->_shippping_details['addressAddition'] 	= $address_addition;
			$this->_shippping_details['zip'] 				= $zip;
			$this->_shippping_details['city'] 				= $city;
			$this->_shippping_details['country'] 			= $country;
			$this->_shippping_details['phone'] 				= $phone;
			$this->_shippping_details['cellPhone'] 			= $cell_phone;
	}

	public function add_article($articleid, $articlequantity, $articlename, $articledescription,
		$article_price, $article_price_gross) {
			$article = array();
			$article['articleid'] 			= $articleid;
			$article['articlequantity'] 	= $articlequantity;
			$article['articlename'] 		= $articlename;
			$article['articledescription'] 	= $articledescription;
			$article['articleprice'] 		= $article_price;
			$article['articlepricegross'] 	= $article_price_gross;

			$this->_article_data[] = $article;
	}


	public function add_order_history($horderid, $hdate, $hamount, $hcurrency, $hpaymenttype, $hstatus) {
		$histOrder = array();
		$histOrder['horderid'] 		= $horderid;
		$histOrder['hdate'] 		= $hdate;
		$histOrder['hamount'] 		= $hamount;
		$histOrder['hcurrency'] 	= $hcurrency;
		$histOrder['hpaymenttype'] 	= $hpaymenttype;
		$histOrder['hstatus'] 		= $hstatus;

		$this->_order_history_data[] = $histOrder;
	}


	public function set_total($rebate, $rebate_gross, $shipping_name, $shipping_price,
			$shipping_price_gross, $cart_total_price, $cart_total_price_gross,
			$currency) {
		$this->_totals['shippingname'] 			= $shipping_name;
		$this->_totals['shippingprice'] 		= $shipping_price;
		$this->_totals['shippingpricegross'] 	= $shipping_price_gross;
		$this->_totals['rebate']				= $rebate;
		$this->_totals['rebategross'] 			= $rebate_gross;
		$this->_totals['carttotalprice'] 		= $cart_total_price;
		$this->_totals['carttotalpricegross'] 	= $cart_total_price_gross;
		$this->_totals['currency'] 				= $currency;
	}

	public function set_bank_account($account_holder, $account_number, $sort_code) {
		$this->_bank_account['accountholder'] 	= $account_holder;
		$this->_bank_account['accountnumber'] 	= $account_number;
		$this->_bank_account['sortcode'] 		= $sort_code;
	}

	public function set_company_details($name, $legalForm, $registerNumber, $holderName, $taxNumber) {
		$this->_company_details['name'] 			= $name;
		$this->_company_details['legalForm'] 		= $legalForm;
		$this->_company_details['registerNumber'] 	= $registerNumber;
		$this->_company_details['holderName'] 		= $holderName;
		$this->_company_details['taxNumber'] 		= $taxNumber;
	}

	public function set_payment_info_params($showhtmlinfo, $showplaininfo) {
		$this->_payment_info_params['htmlinfo'] = $showhtmlinfo ? "1" : "0";
		$this->_payment_info_params['plaininfo'] = $showplaininfo ? "1" : "0";
	}

	public function set_fraud_detection($session_id) {
		$this->_fraud_detection['session_id'] = $session_id;
	}


	protected function _send() {
		$attributes = array();

		return ipl_core_send_prescore_request(
			$this->_ipl_request_url,
			$attributes,
			$this->_default_params,
			$this->_customer_details,
			$this->_shippping_details,
			/* $this->_bank_account, */
			$this->_totals,
			$this->_article_data,
			$this->_order_history_data,
			$this->_company_details,
			$this->_payment_info_params,
			$this->_fraud_detection
		);
	}

	protected function _process_response_xml($data) {
		foreach ($data as $key => $value) {
			$this->$key = $value;
		}
	}

	protected function _process_error_response_xml($data) {
		if (key_exists('status', $data)) {
			$this->status = $data['status'];
		}
	}
}

?>