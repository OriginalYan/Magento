<?php
/**
 * Created by PhpStorm.
 * User: george.babarus
 * Date: 10/24/2014
 * Time: 2:36 PM
 */


error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);


$d = dirname(__FILE__) . '/lib/Zitec/Dpd/Api.php';
include $d;


$_options = array(
    'url'             => 'https://geopost:KF9DVy7Jjk@integration.dpd.eo.pl/IT4EMWebServices/eshop/ShipmentServiceImpl?wsdl',
    'wsUserName'      => 'WEBSERVICE',
    'wsPassword'      => 'VZSxy18nfK',
    'wsLang'          => 'EN',
    'applicationType' => '9',

    'payerId'         => '8038059',
    'senderAddressId' => '8377391',
    'mainServiceCode' => 1,

    'shipmentList'    => array(
        'shipmentReferenceNumber' => 1234567,
        'receiverName'            => 'DPD Price Calculation',
        'receiverFirmName'        => null,

        'receiverCountryCode'     => "SK",

        'receiverZipCode'         => 96001,
        'receiverCity'            => 'Zvolen',
        'receiverStreet'          => 'Antona Bernoláka 28-30',
        'receiverHouseNo'         => '28-30',
        'receiverPhoneNo'         => '0734344544',

        'parcels'                 => array(
            'weight'                => 3,
            'parcelReferenceNumber' => 123
        ),
    ),

    'method'          => Zitec_Dpd_Api_Configs::METHOD_SHIPMENT_SAVE
);

try {
    $dpdApi = new Zitec_Dpd_Api($_options);

    $dpdApi()->execute();

} catch (\Exception $e) {
    var_dump($e->getMessage());
}
var_dump($dpdApi());



/*
 Response example




object(Zitec_Dpd_Api_Shipment_CalculatePrice)[3]
  protected '_url' => string 'https://geopost:KF9DVy7Jjk@integration.dpd.eo.pl/IT4EMWebServices/eshop/ShipmentServiceImpl?wsdl' (length=96)
  protected '_connectionTimeout' => int 10
  protected '_wsUserName' => string 'WEBSERVICE' (length=10)
  protected '_wsPassword' => string 'VZSxy18nfK' (length=10)
  protected '_payerId' => string '8038059' (length=7)
  protected '_senderAddressId' => string '8377391' (length=7)
  protected '_lastResponse' => string '<env:Envelope xmlns:env='http://schemas.xmlsoap.org/soap/envelope/'><env:Header></env:Header><env:Body><ns1:calculatePriceResponse xmlns:ns1='http://it4em.yurticikargo.com.tr/eshop/shipment' xmlns:ns2='http://it4em.yurticikargo.com.tr/eshop/shipment' xmlns:ns3='http://it4em.yurticikargo.com.tr/eshop/'><result><transactionId>82272</transactionId><priceList><price><amount>6.55</amount><vatAmount>1.31</vatAmount><totalAmount>7.86</totalAmount><currency>EUR</currency><amountLocal>6.55</amountLocal><vatAmountLocal>1.31</vatAmountLocal><totalAmountLocal>7.86</totalAmountLocal><currencyLocal>EUR</currencyLocal></price><error xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:nil='true'/></priceList></result></ns1:calculatePriceResponse></env:Body></env:Envelope>' (length=772)
  protected '_lastRequest' => string '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:ns1="http://it4em.yurticikargo.com.tr/eshop/shipment"><SOAP-ENV:Body><ns1:calculatePrice><wsUserName>WEBSERVICE</wsUserName><wsPassword>VZSxy18nfK</wsPassword><wsLang>EN</wsLang><applicationType>9</applicationType><shipmentList><shipmentId xsi:nil="true"/><shipmentReferenceNumber/><payerId>8038059</payerId><senderAddressId>8377391</senderAddressId><receiverName>DPD Price Calculation</receiverName><receiverFirmName xsi:nil="true"/><receiverCountryCode>SK</receiverCountryCode><receiverZipCode>96001</receiverZipCode><receiverCity>Zvolen</receiverCity><receiverStreet>Antona BernolÃ¡ka 28-30</receiverStreet><receiverHouseNo>28-30</receiverHouseNo><receiverPhoneNo>0734344544</receiverPhoneNo><mainServiceCode>1</mainServiceCode><parcels><parcelId xsi:nil="true"/><parcelNo xsi:nil="true"/><parcelReferenceNumber/><dimensionsHeight xsi:nil="true'... (length=1224)
  protected '_data' =>
    array (size=9)
      'url' => string 'https://geopost:KF9DVy7Jjk@integration.dpd.eo.pl/IT4EMWebServices/eshop/ShipmentServiceImpl?wsdl' (length=96)
      'wsUserName' => string 'WEBSERVICE' (length=10)
      'wsPassword' => string 'VZSxy18nfK' (length=10)
      'wsLang' => string 'EN' (length=2)
      'applicationType' => int 9
      'payerId' => string '8038059' (length=7)
      'senderAddressId' => string '8377391' (length=7)
      'mainServiceCode' => int 1
      'shipmentList' =>
        array (size=13)
          'shipmentReferenceNumber' => null
          'receiverName' => string 'DPD Price Calculation' (length=21)
          'receiverFirmName' => null
          'receiverCountryCode' => string 'SK' (length=2)
          'receiverZipCode' => int 96001
          'receiverCity' => string 'Zvolen' (length=6)
          'receiverStreet' => string 'Antona BernolÃ¡ka 28-30' (length=23)
          'receiverHouseNo' => string '28-30' (length=5)
          'receiverPhoneNo' => string '0734344544' (length=10)
          'parcels' =>
            array (size=2)
              'weight' => int 3
              'parcelReferenceNumber' => null
          'mainServiceCode' => int 1
          'payerId' => string '8038059' (length=7)
          'senderAddressId' => string '8377391' (length=7)
  protected '_method' => string 'calculatePrice' (length=14)
  protected '_response' =>
    object(Zitec_Dpd_Api_Shipment_CalculatePrice_Response)[9]
      protected '_response' =>
        object(stdClass)[5]
          public 'result' =>
            object(stdClass)[6]
              public 'transactionId' => int 82272
              public 'priceList' =>
                object(stdClass)[7]
                  public 'price' =>
                    object(stdClass)[8]
                      public 'amount' => float 6.55
                      public 'vatAmount' => float 1.31
                      public 'totalAmount' => float 7.86
                      public 'currency' => string 'EUR' (length=3)
                      public 'amountLocal' => float 6.55
                      public 'vatAmountLocal' => float 1.31
                      public 'totalAmountLocal' => float 7.86
                      public 'currencyLocal' => string 'EUR' (length=3)
                  public 'error' => null
 */
