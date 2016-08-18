<?php
namespace Latrell\Alipay\Mobile;

class SdkPayment
{

	private $__https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';

	private $__http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';

	private $service = 'mobile.securitypay.pay';

	private $partner;

	private $_input_charset = 'UTF-8';

	private $sign_type = 'RSA';

	private $private_key_path;

	private $public_key_path;

	private $notify_url;

	private $out_trade_no;

	private $subject;

	private $payment_type = 1;

	private $seller_id;

	private $total_fee;

	private $body;

	private $show_url;

	private $anti_phishing_key;

	private $exter_invoke_ip;

	private $key;

	private $transport;

	private $cacert;

	public function __construct()
	{
		$this->cacert = getcwd() . DIRECTORY_SEPARATOR .'cacert.pem';
	}

	/**
	 * Get Pay Link
	 */
	public function getPayPara()
	{
		$parameter = array(
			'service' => $this->service,
			'partner' => trim($this->partner),
			'payment_type' => $this->payment_type,
			'notify_url' => $this->notify_url,
			'seller_id' => $this->seller_id,
			'out_trade_no' => $this->out_trade_no,
			'subject' => $this->subject,
			'total_fee' => $this->total_fee,
			'body' => $this->body,
			'show_url' => $this->show_url,
			'anti_phishing_key' => $this->anti_phishing_key,
			'exter_invoke_ip' => $this->exter_invoke_ip,
			'_input_charset' => trim(strtolower($this->_input_charset))
		);

		$para = $this->buildRequestPara($parameter);

		return $this->createLinkstringUrlencode($para);
	}

	/**
	 * Verify If the message is sent from Alipay and its validity
	 */
	public function verify()
	{
		// Verify if the request is null
		if (empty($_POST) && empty($_GET)) {
			return false;
		}

		$data = $_POST ?  : $_GET;

		// Generate the signature
		$is_sign = $this->getSignVeryfy($data, $data['sign']);

		// Get the ATN result of Alipay Servers (Verify if messages are from Alipay)
		$response_txt = 'true';
		if (! empty($data['notify_id'])) {
			$response_txt = $this->getResponse($data['notify_id']);
		}

		// Verify
		// If the result of $response_txt is not true, it's related to server configuration, Partner ID, notify_id expiring in 1min.
		// if the result of isSign is not true, it's related to Security code, parameters in request(eg. customized parameters), encoding format etc.
		if (preg_match('/true$/i', $response_txt) && $is_sign) {
			return true;
		} else {
			return false;
		}
	}

	public function setBody($body)
	{
		$this->body = $body;
		return $this;
	}

	public function setNotifyUrl($notify_url)
	{
		$this->notify_url = $notify_url;
		return $this;
	}

	public function setOutTradeNo($out_trade_no)
	{
		$this->out_trade_no = $out_trade_no;
		return $this;
	}

	public function setPartner($partner)
	{
		$this->partner = $partner;
		return $this;
	}

	public function setPrivateKeyPath($private_key_path)
	{
		$this->private_key_path = $private_key_path;
		return $this;
	}

	public function setPublicKeyPath($public_key_path)
	{
		$this->public_key_path = $public_key_path;
		return $this;
	}

	public function setSellerId($seller_id)
	{
		$this->seller_id = $seller_id;
		return $this;
	}

	public function setSubject($subject)
	{
		$this->subject = $subject;
		return $this;
	}

	public function setTotalFee($total_fee)
	{
		$this->total_fee = $total_fee;
		return $this;
	}

	public function setSignType($sign_type)
	{
		$this->sign_type = $sign_type;
		return $this;
	}

	public function setCacert($cacert)
	{
		$this->cacert = $cacert;
		return $this;
	}

	/**
	 * Generate the parameters to submit to Alipay
	 * @param $para_temp is parameter array before request
	 * @return is the parameter array to be requested
	 */
	private function buildRequestPara($para_temp)
	{
		//Remove the null and signature parameters in parameter array that is to be signed
		$para_filter = $this->paraFilter($para_temp);

		//Sort orders for parameter array that is to be signed
		$para_sort = $this->argSort($para_filter);

		//Generate the Signature
		$mysign = $this->buildRequestMysign($para_sort);

		//Add sign result and sign type to parameter array
		$para_sort['sign'] = $mysign;
		$para_sort['sign_type'] = strtoupper(trim($this->sign_type));

		return $para_sort;
	}

	/**
	 * Generate the Signature
	 * @param $para_sort is the array that already sorted but need the signature
	 * return is the string of signature
	 */
	private function buildRequestMysign($para_sort)
	{
		//Put all elements in array into the string with format of "Parameter = Value", connecting with "&".
		$prestr = $this->createLinkstring($para_sort);

		$mysign = '';
		switch (strtoupper(trim($this->sign_type))) {
			case 'MD5':
				$mysign = $this->md5Sign($prestr, $this->key);
				break;
			case 'RSA':
				$mysign = $this->rsaSign($prestr, trim($this->private_key_path));
				break;
			default:
				$mysign = '';
		}

		return $mysign;
	}

