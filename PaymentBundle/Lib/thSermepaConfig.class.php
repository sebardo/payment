<?php

class thSermepaConfig
{

	/*
	 * Required parameters
	 */

	public $serverUrl;
	public $password;

	public $amount;
	public $currency = 978; // Euro constant
	public $merchantCode;
	public $productDescription;
	public $terminal = 1;
	public $titular;
	public $transactionType = 0; // authorization

	/*
	 * Optional parameters
	 */

	public $authorisationCode;
	public $chargeExpiryDate;
	public $consumerLanguage = 0;
	public $customOrderNumber;
	public $dateFrecuency;
	public $merchantData;
	public $merchantName;
	public $merchantUrl;
	public $merchantUrlOk;
	public $merchantUrlKo;
	public $sumTotal;
	public $transactionDate;

	public function __construct($params = array())
	{
		foreach ($params as $var => $value) {
			$this->$var = $value;
		}
	}

	public function isValid()
	{
		$required = array('serverUrl', 'password', 'amount', 'currency', 'merchantCode',
				'productDescription', 'terminal', 'titular', 'transactionType');
		foreach ($required as $name) {
			if ($this->$name === null) {
				return false;
			}
		}
		return true;
	}

}