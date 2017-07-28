<?php

function sermepa_form_tag(thSermepaPayment $payment, $options = array())
{
    $url = $payment->getConfig()->serverUrl;
    return form_tag($url, $options);
}

function sermepa_hidden_tags(thSermepaPayment $payment)
{
    $html = array();
    $c = $payment->getConfig();

    $desc = $c->productDescription;
    $titular = $c->titular;
    if (function_exists('iconv')) {
        // Encode to ascii
        $desc = iconv('UTF-8', 'ASCII//TRANSLIT', $desc);
        $titular = iconv('UTF-8', 'ASCII//TRANSLIT', $titular);
    }

    $params = array(
        'Ds_Merchant_Amount' => $payment->getAmount(),
        'Ds_Merchant_Currency' => $c->currency,
        'Ds_Merchant_MerchantCode' => $c->merchantCode,
        'Ds_Merchant_MerchantData' => $c->merchantData,
        'Ds_Merchant_MerchantName' => $c->merchantName,
        'Ds_Merchant_MerchantSignature' => $payment->getMerchantSignature(),
        'Ds_Merchant_MerchantURL' => $c->merchantUrl,
        'Ds_Merchant_Order' => $payment->getOrder(),
        'Ds_Merchant_ProductDescription' => $desc,
        'Ds_Merchant_Terminal' => $c->terminal,
        'Ds_Merchant_Titular' => $titular,
        'Ds_Merchant_TransactionType' => $c->transactionType,
        'Ds_Merchant_UrlOK' => $c->merchantUrlOk,
        'Ds_Merchant_UrlKO' => $c->merchantUrlKo,
    );
    foreach ($params as $name => $value) {
        $html[] = tag('input', array('name' => $name, 'value' => $value, 'type' => 'hidden'));
    }

    return implode($html);
}