	/**
	 * Get the signature verifcation result in returning
	 * @param $para_temp is the returned parameter array (通知返回来的参数数组)
	 * @param $sign is the returned signature result
	 * @return is the signature verification result
	 */
	function getSignVeryfy($para_temp, $sign)
	{
		//Remove the null and signatures parameters for parameter array thta is to be signed
		$para_filter = $this->paraFilter($para_temp);

		//Sort order for parameters array that is to be signed
		$para_sort = $this->argSort($para_filter);

		//Put all elements in array into the string with format of "Parameter = Value", connecting with "&". 
		$prestr = $this->createLinkstring($para_sort);

		$is_sgin = false;
		switch (strtoupper(trim($this->sign_type))) {
			case 'MD5':
				$is_sgin = $this->md5Verify($prestr, $sign, $this->key);
				break;
			case 'RSA':
				$is_sgin = $this->rsaVerify($prestr, $this->public_key_path, $sign);
				break;
			default:
				$is_sgin = false;
		}

		return $is_sgin;
	}

	/**
	 * Remove the null and signature parameters in array
	 * @param $para is the Signature parameters array
	 * return The new Signature parameters array after removing null and signature parameters
	 */
	private function paraFilter($para)
	{
		$para_filter = array();
		while ((list ($key, $val) = each($para)) == true) {
			if ($key == 'sign' || $key == 'sign_type' || $val == '') {
				continue;
			} else {
				$para_filter[$key] = $para[$key];
			}
		}
		return $para_filter;
	}

	/**
	 * Sort Orders For Array
	 * @param $para is the array before sorting orders
	 * return is the array after sorting orders
	 */
	private function argSort($para)
	{
		ksort($para);
		reset($para);
		return $para;
	}

	/**
	 * RSA Signature Verification
	 * @param $data is the data to be signed
	 * @param $ali_public_key_path is the Alipay Public Key path
	 * @param $sign is the signature result to be verified
	 * return is verification result
	 */
	private function rsaVerify($data, $public_key_path, $sign)
	{
		$pubKey = file_get_contents($public_key_path);
		$res = openssl_get_publickey($pubKey);
		$result = (bool) openssl_verify($data, base64_decode($sign), $res);
		openssl_free_key($res);
		return $result;
	}

	/**
	 * RSA Signature
	 * @param $data is the data to be signed
	 * @param $private_key_path is the path of merchant private key
	 * return is signature result
	 */
	private function rsaSign($data, $private_key_path)
	{
		$priKey = file_get_contents($private_key_path);
		$res = openssl_get_privatekey($priKey);
		openssl_sign($data, $sign, $res);
		openssl_free_key($res);
		//base64 encoding
		$sign = base64_encode($sign);
		return $sign;
	}

	/**
	 * Put all elements in array into the string with format of "Parameter = Value", connecting with "&".
	 * @param $para is the array to be put into string
	 * return is the string
	 */
	private function createLinkstring($para)
	{
		$arg = '';
		while ((list ($key, $val) = each($para)) == true) {
			$arg .= $key . '=' . $val . '&';
		}
		//Remove the Last "&"
		$arg = substr($arg, 0, count($arg) - 2);

		//If there's any slashes, remove slashes
		if (get_magic_quotes_gpc()) {
			$arg = stripslashes($arg);
		}

		return $arg;
	}

	/**
	 * Put all elements in array into the string with format of "Parameter = Value", connecting with "&". and make the string into urlencode.
	 * @param $para is the array to be put into string
	 * return is the string
	 */
	private function createLinkstringUrlencode($para)
	{
		$arg = '';
		while ((list ($key, $val) = each($para)) == true) {
			$arg .= $key . '=' . urlencode($val) . '&';
		}
		//Remove the last "&"
		$arg = substr($arg, 0, count($arg) - 2);

		//If there's any slashes, remove slashes
		if (get_magic_quotes_gpc()) {
			$arg = stripslashes($arg);
		}

		return $arg;
	}

	/**
	 * Get ATN result from remote servers, verify it and return to URL
	 * @param $notify_id is the notify ID
	 * @return is the ATN result from remote servers
	 * Verification Results:
	 * invalid - Parameters incorrect, pleaes check if  partner and key in return data are null.
	 * true - Return correct info
	 * false - Please check firewall or server ports, and confirm if the verify time has been over 1 min.
	 */
	private function getResponse($notify_id)
	{
		$transport = strtolower(trim($this->transport));
		$partner = trim($this->partner);
		$veryfy_url = '';
		if ($transport == 'https') {
			$veryfy_url = $this->__https_verify_url;
		} else {
			$veryfy_url = $this->__http_verify_url;
		}
		$veryfy_url = $veryfy_url . 'partner=' . $partner . '&notify_id=' . $notify_id;
		$response_txt = $this->getHttpResponseGET($veryfy_url, $this->cacert);

		return $response_txt;
	}

	/**
	 * Get data remotely - GET mode
	 * Notice: 
	 * 1.To use curl you need to change configuration in php.ini on your server, find php_curl.dll and uncomment it
	 * 2.cacert.pem is the SSL cert and please make sure the path for it is correct, the default path is getcwd().'\\cacert.pem'
	 * @param $url is to Specify URL full path address
	 * @param $cacert_url is to Specify the absolute path of the current working directory
	 * return is the data output remotely
	 */
	private function getHttpResponseGET($url, $cacert_url)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, 0); // Filter HTTP header
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // Show Output
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true); //SSL Cert Verification
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); //Strict Verification
		curl_setopt($curl, CURLOPT_CAINFO, $cacert_url); //Cert location
		$responseText = curl_exec($curl);
		//var_dump( curl_error($curl) );//If you get any problem when excuting curl you can enable it for better troubleshooting.
		curl_close($curl);

		return $responseText;
	}
}
