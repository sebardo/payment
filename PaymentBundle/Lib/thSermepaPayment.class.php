<?php

class thSermepaPayment
{

	/**
	 * @var thSermepaConfig
	 */
	protected $config;
	protected $merchantSignature;
	protected $order;

	public function __construct(thSermepaConfig $config)
	{
		if (!$config->isValid()) {
			throw new Exception('thSermepaConfig is not valid');
		}

		$this->config = $config;
		if ($config->customOrderNumber) {
			$this->order = substr(sprintf('%012s', $config->customOrderNumber), 0, 12);
		} else {
			$this->order = date('ymdHis');
		}
		$this->merchantSignature = $this->calculateHash();
	}

	public function getAmount()
	{
		return $this->config->amount * 100;
	}

	public function getOrder()
	{
		return $this->order;
	}

	public function getConfig()
	{
		return $this->config;
	}

	public function getMerchantSignature()
	{
		return $this->merchantSignature;
	}

	protected function calculateHash()
	{
		$message = $this->getAmount()
				. $this->order
				. $this->config->merchantCode
				. $this->config->currency
				. $this->config->transactionType
				. $this->config->merchantUrl
				. $this->config->password;

		return strtoupper(sha1($message));
	}

}