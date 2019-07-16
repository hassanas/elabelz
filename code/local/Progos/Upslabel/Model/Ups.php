<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Progos_Upslabel_Model_Ups extends Infomodus_Upslabel_Model_Ups
{
    private $AccessLicenseNumber;
    private $UserID;
    private $Password;
    private $shipperNumber;
    private $credentials;

    public $packages;
    public $weightUnits;
    public $packageWeight;
    public $weightUnitsDescription;
    public $weightUnitKoef = 1;
    public $dimentionUnitKoef = 1;

    public $includeDimensions;
    public $unitOfMeasurement;
    public $unitOfMeasurementDescription;
    public $length;
    public $width;
    public $height;

    public $customerContext;
    public $shipperName;
    public $shipperPhoneNumber;
    public $shipperAddressLine1;
    public $shipperCity;
    public $shipperStateProvinceCode;
    public $shipperPostalCode;
    public $shipperCountryCode;
    public $shipmentDescription;
    public $shipperAttentionName;

    public $shiptoCompanyName;
    public $shiptoAttentionName;
    public $shiptoPhoneNumber;
    public $shiptoAddressLine1;
    public $shiptoAddressLine2;
    public $shiptoCity;
    public $shiptoStateProvinceCode;
    public $shiptoPostalCode;
    public $shiptoCountryCode;
    public $residentialAddress;

    public $shipfromCompanyName;
    public $shipfromAttentionName;
    public $shipfromPhoneNumber;
    public $shipfromAddressLine1;
    public $shipfromAddressLine2;
    public $shipfromCity;
    public $shipfromStateProvinceCode;
    public $shipfromPostalCode;
    public $shipfromCountryCode;

    public $soldToType;
    public $direct_delivery_only;

    public $serviceCode;
    public $serviceDescription;
    public $shipmentDigest;

    public $trackingNumber;
    public $shipmentIdentificationNumber;
    public $graphicImage;
    public $htmlImage;

    public $codYesNo;
    public $currencyCode;
    public $currencyCodeByInvoice;
    public $codMonetaryValue;
    public $codFundsCode;
    public $invoicelinetotal;
    public $carbon_neutral;
    public $testing;
    public $shipmentcharge = 0;
    public $qvn = 0;
    public $qvn_code = 0;
    public $qvn_email_shipper = '';
    public $qvn_email_shipto = '';
    public $qvn_lang = '';
    public $adult = 0;
    public $upsAccount = 0;
    public $accountData;
    public $saturdayDelivery;
    public $movement_reference_number;
    public $international_invoice;
    public $international_description;
    public $international_comments;
    public $international_invoicenumber;
    public $international_reasonforexport;
    public $international_termsofshipment;
    public $international_purchaseordernumber;
    public $international_products;
    public $international_invoicedate;
    public $declaration_statement = '';

    /* Pickup */
    public $RatePickupIndicator;
    public $CloseTime;
    public $ReadyTime;
    public $PickupDateYear;
    public $PickupDateMonth;
    public $PickupDateDay;
    public $AlternateAddressIndicator;
    public $ServiceCode;
    public $Quantity;
    public $DestinationCountryCode;
    public $ContainerCode;
    public $Weight;
    public $UnitOfMeasurement;
    public $OverweightIndicator;
    public $PaymentMethod;
    public $SpecialInstruction;
    public $ReferenceNumber;
    public $Notification;
    public $ConfirmationEmailAddress;
    public $UndeliverableEmailAddress;
    public $room;
    public $floor;
    public $urbanization;
    public $residential;
    public $pickup_point;
    /* END Pickup */

    /* Access Point */
    public $accesspoint = 0;
    public $accesspoint_type;
    public $accesspoint_name;
    public $accesspoint_atname;
    public $accesspoint_appuid;
    public $accesspoint_street;
    public $accesspoint_street1;
    public $accesspoint_street2;
    public $accesspoint_city;
    public $accesspoint_provincecode;
    public $accesspoint_postal;
    public $accesspoint_country;
    public $accesspoint_COD = true;
    /* Access Point */



    public $negotiated_rates = 0;
    public $rates_tax = 0;

    public function setCredentials($access, $user, $pass, $shipper)
    {
        $this->AccessLicenseNumber = $access;
        $this->UserID = $user;
        $this->Password = $pass;
        $this->shipperNumber = $shipper;
        $this->credentials = 1;
        Mage::helper('upslabel/help')->createMediaFolders();
        return $this->credentials;
    }

    function getShip($type = 'shipment')
    {
        if ( Mage::app()->getRequest()->getParam('dytytaxinternational') )
            $dytytaxinternational = Mage::app()->getRequest()->getParam('dytytaxinternational');
        else
            $dytytaxinternational = Mage::getStoreConfig('upslabel/ratepayment/dytytaxinternational');

        if ($this->credentials != 1) {
            return array('error' => array('cod' => 1, 'message' => 'Not correct registration data'), 'success' => 0);
        }
        /* if(is_dir($filename)){} */
        $path = Mage::getBaseDir('media') . '/upslabel/label/';
        $path_xml = Mage::getBaseDir('media') . '/upslabel/test_xml/';
        $this->customerContext = str_replace('&', '&amp;', strtolower(Mage::app()->getStore( )->getName()));
        $validate = Mage::getStoreConfig('upslabel/shipping/validate' ) == 1 ? 'validate' : 'nonvalidate';
        $data = "<?xml version=\"1.0\" ?>
<AccessRequest xml:lang='en-US'>
<AccessLicenseNumber>" . $this->AccessLicenseNumber . "</AccessLicenseNumber>
<UserId>" . $this->UserID . "</UserId>
<Password>" . $this->Password . "</Password>
</AccessRequest>
<?xml version=\"1.0\"?>
<ShipmentConfirmRequest xml:lang=\"en-US\">
  <Request>
    <TransactionReference>
      <CustomerContext>" . $this->customerContext . "</CustomerContext>
      <XpciVersion/>
    </TransactionReference>
    <RequestAction>ShipConfirm</RequestAction>
    <RequestOption>" . $validate . "</RequestOption>
  </Request>
  <LabelSpecification>
    <LabelPrintMethod>
      <Code>" . Mage::getStoreConfig('upslabel/printing/printer') . "</Code>
    </LabelPrintMethod>
    ";
        if (Mage::getStoreConfig('upslabel/printing/printer') != "GIF") {
            $data .= "<LabelStockSize>
                <Height>4</Height>
                <Width>" . Mage::getStoreConfig('upslabel/printing/termal_width') . "</Width>
            </LabelStockSize>";
        }
        $data .= "
    <HTTPUserAgent>Mozilla/4.5</HTTPUserAgent>
    <LabelImageFormat>
      <Code>" . Mage::getStoreConfig('upslabel/printing/printer') . "</Code>
    </LabelImageFormat>
  </LabelSpecification>
  <Shipment>";
        if (Mage::getStoreConfig('upslabel/ratepayment/negotiatedratesindicator') == 1) {
            $data .= "
   <RateInformation>
      <NegotiatedRatesIndicator/>
    </RateInformation>";
        }
        if (strlen($this->shipmentDescription) > 0) {
            $data .= "<Description>" . $this->shipmentDescription . "</Description>";
        }
        $data .= $this->setShipper() . $this->setShipTo($type) . $this->setShipFrom($type);
        if ($this->shiptoCountryCode != $this->shipfromCountryCode) {
            $paymentTag = 'ItemizedPaymentInformation';
            $data .= "<" . $paymentTag . ">";
            if ($this->upsAccount != 1) {
                $data .= "<ShipmentCharge><Type>01</Type>
        <BillShipper>";
                /*if ($this->accesspoint == 1 && $this->accesspoint_type == '02') {
                    $data .= "<AlternatePaymentMethod>01</AlternatePaymentMethod>";
                } else {*/
                $data .= "<AccountNumber>" . $this->shipperNumber . "</AccountNumber>";
                /* }*/
                $data .= "</BillShipper></ShipmentCharge>";
                if ( $dytytaxinternational === 'shipper') {
                    $data .= "
                <ShipmentCharge>
                <Type>02</Type>
                  <BillShipper>
                    <AccountNumber>" . $this->shipperNumber . "</AccountNumber>
                  </BillShipper></ShipmentCharge>";
                } /*else {
                    $data .= "
                <ShipmentCharge>
                <Type>02</Type>
                  <BillReceiver><AccountNumber/></BillReceiver></ShipmentCharge>";
                }*/
            } else {
                $data .= "<ShipmentCharge><Type>01</Type><BillThirdParty>
                    <BillThirdPartyShipper>
                        <AccountNumber>" . $this->accountData->getAccountnumber() . "</AccountNumber>
                        <ThirdParty>
                            <Address>
                                <PostalCode>" . $this->accountData->getPostalcode() . "</PostalCode>
                                <CountryCode>" . $this->accountData->getCountry() . "</CountryCode>
                            </Address>
                        </ThirdParty>
                    </BillThirdPartyShipper>
                </BillThirdParty></ShipmentCharge>";
            }
            $data .= "
                </" . $paymentTag . ">
            ";
        } else {
            $paymentTag = 'PaymentInformation';
            $data .= "<" . $paymentTag . ">";
            if ($this->upsAccount != 1) {
                $data .= "<Prepaid>
        <BillShipper>";
                /*if ($this->accesspoint == 1 && $this->accesspoint_type == '02') {
                    $data .= "<AlternatePaymentMethod>01</AlternatePaymentMethod>";
                } else {*/
                $data .= "<AccountNumber>" . $this->shipperNumber . "</AccountNumber>";
                /*}*/
                $data .= "</BillShipper>
      </Prepaid>";
            } else {
                $data .= "<BillThirdParty>
                    <BillThirdPartyShipper>
                        <AccountNumber>" . $this->accountData->getAccountnumber() . "</AccountNumber>
                        <ThirdParty>
                            <Address>
                                <PostalCode>" . $this->accountData->getPostalcode() . "</PostalCode>
                                <CountryCode>" . $this->accountData->getCountry() . "</CountryCode>
                            </Address>
                        </ThirdParty>
                    </BillThirdPartyShipper>
                </BillThirdParty>";
            }
            $data .= "
                </" . $paymentTag . ">
            ";
        }
        $data .= "<Service>
      <Code>" . $this->serviceCode . "</Code>
      <Description>" . $this->serviceDescription . "</Description>
    </Service>";
        if ($this->shiptoCountryCode != $this->shipfromCountryCode && $this->movement_reference_number != '') {
            $data .= "<MovementReferenceNumber>" . $this->movement_reference_number . "</MovementReferenceNumber>";
        }
        if ($this->shiptoCountryCode != $this->shipfromCountryCode || ($this->shiptoCountryCode == $this->shipfromCountryCode && $this->shiptoCountryCode != 'US' && $this->shiptoCountryCode != 'PR')) {
            $data .= "<ReferenceNumber>";
            if (Mage::getStoreConfig('upslabel/packaging/packagingreferencebarcode') == 1) {
                $data .= "<BarCodeIndicator/>";
            }
            $data .= "<Code>" . $this->packages[0]['packagingreferencenumbercode'] . "</Code>
		<Value>" . $this->packages[0]['packagingreferencenumbervalue'] . "</Value>
	  </ReferenceNumber>";
            if (isset($this->packages[0]['packagingreferencenumbercode2'])) {
                $data .= "<ReferenceNumber>";
                if (Mage::getStoreConfig('upslabel/packaging/packagingreferencebarcode2') == 1) {
                    $data .= "<BarCodeIndicator/>";
                }
                $data .= "<Code>" . $this->packages[0]['packagingreferencenumbercode2'] . "</Code>
		<Value>" . $this->packages[0]['packagingreferencenumbervalue2'] . "</Value>
	  </ReferenceNumber>";
            }
        }
        foreach ($this->packages AS $pv) {
            $data .= "<Package>
      <PackagingType>
        <Code>" . $pv["packagingtypecode"] . "</Code>
      </PackagingType>
      <Description>" . $pv["packagingdescription"] . "</Description>";
            if (($this->shiptoCountryCode == 'US' || $this->shiptoCountryCode == 'PR') && $this->shiptoCountryCode == $this->shipfromCountryCode) {
                $data .= "<ReferenceNumber>";
                if (Mage::getStoreConfig('upslabel/packaging/packagingreferencebarcode') == 1) {
                    $data .= "<BarCodeIndicator/>";
                }
                $data .= "<Code>" . $pv['packagingreferencenumbercode'] . "</Code>
		<Value>" . $pv['packagingreferencenumbervalue'] . "</Value>
	  </ReferenceNumber>";
                if (isset($pv['packagingreferencenumbercode2'])) {
                    $data .= "<ReferenceNumber>";
                    $data .= "<Code>" . $pv['packagingreferencenumbercode2'] . "</Code>
		<Value>" . $pv['packagingreferencenumbervalue2'] . "</Value>
	  </ReferenceNumber>";
                }
            }
            $data .= array_key_exists('additionalhandling', $pv) ? $pv['additionalhandling'] : '';
            if ($this->includeDimensions == 1) {
                $data .= "<Dimensions>
<UnitOfMeasurement>
<Code>" . $this->unitOfMeasurement . "</Code>";
                if (strlen($this->unitOfMeasurementDescription) > 0) {
                    $data .= "
<Description>" . $this->unitOfMeasurementDescription . "</Description>";
                }
                $data .= "</UnitOfMeasurement>";
                if (isset($pv['length']) && strlen($pv['length']) > 0) {
                    $data .= "<Length>" . $pv['length'] . "</Length>
                            <Width>" . $pv['width'] . "</Width>
                            <Height>" . $pv['height'] . "</Height>";
                }
                $data .= "</Dimensions>";
            }
            $data .= "<PackageWeight>
        <UnitOfMeasurement>
            <Code>" . $this->weightUnits . "</Code>";
            if (strlen($this->weightUnitsDescription) > 0) {
                $data .= "
            <Description>" . $this->weightUnitsDescription . "</Description>";
            }
            $packweight = array_key_exists('packweight', $pv) ? $pv['packweight'] : '';
            $weight = array_key_exists('weight', $pv) ? $pv['weight'] : '';
            $weight = round(($weight * $this->weightUnitKoef + (is_numeric($packweight = str_replace(',', '.', $packweight)) ? $packweight * $this->weightUnitKoef : 0)), 1);
            $data .= "</UnitOfMeasurement>
        <Weight>" . $weight . "</Weight>"
                . $this->largePackageIndicator($pv) . "
      </PackageWeight>
      <PackageServiceOptions>";
            if ($pv['insuredmonetaryvalue'] > 0) {
                $data .= "<InsuredValue>
                <CurrencyCode>" . $this->currencyCode . "</CurrencyCode>
                <MonetaryValue>" . $pv['insuredmonetaryvalue'] . "</MonetaryValue>
                </InsuredValue>
              ";
            }
            if ($pv['cod'] == 1 && ($this->shiptoCountryCode == 'US' || $this->shiptoCountryCode == 'PR' || $this->shiptoCountryCode == 'CA') && ($this->shipfromCountryCode == 'US' || $this->shipfromCountryCode == 'PR' || $this->shipfromCountryCode == 'CA')) {
                if ($this->accesspoint != 1) {
                    $data .= "
              <COD>
                  <CODCode>3</CODCode>
                  <CODFundsCode>" . $pv['codfundscode'] . "</CODFundsCode>
                  <CODAmount>
                      <CurrencyCode>" . $this->currencyCode . "</CurrencyCode>
                      <MonetaryValue>" . $pv['codmonetaryvalue'] . "</MonetaryValue>
                  </CODAmount>
              </COD>";
                } else {
                    $data .= "
              <AccessPointCOD>
                <CurrencyCode>" . $this->currencyCode . "</CurrencyCode>
                <MonetaryValue>" . $pv['codmonetaryvalue'] . "</MonetaryValue>
              </AccessPointCOD>";
                }
            }
            if ($this->isAdult('P')) {
                $data .= "<DeliveryConfirmation><DCISType>" . $this->adult . "</DCISType></DeliveryConfirmation>";
            }
            $data .= "</PackageServiceOptions>
              </Package>";
        }
        $data .= "<ShipmentServiceOptions>";
        if ($this->direct_delivery_only == 1 && $this->accesspoint !== 1) {
            $data .= "<DirectDeliveryOnlyIndicator></DirectDeliveryOnlyIndicator>";
        }
        if ($this->codYesNo == 1 && $this->shiptoCountryCode != 'US' && $this->shiptoCountryCode != 'PR' && $this->shiptoCountryCode != 'CA' && $this->shipfromCountryCode != 'US' && $this->shipfromCountryCode != 'PR' && $this->shipfromCountryCode != 'CA') {
            if ($this->accesspoint != 1) {
                $data .= "<COD>
                  <CODCode>3</CODCode>
                  <CODFundsCode>" . $this->codFundsCode . "</CODFundsCode>
                  <CODAmount>
                      <CurrencyCode>" . $this->currencyCode . "</CurrencyCode>
                      <MonetaryValue>" . $this->codMonetaryValue . "</MonetaryValue>
                  </CODAmount>
              </COD>";
            } elseif ($this->accesspoint_COD === true) {
                $data .= "<AccessPointCOD>
                      <CurrencyCode>" . $this->currencyCode . "</CurrencyCode>
                      <MonetaryValue>" . $this->codMonetaryValue . "</MonetaryValue>
              </AccessPointCOD>";

            }

        }
        if ($this->isAdult('S')) {
            $data .= "<DeliveryConfirmation><DCISType>" . $this->adult . "</DCISType></DeliveryConfirmation>";
        }
        if ($this->carbon_neutral == 1) {
            $data .= "<UPScarbonneutralIndicator/>";
        }
        if (!is_array($this->qvn_code)) {
            $this->qvn_code = explode(",", $this->qvn_code);
        }
        if ($this->qvn == 1 && count($this->qvn_code) > 0) {
            $email_undelivery = 0;
            foreach ($this->qvn_code AS $qvncode) {
                if ($qvncode != 2 && $qvncode != 5) {
                    $data .= "<Notification>
            <NotificationCode>" . $qvncode . "</NotificationCode>
            <EMailMessage>";
                    if (strlen($this->qvn_email_shipper) > 0) {
                        $data .= "<EMailAddress>" . $this->qvn_email_shipper . "</EMailAddress>";
                    }
                    if (strlen($this->qvn_email_shipto) > 0) {
                        $data .= "<EMailAddress>" . $this->qvn_email_shipto . "</EMailAddress>";
                    }
                    if (strlen($this->qvn_email_shipper) > 0 && $email_undelivery == 0) {
                        $data .= "<UndeliverableEMailAddress>" . $this->qvn_email_shipper . "</UndeliverableEMailAddress>";
                        $email_undelivery = 1;
                    }
                    $data .= "</EMailMessage>";
                    if (strlen($this->qvn_lang) > 4) {
                        $qvn_lang = explode(":", $this->qvn_lang);
                        $data .= "<Locale><Language>" . $qvn_lang[0] . "</Language><Dialect>" . $qvn_lang[1] . "</Dialect></Locale>";
                    }
                    $data .= "</Notification>";
                    if ($this->accesspoint == 1) {
                        break;
                    }
                }
            }
        }
        if ($this->accesspoint == 1) {
            $data .= "<Notification>
            <NotificationCode>012</NotificationCode>
            <EMailMessage>";
            if (strlen($this->qvn_email_shipper) > 0) {
                $data .= "<EMailAddress>" . $this->qvn_email_shipper . "</EMailAddress>";
            }
            if (strlen($this->qvn_email_shipto) > 0) {
                $data .= "<EMailAddress>" . $this->qvn_email_shipto . "</EMailAddress>";
            }
            $data .= "</EMailMessage>";
            if (strlen($this->qvn_lang) > 4) {
                $qvn_lang = explode(":", $this->qvn_lang);
                $data .= "<Locale><Language>" . $qvn_lang[0] . "</Language><Dialect>" . $qvn_lang[1] . "</Dialect></Locale>";
            } else {
                $data .= "<Locale><Language>ENG</Language><Dialect>GB</Dialect></Locale>";
            }
            $data .= "</Notification>";
            $data .= "<Notification>
            <NotificationCode>013</NotificationCode>
            <EMailMessage>";
            if (strlen($this->qvn_email_shipper) > 0) {
                $data .= "<EMailAddress>" . $this->qvn_email_shipper . "</EMailAddress>";
            }
            if (strlen($this->qvn_email_shipto) > 0) {
                $data .= "<EMailAddress>" . $this->qvn_email_shipto . "</EMailAddress>";
            }
            $data .= "</EMailMessage>";
            if ($this->qvn_lang && (is_array($this->qvn_lang) || strlen($this->qvn_lang) > 4)) {
                if (!is_array($this->qvn_lang)) {
                    $qvn_lang = explode(":", $this->qvn_lang);
                }
                $data .= "<Locale><Language>" . $qvn_lang[0] . "</Language><Dialect>" . $qvn_lang[1] . "</Dialect></Locale>";
            } else {
                $data .= "<Locale><Language>ENG</Language><Dialect>GB</Dialect></Locale>";
            }
            $data .= "</Notification>";
        }
        $data .= $this->saturdayDelivery;
        if ($this->international_invoice == 1 && $this->shipfromCountryCode != $this->shiptoCountryCode && count($this->international_products) > 0) {
            $data .= "<InternationalForms><FormType>01</FormType><AdditionalDocumentIndicator></AdditionalDocumentIndicator>";
            foreach ($this->international_products AS $interproduct) {
                $data .= "<Product>
            <Description>" . Infomodus_Upslabel_Helper_Help::escapeXML($interproduct['description']) . "</Description>
            <OriginCountryCode>" . Infomodus_Upslabel_Helper_Help::escapeXML($interproduct['country_code']) . "</OriginCountryCode>
            <Unit>
            <Number>" . Infomodus_Upslabel_Helper_Help::escapeXML($interproduct['qty']) . "</Number>
            <Value>" . Infomodus_Upslabel_Helper_Help::escapeXML($interproduct['amount']) . "</Value>
            <UnitOfMeasurement>
            <Code>" . Infomodus_Upslabel_Helper_Help::escapeXML($interproduct['unit_of_measurement']) . "</Code>";
                if (isset($interproduct['unit_of_measurementdesc']) && strlen($interproduct['unit_of_measurementdesc']) > 0) {
                    $data .= "<Description>" . Infomodus_Upslabel_Helper_Help::escapeXML($interproduct['unit_of_measurementdesc']) . "</Description>";
                }
                $data .= "</UnitOfMeasurement>
            </Unit>
            <CommodityCode>" . Infomodus_Upslabel_Helper_Help::escapeXML($interproduct['commoditycode']) . "</CommodityCode>
            <PartNumber>" . Infomodus_Upslabel_Helper_Help::escapeXML($interproduct['partnumber']) . "</PartNumber>";
                if ($interproduct['scheduleB_number'] != '' && $interproduct['scheduleB_number'] != '') {
                    $data .= "<ScheduleB>
<Number>" . $interproduct['scheduleB_number'] . "</Number>
<Quantity>" . $interproduct['qty'] . "</Quantity>
<UnitOfMeasurement>
<Code>" . $interproduct['scheduleB_unit'] . "</Code>
<Description>" . Mage::getModel("upslabel/config_schedulebUnitofmeasurement")->getScheduleUnitName($interproduct['scheduleB_unit']) . "</Description>
</UnitOfMeasurement>
</ScheduleB>";
                }
                $data .= "</Product>";
            }
            $data .= "<PurchaseOrderNumber>" . $this->international_purchaseordernumber . "</PurchaseOrderNumber>
            <TermsOfShipment>" . $this->international_termsofshipment . "</TermsOfShipment>
            <ReasonForExport>" . $this->international_reasonforexport . "</ReasonForExport>
            <Comments>" . $this->international_comments . "</Comments>
            <CurrencyCode>" . $this->currencyCodeByInvoice . "</CurrencyCode>
            <InvoiceNumber>" . $this->international_invoicenumber . "</InvoiceNumber>
            <InvoiceDate>" . $this->international_invoicedate . "</InvoiceDate>";
            if (trim($this->declaration_statement) != '') {
                $data .= "<DeclarationStatement>" . $this->declaration_statement . "</DeclarationStatement>";
            }
            $data .= "</InternationalForms>";
            /*<DeclarationStatement>qww we ete rt</DeclarationStatement>*/
        }
        $data .= "</ShipmentServiceOptions>";
        if (strlen($this->invoicelinetotal) > 0 && ($this->shiptoCountryCode == 'US' || $this->shiptoCountryCode == 'PR' || $this->shiptoCountryCode == 'CA') && ($this->shipfromCountryCode == 'US' || $this->shipfromCountryCode == 'PR' || $this->shipfromCountryCode == 'CA') && $this->shiptoCountryCode != $this->shipfromCountryCode) {
            $data .= "<InvoiceLineTotal>
                          <CurrencyCode>" . $this->currencyCode . "</CurrencyCode>
                          <MonetaryValue>" . round($this->invoicelinetotal, 0) . "</MonetaryValue>
              </InvoiceLineTotal>";
        }
        if ($this->accesspoint == 1) {
            $data .= "<ShipmentIndicationType>
            <Code>" . $this->accesspoint_type . "</Code>
            </ShipmentIndicationType>
            <AlternateDeliveryAddress>
                <Name>" . $this->accesspoint_name . "</Name>
                <AttentionName>" . $this->accesspoint_atname . "</AttentionName>
                <Address>";
            $addressline1 = str_split($this->accesspoint_street, 35);
            foreach ($addressline1 as $addressline) {
                $data .= "<AddressLine1>" . $addressline . "</AddressLine1>";
            }
            if ($this->accesspoint_street1 != "" && $this->accesspoint_street1 != "undefined") {
                $data .= "<AddressLine2>" . $this->accesspoint_street1 . "</AddressLine2>";
            }
            if ($this->accesspoint_street2 != "" && $this->accesspoint_street2 != "undefined") {
                $data .= "<AddressLine3>" . $this->accesspoint_street2 . "</AddressLine3>";
            }
            $data .= "<City>" . $this->accesspoint_city . "</City>";
            if ($this->shiptoCountryCode == "US" || $this->shiptoCountryCode == "CA") {
                $data .= "<StateProvinceCode>" . $this->accesspoint_provincecode . "</StateProvinceCode>";
            }
            $data .= "<PostalCode>" . $this->accesspoint_postal . "</PostalCode>
                    <CountryCode>" . $this->accesspoint_country . "</CountryCode>
                </Address>
                <UPSAccessPointID>" . $this->accesspoint_appuid . "</UPSAccessPointID>
            </AlternateDeliveryAddress>
            ";
        }
        if ($this->international_invoice == 1 && $this->shipfromCountryCode != $this->shiptoCountryCode) {
            if ($this->soldToType == 'shipper') {
                $data .= "<SoldTo>
                <CompanyName>" . $this->shipperName . "</CompanyName>
                <AttentionName>" . $this->shipperAttentionName . "</AttentionName>
                <PhoneNumber>" . $this->shipperPhoneNumber . "</PhoneNumber>
                <Address>
                    <AddressLine1>" . $this->shipperAddressLine1 . "</AddressLine1>
                    <City>" . $this->shipperCity . "</City>
                    <StateProvinceCode>" . $this->shipperStateProvinceCode . "</StateProvinceCode>
                    <PostalCode>" . $this->shipperPostalCode . "</PostalCode>
                    <CountryCode>" . $this->shipperCountryCode . "</CountryCode>
                </Address>
            </SoldTo>";
            } else {
                $data .= "<SoldTo>
                <CompanyName>" . $this->shiptoCompanyName . "</CompanyName>
                <AttentionName>" . $this->shiptoAttentionName . "</AttentionName>
                <PhoneNumber>" . $this->shiptoPhoneNumber . "</PhoneNumber>
                <Address>
                    <AddressLine1>" . $this->shiptoAddressLine1 . "</AddressLine1>
                    <City>" . $this->shiptoCity . "</City>
                    <StateProvinceCode>" . $this->shiptoStateProvinceCode . "</StateProvinceCode>
                    <PostalCode>" . $this->shiptoPostalCode . "</PostalCode>
                    <CountryCode>" . $this->shiptoCountryCode . "</CountryCode>
                </Address>
            </SoldTo>";
            }
        }
        $data .= "</Shipment>
</ShipmentConfirmRequest>
";

        file_put_contents($path_xml . "ShipConfirmRequest.xml", $data);

        $cie = 'wwwcie';
        if (0 == $this->testing) {
            $cie = 'onlinetools';
        }

        $curl = Mage::helper('upslabel/help');
        $curl->testing = !$this->testing;
        $result = $curl->curlSend('https://' . $cie . '.ups.com/ups.app/xml/ShipConfirm', $data);

        if (!$curl->error) {
            file_put_contents($path_xml . "ShipConfirmResponse.xml", $result);

            //return $result;
            $xml = simplexml_load_string($result);
            if ($xml->Response->ResponseStatusCode[0] == 1) {
                if ($xml->NegotiatedRates) {
                    $shiplabelprice = $xml->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue[0];
                    $shiplabelcurrency = $xml->NegotiatedRates->NetSummaryCharges->GrandTotal->CurrencyCode[0];
                } else {
                    $shiplabelprice = $xml->ShipmentCharges->TotalCharges->MonetaryValue[0];
                    $shiplabelcurrency = $xml->ShipmentCharges->TotalCharges->CurrencyCode[0];
                }
                $this->shipmentDigest = $xml->ShipmentDigest[0];
                $data = "<?xml version=\"1.0\" ?>
<AccessRequest xml:lang='en-US'>
<AccessLicenseNumber>" . $this->AccessLicenseNumber . "</AccessLicenseNumber>
<UserId>" . $this->UserID . "</UserId>
<Password>" . $this->Password . "</Password>
</AccessRequest>
<?xml version=\"1.0\" ?>
<ShipmentAcceptRequest>
<Request>
<TransactionReference>
<CustomerContext>" . $this->customerContext . "</CustomerContext>
<XpciVersion>1.0001</XpciVersion>
</TransactionReference>
<RequestAction>ShipAccept</RequestAction>
</Request>
<ShipmentDigest>" . $this->shipmentDigest . "</ShipmentDigest>
</ShipmentAcceptRequest>";
                file_put_contents($path_xml . "ShipAcceptRequest.xml", $data);
                $curl->testing = !$this->testing;
                $result = $curl->curlSend('https://' . $cie . '.ups.com/ups.app/xml/ShipAccept', $data);

                if (!$curl->error) {
                    file_put_contents($path_xml . "ShipAcceptResponse.xml", $result);
                    $xml = simplexml_load_string($result);
                    $this->shipmentIdentificationNumber = $xml->ShipmentResults[0]->ShipmentIdentificationNumber[0];
                    $arrResponsXML = array();
                    $i = 0;
                    foreach ($xml->ShipmentResults[0]->PackageResults AS $resultXML) {
                        $arrResponsXML[$i]['trackingnumber'] = $resultXML->TrackingNumber[0];
                        $arrResponsXML[$i]['graphicImage'] = base64_decode($resultXML->LabelImage[0]->GraphicImage[0]);
                        $arrResponsXML[$i]['type_print'] = $resultXML->LabelImage[0]->LabelImageFormat[0]->Code[0];
                        $file = fopen($path . 'label' . $arrResponsXML[$i]['trackingnumber'] . '.' . strtolower($arrResponsXML[$i]['type_print']), 'w');
                        fwrite($file, $arrResponsXML[$i]['graphicImage']);
                        fclose($file);
                        if ($arrResponsXML[$i]['type_print'] == "GIF") {
                            $arrResponsXML[$i]['htmlImage'] = base64_decode($resultXML->LabelImage[0]->HTMLImage[0]);
                            file_put_contents($path . $arrResponsXML[$i]['trackingnumber'] . ".html", $arrResponsXML[$i]['htmlImage']);
                            file_put_contents($path_xml . "HTML_image.html", $arrResponsXML[$i]['htmlImage']);
                        }
                        $i += 1;
                    }
                    $interInvoice = NULL;
                    if ($this->international_invoice == 1) {
                        if (is_array($xml->ShipmentResults[0]->Form->Image->GraphicImage)) {
                            $interInvoice = $xml->ShipmentResults[0]->Form->Image->GraphicImage[0];
                        } else {
                            $interInvoice = $xml->ShipmentResults[0]->Form->Image->GraphicImage;
                        }
                    }
                    $turnInPage = NULL;
                    if ($xml->ShipmentResults[0]->CODTurnInPage && $xml->ShipmentResults[0]->CODTurnInPage->Image->GraphicImage) {
                        $turnInPage = $xml->ShipmentResults[0]->CODTurnInPage->Image->GraphicImage;
                    }
                    if ($this->codMonetaryValue > 999) {
                        $htmlHVReport = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv=Content-Type content="text/html; charset=windows-1252">
<meta name=ProgId content=Word.Document>
<meta name=Generator content="Microsoft Word 11">
<meta name=Originator content="Microsoft Word 11">
<link rel=File-List href="sample%20UPS%20CONTROL%20LOG_files/filelist.xml">
<title>UPS CONTROL LOG </title>
<style>
<!--
 /* Style Definitions */
 p.MsoNormal, li.MsoNormal, div.MsoNormal
	{mso-style-parent:"";
	margin:0;
	margin-bottom:.0001pt;
	mso-pagination:widow-orphan;
	font-size:10.0pt;
	mso-bidi-font-size:12.0pt;
	font-family:Arial;
	mso-fareast-font-family:"Times New Roman";}
span.GramE
	{mso-style-name:"";
	mso-gram-e:yes;}
@page Section1
	{size:8.5in 11.0in;
	margin:1.0in 1.25in 1.0in 1.25in;
	mso-header-margin:.5in;
	mso-footer-margin:.5in;
	mso-paper-source:0;}
div.Section1
	{page:Section1;}
-->
</style>
</head>
<body lang=EN-US style=\'tab-interval:.5in\'>

<div class=Section1>

<p class=MsoNormal>UPS CONTROL <span class=GramE>LOG</span></p>

<p class=MsoNormal>DATE: ' . date('d') . ' ' . date('M') . ' ' . date('Y') . ' UPS SHIPPER NO. ' . $this->shipperNumber . ' </p>
<br />
<br />
<p class=MsoNormal>TRACKING # PACKAGE ID REFRENCE NUMBER DECLARED VALUE
CURRENCY </p>
<p class=MsoNormal>--------------------------------------------------------------------------------------------------------------------------
</p>
<br /><br />
<p class=MsoNormal>' . $this->trackingNumber . ' <span class=GramE>' . $this->packages[0]['packagingreferencenumbervalue'] . ' ' . round($this->codMonetaryValue, 2) . '</span> ' . Mage::app()->getLocale()->currency(Mage::app()->getStore( )->getCurrentCurrencyCode())->getSymbol() . ' </p>
<br /><br />
<p class=MsoNormal>Total Number of Declared Value Packages = 1 </p>
<p class=MsoNormal>--------------------------------------------------------------------------------------------------------------------------
</p>
<br /><br />
<p class=MsoNormal>RECEIVED BY_________________________PICKUP
TIME__________________PKGS_______ </p>
</div>
</body>
</html>';
                        file_put_contents($path . "HVR" . $this->shipmentIdentificationNumber . ".html", $htmlHVReport);
                    }
                    return array(
                        'arrResponsXML' => $arrResponsXML,
                        'digest' => '' . $this->shipmentDigest . '',
                        'shipidnumber' => '' . $this->shipmentIdentificationNumber . '',
                        'price' => array('currency' => $shiplabelcurrency, 'price' => $shiplabelprice),
                        'inter_invoice' => $interInvoice,
                        'turn_in_page' => $turnInPage
                    );
                } else {
                    return $result;
                }
            } else {
                $error = '<h1>Error</h1> <ul>';
                $errorss = $xml->Response->Error[0];
                $error .= '<li>Error Severity : ' . $errorss->ErrorSeverity . '</li>';
                $error .= '<li>Error Code : ' . $errorss->ErrorCode . '</li>';
                $error .= '<li>Error Description : ' . $errorss->ErrorDescription . '</li>';
                $error .= '</ul>';
                Mage::log($error);
                $error .= '<textarea>' . $result . '</textarea>';
                $error .= '<textarea>' . $data . '</textarea>';
                return array('errordesc' => $errorss->ErrorDescription, 'error' => $error, 'request' => $data, 'response' => $result);
            }
        } else {
            return $result;
        }
    }

    function getShipFrom($type = 'refund')
    {
        if ($this->credentials != 1) {
            return array('error' => array('cod' => 1, 'message' => 'Not correct registration data'), 'success' => 0);
        }
        /* if(is_dir($filename)){} */
        $path_upsdir = Mage::getBaseDir('media') . DS . 'upslabel' . DS;
        if (!is_dir($path_upsdir)) {
            mkdir($path_upsdir, 0777);
            mkdir($path_upsdir . "label" . DS, 0777);
            mkdir($path_upsdir . "test_xml" . DS, 0777);
        }
        $path = Mage::getBaseDir('media') . DS . 'upslabel' . DS . "label" . DS;
        $path_xml = Mage::getBaseDir('media') . DS . 'upslabel' . DS . "test_xml" . DS;
        if (!file_exists($path_xml . ".htaccess")) {
            file_put_contents($path_xml . ".htaccess", "deny from all");
        }
        $this->customerContext = str_replace('&', '&amp;', strtolower(Mage::app()->getStore( )->getName()));
        $validate = Mage::getStoreConfig('upslabel/shipping/validate' ) == 1 ? 'validate' : 'nonvalidate';
        $data = "<?xml version=\"1.0\" ?>
        <AccessRequest xml:lang='en-US'>
        <AccessLicenseNumber>" . $this->AccessLicenseNumber . "</AccessLicenseNumber>
        <UserId>" . $this->UserID . "</UserId>
        <Password>" . $this->Password . "</Password>
        </AccessRequest>
        <?xml version=\"1.0\"?>
        <ShipmentConfirmRequest xml:lang=\"en-US\">
          <Request>
            <TransactionReference>
              <CustomerContext>" . $this->customerContext . "</CustomerContext>
              <XpciVersion/>
            </TransactionReference>
            <RequestAction>ShipConfirm</RequestAction>
            <RequestOption>" . $validate . "</RequestOption>
          </Request>
          <LabelSpecification>
            <LabelPrintMethod>
              <Code>GIF</Code>
              <Description>gif file</Description>
            </LabelPrintMethod>
            <HTTPUserAgent>Mozilla/4.5</HTTPUserAgent>
            <LabelImageFormat>
              <Code>GIF</Code>
              <Description>gif</Description>
            </LabelImageFormat>
          </LabelSpecification>
          <Shipment>";
        if (Mage::getStoreConfig('upslabel/ratepayment/negotiatedratesindicator') == 1) {
            $data .= "<RateInformation>
      <NegotiatedRatesIndicator/>
    </RateInformation>";
        }
        $data .= "<ShipmentServiceOptions>";
        if (Mage::getStoreConfig('upslabel/return/return_service_code') == 8) {
            $data .= "<LabelDelivery>
                        <LabelLinksIndicator />
                    </LabelDelivery>";
        }
        if ($this->carbon_neutral == 1) {
            $data .= "<UPScarbonneutralIndicator/>";
        }
        if ($this->qvn == 1) {
            $email_undelivery = 0;
            if (!is_array($this->qvn_code)) {
                $this->qvn_code = explode(",", $this->qvn_code);
            }
            foreach ($this->qvn_code AS $qvncode) {
                if ($qvncode == 2 || $qvncode == 5) {
                    $data .= "<Notification>
            <NotificationCode>" . $qvncode . "</NotificationCode>
            <EMailMessage>";
                    if (strlen($this->qvn_email_shipper) > 0) {
                        $data .= "<EMailAddress>" . $this->qvn_email_shipper . "</EMailAddress>";
                    }
                    if (strlen($this->qvn_email_shipto) > 0) {
                        $data .= "<EMailAddress>" . $this->qvn_email_shipto . "</EMailAddress>";
                    }
                    if (strlen($this->qvn_email_shipper) > 0 && $email_undelivery == 0) {
                        $data .= "<UndeliverableEMailAddress>" . $this->qvn_email_shipper . "</UndeliverableEMailAddress>";
                        $email_undelivery = 1;
                    }
                    if ($this->qvn_lang && (is_array($this->qvn_lang) || strlen($this->qvn_lang) > 4)) {
                        if (!is_array($this->qvn_lang)) {
                            $qvn_lang = explode(":", $this->qvn_lang);
                        }
                        $data .= "<Locale><Language>" . $this->qvn_lang[0] . "</Language><Dialect>" . $this->qvn_lang[1] . "</Dialect></Locale>";
                    }
                    $data .= "</EMailMessage>
            </Notification>";
                }
            }
        }
        $data .= $this->saturdayDelivery . "</ShipmentServiceOptions>";
        $data .= "<ReturnService><Code>" . Mage::getStoreConfig('upslabel/return/return_service_code') . "</Code></ReturnService>";
        if (strlen($this->shipmentDescription) > 0) {
            $data .= "<Description>" . $this->shipmentDescription . "</Description>";
        }
        $data .= $this->setShipper() . $this->setShipFrom($type) . $this->setShipTo($type) . "
             <PaymentInformation>
              <Prepaid>
                <BillShipper>
                  <AccountNumber>" . $this->shipperNumber . "</AccountNumber>
                </BillShipper>
              </Prepaid>
            </PaymentInformation>
            <Service>
              <Code>" . $this->serviceCode . "</Code>
              <Description>" . $this->serviceDescription . "</Description>
            </Service>";
        /*if($this->shiptoCountryCode != $this->shipfromCountryCode  && $this->movement_reference_number != '') {
            $data .= "<MovementReferenceNumber>".$this->movement_reference_number."</MovementReferenceNumber>";
        }*/
        if ($this->shiptoCountryCode != $this->shipfromCountryCode || ($this->shiptoCountryCode == $this->shipfromCountryCode && $this->shiptoCountryCode != 'US' && $this->shiptoCountryCode != 'PR')) {
            $data .= "<ReferenceNumber>";
            if (Mage::getStoreConfig('upslabel/packaging/packagingreferencebarcode') == 1) {
                $data .= "<BarCodeIndicator></BarCodeIndicator>";
            }
            $data .= "<Code>" . $this->packages[0]['packagingreferencenumbercode'] . "</Code>
		<Value>" . $this->packages[0]['packagingreferencenumbervalue'] . "</Value>
	  </ReferenceNumber>";
            if (isset($this->packages[0]['packagingreferencenumbercode2'])) {
                $data .= "<ReferenceNumber>";
                if (Mage::getStoreConfig('upslabel/packaging/packagingreferencebarcode2') == 1) {
                    $data .= "<BarCodeIndicator></BarCodeIndicator>";
                }
                $data .= "<Code>" . $this->packages[0]['packagingreferencenumbercode2'] . "</Code>
		<Value>" . $this->packages[0]['packagingreferencenumbervalue2'] . "</Value>
	  </ReferenceNumber>";
            }
        }
        $ttWeight = 0;
        foreach ($this->packages AS $pv) {
            $ttWeight += (isset($pv['weight']) ? $pv['weight'] : 0);
        }
        foreach ($this->packages AS $pv) {
            $data .= "<Package>
      <PackagingType>
        <Code>" . $pv["packagingtypecode"] . "</Code>
      </PackagingType>
      <Description>" . $pv["packagingdescription"] . "</Description>";
            if (($this->shiptoCountryCode == 'US' || $this->shiptoCountryCode == 'PR') && $this->shiptoCountryCode == $this->shipfromCountryCode) {
                $data .= "<ReferenceNumber>";
                if (Mage::getStoreConfig('upslabel/packaging/packagingreferencebarcode') == 1) {
                    $data .= "<BarCodeIndicator></BarCodeIndicator>";
                }
                $data .= "<Code>" . $pv['packagingreferencenumbercode'] . "</Code>
		<Value>" . $pv['packagingreferencenumbervalue'] . "</Value>
	  </ReferenceNumber>";
                if (isset($pv['packagingreferencenumbercode2'])) {
                    $data .= "<ReferenceNumber>
	  	<Code>" . $pv['packagingreferencenumbercode2'] . "</Code>
		<Value>" . $pv['packagingreferencenumbervalue2'] . "</Value>
	  </ReferenceNumber>";
                }
            }
            $data .= array_key_exists('additionalhandling', $pv) ? $pv['additionalhandling'] : '';
            if ($this->includeDimensions == 1) {
                $data .= "<Dimensions>
<UnitOfMeasurement>
<Code>" . $this->unitOfMeasurement . "</Code>";
                if (strlen($this->unitOfMeasurementDescription) > 0) {
                    $data .= "
<Description>" . $this->unitOfMeasurementDescription . "</Description>";
                }
                $data .= "</UnitOfMeasurement>";
                if (isset($pv['length']) && strlen($pv['length']) > 0) {
                    $data .= "<Length>" . $pv['length'] . "</Length>
                            <Width>" . $pv['width'] . "</Width>
                            <Height>" . $pv['height'] . "</Height>";
                }
                $data .= "</Dimensions>";
            }
            $data .= "<PackageWeight>
        <UnitOfMeasurement>
            <Code>" . $this->weightUnits . "</Code>";
            if (strlen($this->weightUnitsDescription) > 0) {
                $data .= "
            <Description>" . $this->weightUnitsDescription . "</Description>";
            }
            $packweight = array_key_exists('packweight', $pv) ? $pv['packweight'] : '';
            $weight = round(($ttWeight * $this->weightUnitKoef + (is_numeric($packweight = str_replace(',', '.', $packweight)) ? $packweight * $this->weightUnitKoef : 0)), 1);
            $data .= "</UnitOfMeasurement>
        <Weight>" . $weight . "</Weight>"
                . $this->largePackageIndicator($pv) . "
      </PackageWeight>
      <PackageServiceOptions>";
            if (isset($pv['insuredmonetaryvalue']) && $pv['insuredmonetaryvalue'] > 0) {
                $data .= "<InsuredValue>
                <CurrencyCode>" . $this->currencyCode . "</CurrencyCode>
                <MonetaryValue>" . (isset($pv['insuredmonetaryvalue']) ? $pv['insuredmonetaryvalue'] : '') . "</MonetaryValue>
                </InsuredValue>
              ";
            }
            $data .= "</PackageServiceOptions>
              </Package>";
            break;
        }
        $data .= "
          </Shipment>
        </ShipmentConfirmRequest>
        ";

        file_put_contents($path_xml . "ShipConfirmRefundRequest.xml", $data);

        $cie = 'wwwcie';
        if (0 == $this->testing) {
            $cie = 'onlinetools';
        }

        $curl = Mage::helper('upslabel/help');
        $curl->testing = !$this->testing;
        $result = $curl->curlSend('https://' . $cie . '.ups.com/ups.app/xml/ShipConfirm', $data);

        if (!$curl->error) {
            file_put_contents($path_xml . "ShipConfirmRefundResponse.xml", $result);
        } else {
            return $result;
        }
        //return $result;
        $xml = simplexml_load_string($result);
        if ($xml->Response->ResponseStatusCode[0] == 1) {
            if ($xml->NegotiatedRates) {
                $shiplabelprice = $xml->NegotiatedRates->NetSummaryCharges->GrandTotal->MonetaryValue[0];
                $shiplabelcurrency = $xml->NegotiatedRates->NetSummaryCharges->GrandTotal->CurrencyCode[0];
            } else {
                $shiplabelprice = $xml->ShipmentCharges->TotalCharges->MonetaryValue[0];
                $shiplabelcurrency = $xml->ShipmentCharges->TotalCharges->CurrencyCode[0];
            }
            $this->shipmentDigest = $xml->ShipmentDigest[0];
            $data = "<?xml version=\"1.0\" ?>
        <AccessRequest xml:lang='en-US'>
        <AccessLicenseNumber>" . $this->AccessLicenseNumber . "</AccessLicenseNumber>
        <UserId>" . $this->UserID . "</UserId>
        <Password>" . $this->Password . "</Password>
        </AccessRequest>
        <?xml version=\"1.0\" ?>
        <ShipmentAcceptRequest>
        <Request>
        <TransactionReference>
        <CustomerContext>" . $this->customerContext . "</CustomerContext>
        <XpciVersion>1.0001</XpciVersion>
        </TransactionReference>
        <RequestAction>ShipAccept</RequestAction>
        </Request>
        <ShipmentDigest>" . $this->shipmentDigest . "</ShipmentDigest>
        </ShipmentAcceptRequest>";

            file_put_contents($path_xml . "ShipAcceptRefundRequest.xml", $data);
            $curl->testing = !$this->testing;
            $result = $curl->curlSend('https://' . $cie . '.ups.com/ups.app/xml/ShipAccept', $data);

            if (!$curl->error) {
                file_put_contents($path_xml . "ShipAcceptRefundResponse.xml", $result);
            } else {
                return $result;
            }
            $xml = simplexml_load_string($result);
            $this->shipmentIdentificationNumber = $xml->ShipmentResults[0]->ShipmentIdentificationNumber[0];
            $i = 0;
            foreach ($xml->ShipmentResults[0]->PackageResults AS $resultXML) {
                $arrResponsXML[$i]['trackingnumber'] = $resultXML->TrackingNumber[0];

                if ($xml->ShipmentResults[$i]->LabelURL) {
                    $arrResponsXML[$i]['type_print'] = "link";
                    $arrResponsXML[$i]['labelname'] = $xml->ShipmentResults[$i]->LabelURL[0];
                } else {
                    $arrResponsXML[$i]['type_print'] = 'virtual';
                    $arrResponsXML[$i]['labelname'] = 'label';
                }
                break;
            }

            if ($this->codMonetaryValue > 999) {
                $htmlHVReport = '<html xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns="http://www.w3.org/TR/REC-html40">
        <head>
        <meta http-equiv=Content-Type content="text/html; charset=windows-1252">
        <meta name=ProgId content=Word.Document>
        <meta name=Generator content="Microsoft Word 11">
        <meta name=Originator content="Microsoft Word 11">
        <link rel=File-List href="sample%20UPS%20CONTROL%20LOG_files/filelist.xml">
        <title>UPS CONTROL LOG </title>
        <style>
        <!--
         /* Style Definitions */
         p.MsoNormal, li.MsoNormal, div.MsoNormal
        	{mso-style-parent:"";
        	margin:0;
        	margin-bottom:.0001pt;
        	mso-pagination:widow-orphan;
        	font-size:10.0pt;
        	mso-bidi-font-size:12.0pt;
        	font-family:Arial;
        	mso-fareast-font-family:"Times New Roman";}
        span.GramE
        	{mso-style-name:"";
        	mso-gram-e:yes;}
        @page Section1
        	{size:8.5in 11.0in;
        	margin:1.0in 1.25in 1.0in 1.25in;
        	mso-header-margin:.5in;
        	mso-footer-margin:.5in;
        	mso-paper-source:0;}
        div.Section1
        	{page:Section1;}
        -->
        </style>
        </head>
        <body lang=EN-US style=\'tab-interval:.5in\'>

        <div class=Section1>

        <p class=MsoNormal>UPS CONTROL <span class=GramE>LOG</span></p>

        <p class=MsoNormal>DATE: ' . date('d') . ' ' . date('M') . ' ' . date('Y') . ' UPS SHIPPER NO. ' . $this->shipperNumber . ' </p>
        <br />
        <br />
        <p class=MsoNormal>TRACKING # PACKAGE ID REFRENCE NUMBER DECLARED VALUE
        CURRENCY </p>
        <p class=MsoNormal>--------------------------------------------------------------------------------------------------------------------------
        </p>
        <br /><br />
        <p class=MsoNormal>' . $this->trackingNumber . ' <span class=GramE>' . $this->packages[0]['packagingreferencenumbervalue'] . ' ' . round($this->codMonetaryValue, 2) . '</span> ' . Mage::app()->getLocale()->currency(Mage::app()->getStore( )->getCurrentCurrencyCode())->getSymbol() . ' </p>
        <br /><br />
        <p class=MsoNormal>Total Number of Declared Value Packages = 1 </p>
        <p class=MsoNormal>--------------------------------------------------------------------------------------------------------------------------
        </p>
        <br /><br />
        <p class=MsoNormal>RECEIVED BY_________________________PICKUP
        TIME__________________PKGS_______ </p>
        </div>
        </body>
        </html>';
                file_put_contents($path . "HVR" . $this->shipmentIdentificationNumber . ".html", $htmlHVReport);
            }
            return array(
                'arrResponsXML' => $arrResponsXML,
                'digest' => '' . $this->shipmentDigest . '',
                'shipidnumber' => '' . $this->shipmentIdentificationNumber . '',
                'price' => array('currency' => $shiplabelcurrency, 'price' => $shiplabelprice),
            );
        } else {
            $error = '<h1>Error</h1> <ul>';
            $errorss = $xml->Response->Error[0];
            $error .= '<li>Error Severity : ' . $errorss->ErrorSeverity . '</li>';
            $error .= '<li>Error Code : ' . $errorss->ErrorCode . '</li>';
            $error .= '<li>Error Description : ' . $errorss->ErrorDescription . '</li>';
            $error .= '</ul>';
            Mage::log($error);
            $error .= '<textarea>' . $result . '</textarea>';
            $error .= '<textarea>' . $data . '</textarea>';
            return array('errordesc' => $errorss->ErrorDescription, 'error' => $error, 'request' => $data, 'response' => $result);
            //return print_r($xml->Response->Error);
        }
    }

    function getShipPrice($type = 'shipment')
    {
        if ($this->credentials != 1) {
            return array('error' => array('cod' => 1, 'message' => 'Not correct registration data'), 'success' => 0);
        }
        $path_upsdir = Mage::getBaseDir('media') . DS . 'upslabel' . DS;
        if (!is_dir($path_upsdir)) {
            mkdir($path_upsdir, 0777);
            mkdir($path_upsdir . "label" . DS, 0777);
            mkdir($path_upsdir . "test_xml" . DS, 0777);
        }
        $path_xml = Mage::getBaseDir('media') . DS . 'upslabel' . DS . "test_xml" . DS;
        if (!file_exists($path_xml . ".htaccess")) {
            file_put_contents($path_xml . ".htaccess", "deny from all");
        }
        $this->customerContext = str_replace('&', '&amp;', strtolower(Mage::app()->getStore( )->getName()));
        $data = "<?xml version=\"1.0\" ?>
<AccessRequest xml:lang='en-US'>
<AccessLicenseNumber>" . $this->AccessLicenseNumber . "</AccessLicenseNumber>
<UserId>" . $this->UserID . "</UserId>
<Password>" . $this->Password . "</Password>
</AccessRequest>
<?xml version=\"1.0\"?>
<RatingServiceSelectionRequest xml:lang=\"en-US\">
  <Request>
    <TransactionReference>
      <CustomerContext>" . $this->customerContext . "</CustomerContext>
      <XpciVersion>1.0</XpciVersion>
    </TransactionReference>
    <RequestAction>Rate</RequestAction>
    <RequestOption>Shop</RequestOption>
  </Request>
  <PickupType>
    <Code>03</Code>
    <Description>Customer Counter</Description>
</PickupType>
  <Shipment>";
        if ($this->negotiated_rates == 1) {
            $data .= "
   <RateInformation>
      <NegotiatedRatesIndicator/>
    </RateInformation>";
        }
        $data .= $this->setShipper($type) . $this->setShipTo($type) . $this->setShipFrom($type);
        if ($this->shipmentcharge == 1) {
            $data .= "<ItemizedPaymentInformation>
            <ShipmentCharge>
      <Type>01</Type>
        <BillShipper>
          <AccountNumber>" . $this->shipperNumber . "</AccountNumber>
        </BillShipper>
      </ShipmentCharge>
      <ShipmentCharge>
      <Type>02</Type>
        <BillShipper>
          <AccountNumber>" . $this->shipperNumber . "</AccountNumber>
        </BillShipper>
      </ShipmentCharge>
    </ItemizedPaymentInformation>
    ";
        } else {
            $data .= "<PaymentInformation>
      <Prepaid>
        <BillShipper>
          <AccountNumber>" . $this->shipperNumber . "</AccountNumber>
        </BillShipper>
      </Prepaid>
    </PaymentInformation>
    ";
        }
        foreach ($this->packages AS $pv) {
            $data .= "<Package>
      <PackagingType>
        <Code>" . $pv["packagingtypecode"] . "</Code>
      </PackagingType>";
            if (($this->shiptoCountryCode == 'US' || $this->shiptoCountryCode == 'PR') && $this->shiptoCountryCode == $this->shipfromCountryCode) {
                $data .= "<ReferenceNumber>
	  	<Code>" . $pv['packagingreferencenumbercode'] . "</Code>
		<Value>" . $pv['packagingreferencenumbervalue'] . "</Value>
	  </ReferenceNumber>";
                if (isset($pv['packagingreferencenumbercode2'])) {
                    $data .= "<ReferenceNumber>
	  	<Code>" . $pv['packagingreferencenumbercode2'] . "</Code>
		<Value>" . $pv['packagingreferencenumbervalue2'] . "</Value>
	  </ReferenceNumber>";
                }
            }
            $data .= array_key_exists('additionalhandling', $pv) ? $pv['additionalhandling'] : '';
            if ($this->includeDimensions == 1) {
                $data .= "<Dimensions>
<UnitOfMeasurement>
<Code>" . $this->unitOfMeasurement . "</Code>";
                if (strlen($this->unitOfMeasurementDescription) > 0) {
                    $data .= "
<Description>" . $this->unitOfMeasurementDescription . "</Description>";
                }
                $data .= "</UnitOfMeasurement>";
                if (isset($pv['length']) && strlen($pv['length']) > 0) {
                    $data .= "<Length>" . $pv['length'] . "</Length>
<Width>" . $pv['width'] . "</Width>
<Height>" . $pv['height'] . "</Height>";
                }
                $data .= "</Dimensions>";
            }
            $data .= "<PackageWeight>
        <UnitOfMeasurement>
            <Code>" . $this->weightUnits . "</Code>";
            if (strlen($this->weightUnitsDescription) > 0) {
                $data .= "
            <Description>" . $this->weightUnitsDescription . "</Description>";
            }
            $packweight = array_key_exists('packweight', $pv) ? $pv['packweight'] : '';
            $weight = array_key_exists('weight', $pv) ? $pv['weight'] : '';
            $weight = round(($weight * $this->weightUnitKoef + (is_numeric($packweight = str_replace(',', '.', $packweight)) ? $packweight * $this->weightUnitKoef : 0)), 1);
            $data .= "</UnitOfMeasurement>
        <Weight>" . $weight . "</Weight>"
                . $this->largePackageIndicator($pv) . "
      </PackageWeight>";
            $isPackageServiceOptions = 0;
            if (array_key_exists('insuredmonetaryvalue', $pv) && $pv['insuredmonetaryvalue'] > 0) {
                if ($isPackageServiceOptions === 0) {
                    $data .= "<PackageServiceOptions>";
                    $isPackageServiceOptions = 1;
                }
                $insuredmonetaryvalue = array_key_exists('insuredmonetaryvalue', $pv) ? $pv['insuredmonetaryvalue'] : '';
                $data .= "<InsuredValue>
                <CurrencyCode>" . $this->currencyCode . "</CurrencyCode>
                <MonetaryValue>" . $insuredmonetaryvalue . "</MonetaryValue>
                </InsuredValue>
              ";
            }
            $cod = array_key_exists('cod', $pv) ? $pv['cod'] : 0;
            if ($cod == 1 && ($this->shiptoCountryCode == 'US' || $this->shiptoCountryCode == 'PR' || $this->shiptoCountryCode == 'CA') && ($this->shipfromCountryCode == 'US' || $this->shipfromCountryCode == 'PR' || $this->shipfromCountryCode == 'CA')) {
                if ($isPackageServiceOptions === 0) {
                    $data .= "<PackageServiceOptions>";
                    $isPackageServiceOptions = 1;
                }
                $codfundscode = array_key_exists('codfundscode', $pv) ? $pv['codfundscode'] : '';
                $codmonetaryvalue = array_key_exists('codmonetaryvalue', $pv) ? $pv['codmonetaryvalue'] : '';
                $data .= "
              <COD>
                  <CODFundsCode>" . $codfundscode . "</CODFundsCode>
                  <CODAmount>
                      <CurrencyCod>" . $this->currencyCode . "</CurrencyCod>
                      <MonetaryValue>" . $codmonetaryvalue . "</MonetaryValue>
                  </CODAmount>
              </COD>";
            }
            if ($isPackageServiceOptions === 1) {
                $data .= "</PackageServiceOptions>";
            }
            $data .= "</Package>";
        }
        $isServiceOptions = 0;
        if ($this->codYesNo == 1 && $this->shiptoCountryCode != 'US' && $this->shiptoCountryCode != 'PR' && $this->shiptoCountryCode != 'CA' && $this->shipfromCountryCode != 'US' && $this->shipfromCountryCode != 'PR' && $this->shipfromCountryCode != 'CA') {
            if ($isServiceOptions === 0) {
                $data .= "<ShipmentServiceOptions>";
                $isServiceOptions = 1;
            }
            $data .= "<COD>
                  <CODFundsCode>" . $this->codFundsCode . "</CODFundsCode>
                  <CODAmount>
                      <CurrencyCod>" . $this->currencyCode . "</CurrencyCod>
                      <MonetaryValue>" . $this->codMonetaryValue . "</MonetaryValue>
                  </CODAmount>
              </COD>";
        }
        if ($this->carbon_neutral == 1) {
            if ($isServiceOptions === 0) {
                $data .= "<ShipmentServiceOptions>";
                $isServiceOptions = 1;
            }
            $data .= "<UPScarbonneutralIndicator/>";
        }
        if ($isServiceOptions === 1) {
            $data .= "</ShipmentServiceOptions>";
        }
        $data .= "</Shipment>
</RatingServiceSelectionRequest>
";
        file_put_contents($path_xml . "RateRequest.xml", $data);
        /*$cie = 'wwwcie';
        if (0 == $this->testing) {*/
        $cie = 'onlinetools';
        /*}*/

        $curl = Mage::helper('upslabel/help');
        $curl->testing = !$this->testing;
        $result = $curl->curlSend('https://' . $cie . '.ups.com/ups.app/xml/Rate', $data);
        file_put_contents($path_xml . "RateResponse.xml", 'https://' . $cie . '.ups.com/ups.app/xml/Rate' . $result);
        if (!$curl->error) {
            $xml = simplexml_load_string($result);
            if (($xml->Response->ResponseStatusCode[0] == 1 || $xml->Response->ResponseStatusCode == 1) && isset($xml->RatedShipment)) {
                $ratedShipmentArray = $this->xml2array($xml);
                Mage::log($ratedShipmentArray);
                $price = NULL;
                if (!isset($ratedShipmentArray['RatedShipment'][0])) {
                    $ratedShipmentArray['RatedShipment'] = array($ratedShipmentArray['RatedShipment']);
                }
                foreach ($ratedShipmentArray['RatedShipment'] AS $ratedShipment) {
                    if ($ratedShipment['Service']['Code'] == $this->serviceCode) {
                        $defaultPrice = $ratedShipment['TotalCharges']['MonetaryValue'];
                        $defaultCurrencyCode = $ratedShipment['TotalCharges']['CurrencyCode'];
                        $priceNegotiatedRates = array();
                        if (isset($ratedShipment['NegotiatedRates']) && isset($ratedShipment['NegotiatedRates']['NetSummaryCharges'])) {
                            $priceNegotiatedRates['MonetaryValue'] = $ratedShipment['NegotiatedRates']['NetSummaryCharges']['GrandTotal']['MonetaryValue'];
                            $priceNegotiatedRates['CurrencyCode'] = $ratedShipment['NegotiatedRates']['NetSummaryCharges']['GrandTotal']['CurrencyCode'];
                        }
                        $price = array(
                            'def' => array('MonetaryValue' => $defaultPrice, 'CurrencyCode' => $defaultCurrencyCode),
                            'negotiated' => $priceNegotiatedRates
                        );
                    }
                }
                return json_encode(array(
                    'price' => $price,
                    'methods' => $ratedShipmentArray['RatedShipment'],
                ));
            } else {
                $error = array('error' => $xml->Response[0]->Error[0]->ErrorDescription[0]);
                return json_encode($error);
            }
        } else {
            $error = array('error' => "cURL error");
            return json_encode($error);
        }
        return $result;
    }

    function getShipPriceFrom($type = 'refund')
    {
        if ($this->credentials != 1) {
            return array('error' => array('cod' => 1, 'message' => 'Not correct registration data'), 'success' => 0);
        }
        $path_upsdir = Mage::getBaseDir('media') . DS . 'upslabel' . DS;
        if (!is_dir($path_upsdir)) {
            mkdir($path_upsdir, 0777);
            mkdir($path_upsdir . "label" . DS, 0777);
            mkdir($path_upsdir . "test_xml" . DS, 0777);
        }
        $path_xml = Mage::getBaseDir('media') . DS . 'upslabel' . DS . "test_xml" . DS;
        if (!file_exists($path_xml . ".htaccess")) {
            file_put_contents($path_xml . ".htaccess", "deny from all");
        }
        $this->customerContext = str_replace('&', '&amp;', strtolower(Mage::app()->getStore( )->getName()));
        $data = "<?xml version=\"1.0\" ?>
        <AccessRequest xml:lang='en-US'>
        <AccessLicenseNumber>" . $this->AccessLicenseNumber . "</AccessLicenseNumber>
        <UserId>" . $this->UserID . "</UserId>
        <Password>" . $this->Password . "</Password>
        </AccessRequest>
        <?xml version=\"1.0\"?>
        <RatingServiceSelectionRequest xml:lang=\"en-US\">
          <Request>
            <TransactionReference>
              <CustomerContext>" . $this->customerContext . "</CustomerContext>
              <XpciVersion/>
            </TransactionReference>
            <RequestAction>Rate</RequestAction>
            <RequestOption>Shop</RequestOption>
          </Request>
          <Shipment>";
        if ($this->negotiated_rates == 1) {
            $data .= "<RateInformation>
      <NegotiatedRatesIndicator/>
    </RateInformation>";
        }
        if (strlen($this->shipmentDescription) > 0) {
            $data .= "<Description>" . $this->shipmentDescription . "</Description>";
        }
        $data .= $this->setShipper($type) . $this->setShipTo($type) . $this->setShipFrom($type) . "
            <Service>
              <Code>" . $this->serviceCode . "</Code>
              <Description>" . $this->serviceDescription . "</Description>
            </Service>";
        $data .= "<ShipmentServiceOptions><ReturnService><Code>" . Mage::getStoreConfig('upslabel/return/return_service_code') . "</Code></ReturnService></ShipmentServiceOptions>";
        $ttWeight = 0;
        foreach ($this->packages AS $pv) {
            $ttWeight += $pv['weight'];
        }
        foreach ($this->packages AS $pv) {
            $data .= "<Package>
      <PackagingType>
        <Code>" . $pv["packagingtypecode"] . "</Code>
      </PackagingType>
      <Description>" . $pv["packagingdescription"] . "</Description>";
            if (($this->shiptoCountryCode == 'US' || $this->shiptoCountryCode == 'PR') && $this->shiptoCountryCode == $this->shipfromCountryCode) {
                $data .= "<ReferenceNumber>
	  	<Code>" . $pv['packagingreferencenumbercode'] . "</Code>
		<Value>" . $pv['packagingreferencenumbervalue'] . "</Value>
	  </ReferenceNumber>";
                if (isset($pv['packagingreferencenumbercode2'])) {
                    $data .= "<ReferenceNumber>
	  	<Code>" . $pv['packagingreferencenumbercode2'] . "</Code>
		<Value>" . $pv['packagingreferencenumbervalue2'] . "</Value>
	  </ReferenceNumber>";
                }
            }
            $data .= array_key_exists('additionalhandling', $pv) ? $pv['additionalhandling'] : '';
            if ($this->includeDimensions == 1) {
                $data .= "<Dimensions>
<UnitOfMeasurement>
<Code>" . $this->unitOfMeasurement . "</Code>";
                if (strlen($this->unitOfMeasurementDescription) > 0) {
                    $data .= "
<Description>" . $this->unitOfMeasurementDescription . "</Description>";
                }
                $data .= "</UnitOfMeasurement>";
                if (isset($pv['length']) && strlen($pv['length']) > 0) {
                    $data .= "<Length>" . $pv['length'] . "</Length>
<Width>" . $pv['width'] . "</Width>
<Height>" . $pv['height'] . "</Height>";
                }
                $data .= "</Dimensions>";
            }
            $data .= "<PackageWeight>
        <UnitOfMeasurement>
            <Code>" . $this->weightUnits . "</Code>";
            if (strlen($this->weightUnitsDescription) > 0) {
                $data .= "
            <Description>" . $this->weightUnitsDescription . "</Description>";
            }

            $weight = round(($ttWeight * $this->weightUnitKoef + (isset($pv['packweight']) && is_numeric($pv['packweight'] = str_replace(',', '.', $pv['packweight'])) ? $pv['packweight'] * $this->weightUnitKoef : 0)), 1);
            $data .= "</UnitOfMeasurement>
        <Weight>" . $weight . "</Weight>"
                . $this->largePackageIndicator($pv) . "
      </PackageWeight>
      <PackageServiceOptions>";
            if ($pv['insuredmonetaryvalue'] > 0) {
                $data .= "<InsuredValue>
                <CurrencyCode>" . $this->currencyCode . "</CurrencyCode>
                <MonetaryValue>" . $pv['insuredmonetaryvalue'] . "</MonetaryValue>
                </InsuredValue>
              ";
            }
            if (isset($pv['cod']) && $pv['cod'] == 1 && ($this->shiptoCountryCode == 'US' || $this->shiptoCountryCode == 'PR' || $this->shiptoCountryCode == 'CA') && ($this->shipfromCountryCode == 'US' || $this->shipfromCountryCode == 'PR' || $this->shipfromCountryCode == 'CA')) {
                $data .= "
              <COD>
                  <CODCode>3</CODCode>
                  <CODFundsCode>0</CODFundsCode>
                  <CODAmount>
                      <CurrencyCod>" . $this->currencyCode . "</CurrencyCod>
                      <MonetaryValue>" . $pv['codmonetaryvalue'] . "</MonetaryValue>
                  </CODAmount>
              </COD>";
            }
            $data .= "</PackageServiceOptions>
              </Package>";
            break;
        }
        $data .= "</Shipment>
        </RatingServiceSelectionRequest>
        ";

        file_put_contents($path_xml . "RateReturnRequest.xml", $data);

        /*$cie = 'wwwcie';
        if (0 == $this->testing) {*/
        $cie = 'onlinetools';
        /*}*/

        $curl = Mage::helper('upslabel/help');
        $curl->testing = !$this->testing;
        $result = $curl->curlSend('https://' . $cie . '.ups.com/ups.app/xml/Rate', $data);
        file_put_contents($path_xml . "RateReturnResponse.xml", $result);
        if (!$curl->error) {
            $xml = simplexml_load_string($result);
            if (($xml->Response->ResponseStatusCode[0] == 1 || $xml->Response->ResponseStatusCode == 1) && isset($xml->RatedShipment)) {
                $ratedShipmentArray = $this->xml2array($xml);
                $price = NULL;
                foreach ($ratedShipmentArray['RatedShipment'] AS $ratedShipment) {
                    if ($ratedShipment['Service']['Code'] == $this->serviceCode) {
                        $defaultPrice = $ratedShipment['TotalCharges']['MonetaryValue'];
                        $defaultCurrencyCode = $ratedShipment['TotalCharges']['CurrencyCode'];
                        $priceNegotiatedRates = array();
                        if (isset($ratedShipment['NegotiatedRates']) && isset($ratedShipment['NegotiatedRates']['NetSummaryCharges'])) {
                            $priceNegotiatedRates['MonetaryValue'] = $ratedShipment['NegotiatedRates']['NetSummaryCharges']['GrandTotal']['MonetaryValue'];
                            $priceNegotiatedRates['CurrencyCode'] = $ratedShipment['NegotiatedRates']['NetSummaryCharges']['GrandTotal']['CurrencyCode'];
                        }
                        $price = array(
                            'def' => array('MonetaryValue' => $defaultPrice, 'CurrencyCode' => $defaultCurrencyCode),
                            'negotiated' => $priceNegotiatedRates
                        );
                    }
                }
                return json_encode(array(
                    'price' => $price,
                    'methods' => $ratedShipmentArray['RatedShipment'],
                ));
                $error = array('error' => array(Mage::helper('upslabel')->__('An incorrect UPS shipping method')));
                return json_encode($error);
            } else {
                $error = array('error' => $xml->Response[0]->Error[0]->ErrorDescription[0]);
                return json_encode($error);
            }
        }
        return $result;
    }

    public function deleteLabel($trnum)
    {
        $path_xml = Mage::getBaseDir('media') . DS . 'upslabel' . DS . "test_xml" . DS;
        $cie = 'wwwcie';
        $testing = $this->testing;
        $shipIndefNumbr = $trnum;
        if (0 == $testing) {
            $cie = 'onlinetools';
        } else {
            /*$trnum = '1Z2220060291994175';*/
            $shipIndefNumbr = '1ZISDE016691676846';
        }
        $data = "<?xml version=\"1.0\" ?>
<AccessRequest xml:lang='en-US'>
<AccessLicenseNumber>" . $this->AccessLicenseNumber . "</AccessLicenseNumber>
<UserId>" . $this->UserID . "</UserId>
<Password>" . $this->Password . "</Password>
</AccessRequest>
<?xml version=\"1.0\" ?>
<VoidShipmentRequest>
<Request>
<RequestAction>1</RequestAction>
</Request>
<ShipmentIdentificationNumber>" . $shipIndefNumbr . "</ShipmentIdentificationNumber>
    <ExpandedVoidShipment>
          <ShipmentIdentificationNumber>" . $shipIndefNumbr . "</ShipmentIdentificationNumber>
          </ExpandedVoidShipment>
</VoidShipmentRequest> ";
        /*<TrackingNumber>" . $trnum . "</TrackingNumber>*/
        /*  */
        file_put_contents($path_xml . "VoidShipmentRequest.xml", $data);
        $curl = Mage::helper('upslabel/help');
        $curl->testing = !$this->testing;
        $result = $curl->curlSend('https://' . $cie . '.ups.com/ups.app/xml/Void', $data);
        if (!$curl->error) {
            file_put_contents($path_xml . "VoidShipmentResponse.xml", $result);
            $xml = simplexml_load_string($result);
            if ($xml->Response->Error[0] && (int)$xml->Response->Error[0]->ErrorCode != 190117) {
                $error = '<h1>Error</h1> <ul>';
                $errorss = $xml->Response->Error[0];
                $error .= '<li>Error Severity : ' . $errorss->ErrorSeverity . '</li>';
                $error .= '<li>Error Code : ' . $errorss->ErrorCode . '</li>';
                $error .= '<li>Error Description : ' . $errorss->ErrorDescription . '</li>';
                $error .= '</ul>';
                $error .= '<textarea>' . $result . '</textarea>';
                $error .= '<textarea>' . $data . '</textarea>';
                return array('error' => $error);
            } else {
                return true;
            }
        } else {
            return $result;
        }
    }

    function getPickup()
    {
        if ($this->credentials != 1) {
            return array('error' => array('cod' => 1, 'message' => 'Not correct registration data'), 'success' => 0);
        }
        /* if(is_dir($filename)){} */
        $path_upsdir = Mage::getBaseDir('media') . DS . 'upslabel' . DS;
        if (!is_dir($path_upsdir)) {
            mkdir($path_upsdir, 0777);
            mkdir($path_upsdir . "label" . DS, 0777);
            mkdir($path_upsdir . "test_xml" . DS, 0777);
        }
        $path_xml = Mage::getBaseDir('media') . DS . 'upslabel' . DS . "test_xml" . DS;
        if (!file_exists($path_xml . ".htaccess")) {
            file_put_contents($path_xml . ".htaccess", "deny from all");
        }
        $this->customerContext = str_replace('&', '&amp;', strtolower(Mage::app()->getStore( )->getName()));

        $data = '<envr:Envelope xmlns:envr="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:common="http://www.ups.com/XMLSchema/XOLTWS/Common/v1.0" xmlns:wsf="http://www.ups.com/schema/wsf" xmlns:upss="http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0">
	<envr:Header>
		<upss:UPSSecurity>
			<upss:UsernameToken>
				<upss:Username>' . $this->UserID . '</upss:Username>
				<upss:Password>' . $this->Password . '</upss:Password>
			</upss:UsernameToken>
			<upss:ServiceAccessToken>
				<upss:AccessLicenseNumber>' . $this->AccessLicenseNumber . '</upss:AccessLicenseNumber>
			</upss:ServiceAccessToken>
		</upss:UPSSecurity>
		<common:ClientInformation>
			<common:Property Key="DataSource">AG</common:Property>
			<common:Property Key="ClientCode">APS</common:Property>
		</common:ClientInformation>
	</envr:Header>';
        $data .= "<envr:Body><PickupCreationRequest xmlns=\"http://www.ups.com/XMLSchema/XOLTWS/Pickup/v1.1\" xmlns:common=\"http://www.ups.com/XMLSchema/XOLTWS/Common/v1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
    <RatePickupIndicator>" . $this->RatePickupIndicator . "</RatePickupIndicator>
    <Shipper>
        <Account>
            <AccountNumber>" . $this->shipperNumber . "</AccountNumber>
            <AccountCountryCode>" . $this->shipperCountryCode . "</AccountCountryCode>
        </Account>
    </Shipper>
    <PickupDateInfo>
        <CloseTime>" . str_replace(",", "", substr($this->CloseTime, 0, 5)) . "</CloseTime>
        <ReadyTime>" . str_replace(",", "", substr($this->ReadyTime, 0, 5)) . "</ReadyTime>
        <PickupDate>" . ($this->PickupDateYear . $this->PickupDateMonth . $this->PickupDateDay) . "</PickupDate>
    </PickupDateInfo>
    <PickupAddress>
        <CompanyName>" . $this->shipfromCompanyName . "</CompanyName>
        <ContactName>" . $this->shipfromAttentionName . "</ContactName>
        <AddressLine>" . $this->shipfromAddressLine1 . "</AddressLine>";
        if (strlen($this->room) > 0) {
            $data .= "<Room>" . $this->room . "</Room>";
        }
        if (strlen($this->floor) > 0) {
            $data .= "<Floor>" . $this->floor . "</Floor>";
        }
        $data .= "<City>" . $this->shipfromCity . "</City>";
        if (strlen($this->shipfromStateProvinceCode) > 0) {
            $data .= "<StateProvince>" . $this->shipfromStateProvinceCode . "</StateProvince>";
        }
        if (strlen($this->urbanization) > 0) {
            $data .= "<Urbanization>" . $this->urbanization . "</Urbanization>";
        }
        $data .= "<PostalCode>" . $this->shipfromPostalCode . "</PostalCode>
        <CountryCode>" . $this->shipfromCountryCode . "</CountryCode>
        <ResidentialIndicator>" . $this->residential . "</ResidentialIndicator>";
        if (strlen($this->pickup_point) > 0) {
            $data .= "<PickupPoint>" . $this->pickup_point . "</PickupPoint>";
        }
        $data .= "<Phone><Number>" . $this->shipfromPhoneNumber . "</Number></Phone>
    </PickupAddress>
    <AlternateAddressIndicator>" . $this->AlternateAddressIndicator . "</AlternateAddressIndicator>
    <PickupPiece>
        <ServiceCode>" . $this->ServiceCode . "</ServiceCode>
        <Quantity>" . $this->Quantity . "</Quantity>
        <DestinationCountryCode>" . $this->DestinationCountryCode . "</DestinationCountryCode>
        <ContainerCode>" . $this->ContainerCode . "</ContainerCode>
    </PickupPiece>";
        if (strlen($this->Weight) > 0) {
            $data .= "<TotalWeight>
            <Weight>" . $this->Weight . "</Weight>
            <UnitOfMeasurement>" . $this->UnitOfMeasurement . "</UnitOfMeasurement>
            <OverweightIndicator>" . $this->OverweightIndicator . "</OverweightIndicator>
        </TotalWeight>";
        }
        $data .= "
    <PaymentMethod>" . $this->PaymentMethod . "</PaymentMethod>
    ";
        if (strlen($this->SpecialInstruction) > 0) {
            $data .= "<SpecialInstruction>" . $this->SpecialInstruction . "</SpecialInstruction>";
        }
        if (strlen($this->ReferenceNumber) > 0) {
            $data .= "<ReferenceNumber>" . $this->ReferenceNumber . "</ReferenceNumber>";
        }
        if ($this->Notification == 1) {
            $data .= "<Notification>";
            $confirmEmail = explode(",", $this->ConfirmationEmailAddress);
            if (count($confirmEmail) > 0) {
                foreach ($confirmEmail AS $v) {
                    $data .= "<ConfirmationEmailAddress>" . trim($v) . "</ConfirmationEmailAddress>";
                }
            }
            $data .= "<UndeliverableEmailAddress>" . $this->UndeliverableEmailAddress . "</UndeliverableEmailAddress>";
            $data .= "</Notification>";
        }
        $data .= "
</PickupCreationRequest></envr:Body>
</envr:Envelope>";
        file_put_contents($path_xml . "PickupRequest.xml", $data);
        $cie = 'wwwcie';
        if (0 == $this->testing) {
            $cie = 'onlinetools';
        }
        $curl = Mage::helper('upslabel/help');
        $result = $curl->curlSetOption('https://' . $cie . '.ups.com/webservices/Pickup', $data);
        $result = strstr($result, '<soapenv:');
        if ($result) {
            file_put_contents($path_xml . "PickupResponse.xml", $result);
        }
        //return $result;
        $xml = simplexml_load_string($result);
        $soap = $xml->children('soapenv', true)->Body[0];
        if (!isset($soap->children('soapenv', true)->Fault)) {
            $response = $soap->children('pkup', true);
            if ($response->children('common', true)->Response[0]->ResponseStatus[0]->Code[0] == 1
                && $response->children('common', true)->Response[0]->ResponseStatus[0]->Description[0] == "Success"
            ) {
                return array(
                    'Description' => $response->children('common', true)->Response[0]->ResponseStatus[0]->Description[0],
                    'data' => $data,
                    'response' => $result
                );
            }
        } else {
            $error = '<h1>Error</h1> <ul>';
            $errorss = $soap->Fault[0]->children()->detail[0]->children('err', true)->Errors[0]->ErrorDetail[0];
            $error .= '<li>Error Severity : ' . $errorss->Severity[0] . '</li>';
            $error .= '<li>Error Code : ' . $errorss->PrimaryErrorCode[0]->Code[0] . '</li>';
            $error .= '<li>Error Description : ' . $errorss->PrimaryErrorCode[0]->Description[0] . '</li>';
            $error .= '</ul>';
            $error .= '<textarea>' . $result . '</textarea>';
            $error .= '<textarea>' . $data . '</textarea>';
            return array('error' => $error, 'data' => $data, 'response' => $result);
            //return print_r($xml->Response->Error);
        }
    }

    function cancelPickup($PRN)
    {
        if ($this->credentials != 1) {
            return array('error' => array('cod' => 1, 'message' => 'Not correct registration data'), 'success' => 0);
        }
        /* if(is_dir($filename)){} */
        $path_upsdir = Mage::getBaseDir('media') . DS . 'upslabel' . DS;
        if (!is_dir($path_upsdir)) {
            mkdir($path_upsdir, 0777);
            mkdir($path_upsdir . DS . "label" . DS, 0777);
            mkdir($path_upsdir . DS . "test_xml" . DS, 0777);
        }
        $path_xml = Mage::getBaseDir('media') . DS . 'upslabel' . DS . "test_xml" . DS;
        if (!file_exists($path_xml . ".htaccess")) {
            file_put_contents($path_xml . ".htaccess", "deny from all");
        }
        $cie = 'wwwcie';
        if (0 == $this->testing) {
            $cie = 'onlinetools';
            /*$PRN = '02';*/
        }
        /*else {
            $PRN = '2929602E9CP';
        }*/
        $data = '<envr:Envelope xmlns:envr="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:common="http://www.ups.com/XMLSchema/XOLTWS/Common/v1.0" xmlns:wsf="http://www.ups.com/schema/wsf" xmlns:upss="http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0">
	<envr:Header>
		<upss:UPSSecurity>
			<upss:UsernameToken>
				<upss:Username>' . $this->UserID . '</upss:Username>
				<upss:Password>' . $this->Password . '</upss:Password>
			</upss:UsernameToken>
			<upss:ServiceAccessToken>
				<upss:AccessLicenseNumber>' . $this->AccessLicenseNumber . '</upss:AccessLicenseNumber>
			</upss:ServiceAccessToken>
		</upss:UPSSecurity>
		<common:ClientInformation>
			<common:Property Key="DataSource">AG</common:Property>
			<common:Property Key="ClientCode">APS</common:Property>
		</common:ClientInformation>
	</envr:Header>';
        $data .= "<envr:Body><PickupCancelRequest xmlns=\"http://www.ups.com/XMLSchema/XOLTWS/Pickup/v1.1\" xmlns:common=\"http://www.ups.com/XMLSchema/XOLTWS/Common/v1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
        <Request></Request>
        <CancelBy>02</CancelBy>
        <PRN>" . $PRN . "</PRN>";
        $data .= "</PickupCancelRequest></envr:Body>
</envr:Envelope>";
        file_put_contents($path_xml . "PickupCancelRequest.xml", $data);

        $curl = Mage::helper('upslabel/help');
        $result = $curl->curlSetOption('https://' . $cie . '.ups.com/webservices/Pickup', $data);
        $result = strstr($result, '<soapenv:');
        if ($result) {
            file_put_contents($path_xml . "PickupCancelResponse.xml", $result);
        }

        $xml = simplexml_load_string($result);
        $soap = $xml->children('soapenv', true)->Body[0];
        $response = $soap->children('pkup', true);
        if ($response->children('common', true)->Response[0]->ResponseStatus[0]->Code[0] == 1 && $response->children('common', true)->Response[0]->ResponseStatus[0]->Description[0] == "Success") {
            return array(
                'Description' => "Canceled",
                'data' => $data,
                'response' => $result
            );
        } else {
            $error = '<h1>Error</h1> <ul>';
            $errorss = $soap->Fault[0]->children()->detail[0]->children('err', true)->Errors[0]->ErrorDetail[0];
            $error .= '<li>Error Severity : ' . $errorss->Severity[0] . '</li>';
            $error .= '<li>Error Code : ' . $errorss->PrimaryErrorCode[0]->Code[0] . '</li>';
            $error .= '<li>Error Description : ' . $errorss->PrimaryErrorCode[0]->Description[0] . '</li>';
            $error .= '</ul>';
            $error .= '<textarea>' . $result . '</textarea>';
            $error .= '<textarea>' . $data . '</textarea>';
            return array('error' => $error, 'data' => $data, 'response' => $result);
            //return print_r($xml->Response->Error);
        }
    }

    function statusPickup()
    {
        if ($this->credentials != 1) {
            return array('error' => array('cod' => 1, 'message' => 'Not correct registration data'), 'success' => 0);
        }
        /* if(is_dir($filename)){} */
        $path_upsdir = Mage::getBaseDir('media') . DS . 'upslabel' . DS;
        if (!is_dir($path_upsdir)) {
            mkdir($path_upsdir, 0777);
            mkdir($path_upsdir . DS . "label" . DS, 0777);
            mkdir($path_upsdir . DS . "test_xml" . DS, 0777);
        }
        $path_xml = Mage::getBaseDir('media') . DS . 'upslabel' . DS . "test_xml" . DS;
        if (!file_exists($path_xml . ".htaccess")) {
            file_put_contents($path_xml . ".htaccess", "deny from all");
        }
        $data = '<envr:Envelope xmlns:envr="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:common="http://www.ups.com/XMLSchema/XOLTWS/Common/v1.0" xmlns:wsf="http://www.ups.com/schema/wsf" xmlns:upss="http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0">
	<envr:Header>
		<upss:UPSSecurity>
			<upss:UsernameToken>
				<upss:Username>' . $this->UserID . '</upss:Username>
				<upss:Password>' . $this->Password . '</upss:Password>
			</upss:UsernameToken>
			<upss:ServiceAccessToken>
				<upss:AccessLicenseNumber>' . $this->AccessLicenseNumber . '</upss:AccessLicenseNumber>
			</upss:ServiceAccessToken>
		</upss:UPSSecurity>
		<common:ClientInformation>
			<common:Property Key="DataSource">AG</common:Property>
			<common:Property Key="ClientCode">APS</common:Property>
		</common:ClientInformation>
	</envr:Header>';
        $data .= "<envr:Body><PickupPendingStatusRequest xmlns=\"http://www.ups.com/XMLSchema/XOLTWS/Pickup/v1.1\" xmlns:common=\"http://www.ups.com/XMLSchema/XOLTWS/Common/v1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
        <Request></Request>
        <PickupType>01</PickupType>
        <AccountNumber>" . $this->shipperNumber . "</AccountNumber>";
        $data .= "</PickupPendingStatusRequest></envr:Body>
</envr:Envelope>";
        file_put_contents($path_xml . "PickupPendingStatusRequest.xml", $data);
        $cie = 'wwwcie';
        if (0 == $this->testing) {
            $cie = 'onlinetools';
        }

        $curl = Mage::helper('upslabel/help');
        $result = $curl->curlSetOption('https://' . $cie . '.ups.com/webservices/Pickup', $data);
        $result = strstr($result, '<soapenv:');
        if ($result) {
            file_put_contents($path_xml . "PickupPendingStatusResponse.xml", $result);
        }
        $xml = simplexml_load_string($result);
        $soap = $xml->children('soapenv', true)->Body[0];
        $response = $soap->children('pkup', true);
        if ($response->children('common', true)->Response[0]->ResponseStatus[0]->Code[0] == 1 && $response->children('common', true)->Response[0]->ResponseStatus[0]->Description[0] == "Success") {
            return array(
                'Description' => "Canceled",
                'data' => $data,
                'response' => $result
            );
        } else {
            $error = '<h1>Error</h1> <ul>';
            $errorss = $soap->Fault[0]->children()->detail[0]->children('err', true)->Errors[0]->ErrorDetail[0];
            $error .= '<li>Error Severity : ' . $errorss->Severity[0] . '</li>';
            $error .= '<li>Error Code : ' . $errorss->PrimaryErrorCode[0]->Code[0] . '</li>';
            $error .= '<li>Error Description : ' . $errorss->PrimaryErrorCode[0]->Description[0] . '</li>';
            $error .= '</ul>';
            $error .= '<textarea>' . $result . '</textarea>';
            $error .= '<textarea>' . $data . '</textarea>';
            return array('error' => $error, 'data' => $data, 'response' => $result);
            //return print_r($xml->Response->Error);
        }
    }

    function ratePickup()
    {
        if ($this->credentials != 1) {
            return array('error' => array('cod' => 1, 'message' => 'Not correct registration data'), 'success' => 0);
        }
        $data = '<envr:Envelope xmlns:envr="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:common="http://www.ups.com/XMLSchema/XOLTWS/Common/v1.0" xmlns:wsf="http://www.ups.com/schema/wsf" xmlns:upss="http://www.ups.com/XMLSchema/XOLTWS/UPSS/v1.0">
	<envr:Header>
		<upss:UPSSecurity>
			<upss:UsernameToken>
				<upss:Username>' . $this->UserID . '</upss:Username>
				<upss:Password>' . $this->Password . '</upss:Password>
			</upss:UsernameToken>
			<upss:ServiceAccessToken>
				<upss:AccessLicenseNumber>' . $this->AccessLicenseNumber . '</upss:AccessLicenseNumber>
			</upss:ServiceAccessToken>
		</upss:UPSSecurity>
		<common:ClientInformation>
			<common:Property Key="DataSource">AG</common:Property>
			<common:Property Key="ClientCode">APS</common:Property>
		</common:ClientInformation>
	</envr:Header>';
        $data .= "<envr:Body><PickupRateRequest xmlns=\"http://www.ups.com/XMLSchema/XOLTWS/Pickup/v1.1\" xmlns:common=\"http://www.ups.com/XMLSchema/XOLTWS/Common/v1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">
        <Request></Request>
    <PickupAddress>
        <AddressLine>" . $this->shipfromAddressLine1 . "</AddressLine>";
        $data .= "<City>" . $this->shipfromCity . "</City>";
        $data .= "<StateProvince>" . $this->shipfromStateProvinceCode . "</StateProvince>";
        $data .= "<PostalCode>" . $this->shipfromPostalCode . "</PostalCode>
        <CountryCode>" . $this->shipfromCountryCode . "</CountryCode>
        <ResidentialIndicator>" . $this->residential . "</ResidentialIndicator>";
        $data .= "</PickupAddress>
    <AlternateAddressIndicator>" . $this->AlternateAddressIndicator . "</AlternateAddressIndicator>
    <ServiceDateOption>" . ($this->PickupDateYear . $this->PickupDateMonth . $this->PickupDateDay == date("Ymd") ? "01" : "02") . "</ServiceDateOption>
    <PickupDateInfo>
        <CloseTime>" . str_replace(",", "", substr($this->CloseTime, 0, 5)) . "</CloseTime>
        <ReadyTime>" . str_replace(",", "", substr($this->ReadyTime, 0, 5)) . "</ReadyTime>
        <PickupDate>" . ($this->PickupDateYear . $this->PickupDateMonth . $this->PickupDateDay) . "</PickupDate>
    </PickupDateInfo>";
        $data .= "</PickupRateRequest></envr:Body>
</envr:Envelope>";
        $cie = 'wwwcie';
        if (0 == $this->testing) {
            $cie = 'onlinetools';
        }
        $curl = Mage::helper('upslabel/help');
        $result = $curl->curlSetOption('https://' . $cie . '.ups.com/webservices/Pickup', $data);
        $result = strstr($result, '<soapenv:');
        $xml = simplexml_load_string($result);
        $soap = $xml->children('soapenv', true);
        if ($soap->count() > 0) {
            $soap = $soap->Body[0];
            $response = $soap->children('pkup', true);
            if ($response->count() > 0) {
                $common = $response->children('common', true);
                if ($common->count() > 0 && $common->Response[0]->ResponseStatus[0]->Code[0] == 1 && $common->Response[0]->ResponseStatus[0]->Description[0] == "Success") {
                    $pukp = $soap->children('pkup', true);
                    if ($pukp->count() > 0) {
                        return $pukp->PickupRateResponse[0]->RateResult[0]->GrandTotalOfAllCharge;
                    }
                }
            }
        }
        return false;
    }

    function getShipRate()
    {
        $this->customerContext = str_replace('&', '&amp;', strtolower(Mage::app()->getStore()->getName()));
        $weightSum = 0;
        $data = "<?xml version=\"1.0\" ?>
<AccessRequest xml:lang='en-US'>
<AccessLicenseNumber>" . $this->AccessLicenseNumber . "</AccessLicenseNumber>
<UserId>" . $this->UserID . "</UserId>
<Password>" . $this->Password . "</Password>
</AccessRequest>
<?xml version=\"1.0\"?>
<RatingServiceSelectionRequest xml:lang=\"en-US\">
  <Request>
    <TransactionReference>
      <CustomerContext>Rating and Service</CustomerContext>
      <XpciVersion>1.0</XpciVersion>
    </TransactionReference>
    <RequestAction>Rate</RequestAction>
    <RequestOption>Shop</RequestOption>
  </Request>
  <PickupType>
          <Code>03</Code>
          <Description>Customer Counter</Description>
  </PickupType>
  <Shipment>";
        if ($this->negotiated_rates == 1) {
            $data .= "
   <RateInformation>
      <NegotiatedRatesIndicator/>
    </RateInformation>";
        }
        $data .= "<Shipper>";
        $data .= "<ShipperNumber>" . $this->shipperNumber . "</ShipperNumber>
      <Address>
    	<City>" . $this->shipperCity . "</City>
    	<StateProvinceCode>" . $this->shipperStateProvinceCode . "</StateProvinceCode>
    	<PostalCode>" . $this->shipperPostalCode . "</PostalCode>
    	<CountryCode>" . $this->shipperCountryCode . "</CountryCode>
     </Address>
    </Shipper>
	<ShipTo>
      <Address>
        <StateProvinceCode>" . $this->shiptoStateProvinceCode . "</StateProvinceCode>
        <PostalCode>" . $this->shiptoPostalCode . "</PostalCode>
        <CountryCode>" . $this->shiptoCountryCode . "</CountryCode>
        <ResidentialAddress>02</ResidentialAddress>
      </Address>
    </ShipTo>
    <ShipFrom>
      <Address>
    	<StateProvinceCode>" . $this->shipfromStateProvinceCode . "</StateProvinceCode>
    	<PostalCode>" . $this->shipfromPostalCode . "</PostalCode>
    	<CountryCode>" . $this->shipfromCountryCode . "</CountryCode>
      </Address>
    </ShipFrom>";
        foreach ($this->packages AS $pv) {
            $data .= "<Package>
      <PackagingType>
        <Code>" . $pv["packagingtypecode"] . "</Code>
      </PackagingType>";
            $data .= array_key_exists('additionalhandling', $pv) ? $pv['additionalhandling'] : '';
            if ($this->includeDimensions == 1) {
                $data .= "<Dimensions>
<UnitOfMeasurement>
<Code>" . $this->unitOfMeasurement . "</Code>";
                if (strlen($this->unitOfMeasurementDescription) > 0) {
                    $data .= "
<Description>" . $this->unitOfMeasurementDescription . "</Description>";
                }
                $data .= "</UnitOfMeasurement>";
                if (isset($pv['length']) && strlen($pv['length']) > 0) {
                    $data .= "<Length>" . $pv['length'] . "</Length>
<Width>" . $pv['width'] . "</Width>
<Height>" . $pv['height'] . "</Height>";
                }
                $data .= "</Dimensions>";
            }
            $data .= "<PackageWeight>
        <UnitOfMeasurement>
            <Code>" . $this->weightUnits . "</Code>";
            $packweight = array_key_exists('packweight', $pv) ? $pv['packweight'] : '';
            $weight = array_key_exists('weight', $pv) ? $pv['weight'] : '';
            $weightSum += $weight;
            $weight = round(($weight * $this->weightUnitKoef + (is_numeric($packweight = str_replace(',', '.', $packweight)) ? $packweight * $this->weightUnitKoef : 0)), 1);
            $data .= "</UnitOfMeasurement>
        <Weight>" . $weight . "</Weight>"
                . $this->largePackageIndicator($pv) . "
      </PackageWeight>
              </Package>";
        }
        $data .= "</Shipment></RatingServiceSelectionRequest>";
        Mage::log($data);
        $cie = 'onlinetools';
        $curl = Mage::helper('upslabel/help');
        $curl->testing = 1;
        $result = $curl->curlSend('https://' . $cie . '.ups.com/ups.app/xml/Rate', $data);
        //return $data;
        if (!$curl->error) {
            $xml = $this->xml2array(simplexml_load_string($result));
            if (isset($xml['Response']['ResponseStatusCode']) && $xml['Response']['ResponseStatusCode'] == 1) {
                $rates = array();
                $timeInTransit = null;
                if (!isset($xml['RatedShipment'][0])) {
                    $xml['RatedShipment'] = array($xml['RatedShipment']);
                }
                foreach ($xml['RatedShipment'] AS $rated) {
                    $rateCode = (string)$rated['Service']['Code'];

                    /*$time = (string)$rated->GuaranteedDaysToDelivery;
                    if ($rated->Service[0]->Code[0] == $this->serviceCode) {*/

                    $defaultPrice = $rated['TotalCharges']['MonetaryValue'];
                    $defaultCurrency = $rated['TotalCharges']['CurrencyCode'];
                    if (!isset($rated['NegotiatedRates'])) {
                        $rates[$rateCode] = array(
                            'price' => $defaultPrice,
                            'currency' => $defaultCurrency,
                        );
                    } else {
                        $defaultPrice = $rated['NegotiatedRates']['NetSummaryCharges']['GrandTotal']['MonetaryValue'];
                        $defaultCurrency = $rated['NegotiatedRates']['NetSummaryCharges']['GrandTotal']['CurrencyCode'];
                        if ($this->rates_tax == 1) {
                            $defaultPrice2 = $rated['NegotiatedRates']['NetSummaryCharges']['TotalChargesWithTaxes']['MonetaryValue'];
                            $defaultCurrency2 = $rated['NegotiatedRates']['NetSummaryCharges']['TotalChargesWithTaxes']['CurrencyCode'];
                            if ($defaultPrice2) {
                                $defaultPrice = $defaultPrice2;
                                $defaultCurrency = $defaultCurrency2;
                            }
                        }
                        $rates[$rateCode] = array(
                            'price' => $defaultPrice,
                            'currency' => $defaultCurrency,
                        );
                    }
                    /*}*/
                    if ($timeInTransit === null) {
                        $timeInTransit = $this->timeInTransit($weightSum * $this->weightUnitKoef);
                    }
                    if (is_array($timeInTransit) && isset($timeInTransit['days'][$rateCode])) {
                        $rates[$rateCode]['day'] = $timeInTransit['days'][$rateCode];
                    }
                }
                return $rates;
            } else {
                $error = array('error' => $xml['Response']['Error']['ErrorDescription']);
                return $error;
            }
        } else {
            Mage::log('CUrl Error');
        }
        return false;
    }

    public function getTrackStatus($trnum)
    {
        $cie = 'wwwcie';
        $testing = $this->testing;
        if (0 == $testing) {
            $cie = 'onlinetools';
        }
        $this->customerContext = str_replace('&', '&amp;', strtolower(Mage::app()->getStore(1)->getName()));
        $data = "<?xml version=\"1.0\" ?>
<AccessRequest xml:lang='en-US'>
<AccessLicenseNumber>" . $this->AccessLicenseNumber . "</AccessLicenseNumber>
<UserId>" . $this->UserID . "</UserId>
<Password>" . $this->Password . "</Password>
</AccessRequest>
<?xml version=\"1.0\" ?>
<TrackRequest xml:lang=\"en-US\">
<Request>
<TransactionReference>
<CustomerContext>" . $this->customerContext . "</CustomerContext>
<XpciVersion>1.0</XpciVersion>
</TransactionReference>
<RequestAction>Track</RequestAction>
<RequestOption>activity</RequestOption>
</Request>
<TrackingNumber>" . $trnum . "</TrackingNumber>
</TrackRequest>";
        $curl = Mage::helper('upslabel/help');
        $curl->testing = !$this->testing;
        $result = $curl->curlSend('https://' . $cie . '.ups.com/ups.app/xml/Track', $data);
        $xml = new Varien_Simplexml_Config();
        $xml->loadString($result);
        $arr = $xml->getXpath("//TrackResponse/Response/ResponseStatusCode/text()");
        $success = (int)$arr[0][0];
        $resultArr = array();
        $packageProgress = array();
        if ($success === 1) {
            $arr = $xml->getXpath("//TrackResponse/Shipment/Service/Description/text()");
            $resultArr['service'] = (string)$arr[0];

            $arr = $xml->getXpath("//TrackResponse/Shipment/PickupDate/text()");
            $resultArr['shippeddate'] = (string)$arr[0];

            $arr = $xml->getXpath("//TrackResponse/Shipment/Package/PackageWeight/Weight/text()");
            $weight = (string)$arr[0];

            $arr = $xml->getXpath("//TrackResponse/Shipment/Package/PackageWeight/UnitOfMeasurement/Code/text()");
            $unit = (string)$arr[0];

            $resultArr['weight'] = "{$weight} {$unit}";

            $activityTags = $xml->getXpath("//TrackResponse/Shipment/Package/Activity");
            if ($activityTags) {
                $i = 1;
                foreach ($activityTags as $activityTag) {
                    $addArr = array();
                    if (isset($activityTag->ActivityLocation->Address->City)) {
                        $addArr[] = (string)$activityTag->ActivityLocation->Address->City;
                    }
                    if (isset($activityTag->ActivityLocation->Address->StateProvinceCode)) {
                        $addArr[] = (string)$activityTag->ActivityLocation->Address->StateProvinceCode;
                    }
                    if (isset($activityTag->ActivityLocation->Address->CountryCode)) {
                        $addArr[] = (string)$activityTag->ActivityLocation->Address->CountryCode;
                    }
                    $dateArr = array();
                    $date = (string)$activityTag->Date; //YYYYMMDD
                    $dateArr[] = substr($date, 0, 4);
                    $dateArr[] = substr($date, 4, 2);
                    $dateArr[] = substr($date, -2, 2);

                    $timeArr = array();
                    $time = (string)$activityTag->Time; //HHMMSS
                    $timeArr[] = substr($time, 0, 2);
                    $timeArr[] = substr($time, 2, 2);
                    $timeArr[] = substr($time, -2, 2);

                    if ($i == 1) {
                        $resultArr['status'] = (string)$activityTag->Status->StatusType->Description;
                        $resultArr['deliverydate'] = implode('-', $dateArr); //YYYY-MM-DD
                        $resultArr['deliverytime'] = implode(':', $timeArr); //HH:MM:SS
                        $resultArr['deliverylocation'] = (string)$activityTag->ActivityLocation->Description;
                        $resultArr['signedby'] = (string)$activityTag->ActivityLocation->SignedForByName;
                        if ($addArr) {
                            $resultArr['deliveryto'] = implode(', ', $addArr);
                        }
                    } else {
                        $tempArr = array();
                        $tempArr['activity'] = (string)$activityTag->Status->StatusType->Description;
                        $tempArr['deliverydate'] = implode('-', $dateArr); //YYYY-MM-DD
                        $tempArr['deliverytime'] = implode(':', $timeArr); //HH:MM:SS
                        if ($addArr) {
                            $tempArr['deliverylocation'] = implode(', ', $addArr);
                        }
                        $packageProgress[] = $tempArr;
                    }
                    $i++;
                }
                $resultArr['progressdetail'] = $packageProgress;
            }
        } else {
            $arr = $xml->getXpath("//TrackResponse/Response/Error/ErrorDescription/text()");
            $errorTitle = (string)$arr[0][0];
            return array('error' => $errorTitle);
        }
        return $resultArr;
    }

    function setShipper($type = 'shipment')
    {
        /*if ($type == 'shipment') {*/
        $data = "<Shipper><ShipperNumber>" . $this->shipperNumber . "</ShipperNumber>";
        if ($type !== "ajaxprice_shipment" && $type !== 'ajaxprice_invert' && $type !== 'ajaxprice_refund') {
            $data .= "<Name>" . $this->shipperName . "</Name>
    <AttentionName>" . $this->shipperAttentionName . "</AttentionName>
    <PhoneNumber>" . $this->shipperPhoneNumber . "</PhoneNumber>";
            $data .= "<TaxIdentificationNumber></TaxIdentificationNumber>";
        }
        $data .= "<Address>";
        if ($type !== "ajaxprice_shipment" && $type !== 'ajaxprice_invert' && $type !== 'ajaxprice_refund') {
            $data .= "<AddressLine1>" . $this->shipperAddressLine1 . "</AddressLine1><PostcodeExtendedLow></PostcodeExtendedLow>";
        }
        $data .= "<City>" . $this->shipperCity . "</City>
    	<StateProvinceCode>" . $this->shipperStateProvinceCode . "</StateProvinceCode>
    	<PostalCode>" . $this->shipperPostalCode . "</PostalCode>
    	<CountryCode>" . $this->shipperCountryCode . "</CountryCode>
     </Address>
    </Shipper>";
        return $data;
        /*} else {
            return "<Shipper>
        <Name>" . $this->shipperName . "</Name>
        <AttentionName>" . $this->shipperAttentionName . "</AttentionName>
        <PhoneNumber>" . $this->shipperPhoneNumber . "</PhoneNumber>
              <ShipperNumber>" . $this->shipperNumber . "</ShipperNumber>
        	  <TaxIdentificationNumber></TaxIdentificationNumber>
              <Address>
            	<AddressLine1>" . $this->shipperAddressLine1 . "</AddressLine1>
            	<City>" . $this->shipperCity . "</City>
            	<StateProvinceCode>" . $this->shipperStateProvinceCode . "</StateProvinceCode>
            	<PostalCode>" . $this->shipperPostalCode . "</PostalCode>
            	<PostcodeExtendedLow></PostcodeExtendedLow>
            	<CountryCode>" . $this->shipperCountryCode . "</CountryCode>
             </Address>
            </Shipper>";
        }*/
    }

    function setShipFrom($type = 'shipment')
    {
        $data = '';
        if ($type == 'shipment' || $type == "ajaxprice_shipment") {
            $data .= "<ShipFrom>
      <CompanyName>" . $this->shipfromCompanyName . "</CompanyName>
      <AttentionName>" . $this->shipfromAttentionName . "</AttentionName>
      <PhoneNumber>" . $this->shipfromPhoneNumber . "</PhoneNumber>";
            if ($type !== "ajaxprice_shipment" && $type !== 'ajaxprice_invert' && $type !== 'ajaxprice_refund') {
                $data .= "<TaxIdentificationNumber></TaxIdentificationNumber>";
            }
            $data .= "<Address>";
            if ($type !== "ajaxprice_shipment" && $type !== 'ajaxprice_invert' && $type !== 'ajaxprice_refund') {
                $data .= "<AddressLine1>" . $this->shipfromAddressLine1 . "</AddressLine1>";
            }
            $data .= "<City>" . $this->shipfromCity . "</City>
    	<StateProvinceCode>" . $this->shipfromStateProvinceCode . "</StateProvinceCode>
    	<PostalCode>" . $this->shipfromPostalCode . "</PostalCode>
    	<CountryCode>" . $this->shipfromCountryCode . "</CountryCode>
      </Address>
    </ShipFrom>";
            return $data;
        } else {
            $data = "<ShipFrom>
             <CompanyName>" . $this->shiptoCompanyName . "</CompanyName>
              <AttentionName>" . $this->shiptoAttentionName . "</AttentionName>";
            if (strlen($this->shiptoPhoneNumber) > 0) {
                $data .= "<PhoneNumber>" . $this->shiptoPhoneNumber . "</PhoneNumber>";
            } else if ($this->serviceCode == 14 || $this->shiptoCountryCode != $this->shipfromCountryCode) {
                $data .= "<PhoneNumber>" . $this->shipfromPhoneNumber . "</PhoneNumber>";
            }
            if ($type !== "ajaxprice_shipment" && $type !== 'ajaxprice_invert' && $type !== 'ajaxprice_refund') {
                $data .= "<TaxIdentificationNumber></TaxIdentificationNumber>";
            }
            $data .= "<Address>
                <AddressLine1>" . $this->shiptoAddressLine1 . "</AddressLine1>";
            if (strlen($this->shiptoAddressLine2) > 0) {
                $data .= '<AddressLine2>' . $this->shiptoAddressLine2 . '</AddressLine2>';
            }
            $data .= "<City>" . $this->shiptoCity . "</City>
                <StateProvinceCode>" . $this->shiptoStateProvinceCode . "</StateProvinceCode>
                <PostalCode>" . $this->shiptoPostalCode . "</PostalCode>
                <CountryCode>" . $this->shiptoCountryCode . "</CountryCode>
              </Address>
            </ShipFrom>";
            return $data;
        }
    }

    function setShipTo($type = 'shipment')
    {
        if ($type == 'shipment' || $type == "ajaxprice_shipment") {
            $data = "<ShipTo>
     <CompanyName>" . $this->shiptoCompanyName . "</CompanyName>
      <AttentionName>" . $this->shiptoAttentionName . "</AttentionName>";
            if (strlen($this->shiptoPhoneNumber) > 0) {
                $data .= "<PhoneNumber>" . $this->shiptoPhoneNumber . "</PhoneNumber>";
            } else if ($this->serviceCode == 14 || $this->shiptoCountryCode != $this->shipfromCountryCode) {
                $data .= "<PhoneNumber>" . $this->shipfromPhoneNumber . "</PhoneNumber>";
            }
            $data .= "<Address>";
            $data .= "<City>" . $this->shiptoCity . "</City>";
            if (strlen($this->shiptoStateProvinceCode) > 0) {
                $data .= "<StateProvinceCode>" . $this->shiptoStateProvinceCode . "</StateProvinceCode>";
            } else {
                $data .= "<StateProvinceCode/>";
            }
            $data .= "<PostalCode>" . $this->shiptoPostalCode . "</PostalCode>
        <CountryCode>" . $this->shiptoCountryCode . "</CountryCode>";
            if ($type != "ajaxprice_shipment") {
                /*$data .= "<ResidentialAddress>01</ResidentialAddress><ResidentialAddressIndicator>01</ResidentialAddressIndicator>";*/
                $data .= "<AddressLine1>" . $this->shiptoAddressLine1 . "</AddressLine1>";
                if (strlen($this->shiptoAddressLine2) > 0) {
                    $data .= '<AddressLine2>' . $this->shiptoAddressLine2 . '</AddressLine2>';
                }
            }
            $data .= $this->residentialAddress;
            $data .= "</Address>
    </ShipTo>";
            return $data;
        } else {
            $data = "<ShipTo>
              <CompanyName>" . $this->shipfromCompanyName . "</CompanyName>
              <AttentionName>" . $this->shipfromAttentionName . "</AttentionName>
              <PhoneNumber>" . $this->shipfromPhoneNumber . "</PhoneNumber>
              <Address>
                <AddressLine1>" . $this->shipfromAddressLine1 . "</AddressLine1>";
            if (strlen($this->shipfromAddressLine2) > 0) {
                $data .= '<AddressLine2>' . $this->shipfromAddressLine2 . '</AddressLine2>';
            }
            $data .= "
                <City>" . $this->shipfromCity . "</City>";
            if (strlen($this->shipfromStateProvinceCode) > 0) {
                $data .= "<StateProvinceCode>" . $this->shipfromStateProvinceCode . "</StateProvinceCode>";
            } else {
                $data .= "<StateProvinceCode/>";
            }
            $data .= "<PostalCode>" . $this->shipfromPostalCode . "</PostalCode>
            	<CountryCode>" . $this->shipfromCountryCode . "</CountryCode>
              </Address>
            </ShipTo>";
            return $data;
        }
    }

    public function timeInTransit($weightSum = 0.1)
    {
        /*$cie = 'wwwcie';
        $testing = $this->testing;
        if (0 == $testing) {*/
        $cie = 'onlinetools';
        /*}*/
        $data = "<?xml version=\"1.0\" ?>
<AccessRequest xml:lang='en-US'>
<AccessLicenseNumber>" . $this->AccessLicenseNumber . "</AccessLicenseNumber>
<UserId>" . $this->UserID . "</UserId>
<Password>" . $this->Password . "</Password>
</AccessRequest>
<?xml version=\"1.0\" ?>
<TimeInTransitRequest xml:lang='en-US'>
<Request>
<TransactionReference>
<CustomerContext>Shipper</CustomerContext>
<XpciVersion>1.0002</XpciVersion>
</TransactionReference>
<RequestAction>TimeInTransit</RequestAction>
</Request>
<TransitFrom>
<AddressArtifactFormat>
<CountryCode>" . $this->shipfromCountryCode . "</CountryCode>
<PostcodePrimaryLow>" . $this->shipfromPostalCode . "</PostcodePrimaryLow>
</AddressArtifactFormat>
</TransitFrom>
<TransitTo>
<AddressArtifactFormat>
<PoliticalDivision2>" . $this->shiptoCity . "</PoliticalDivision2>
<PoliticalDivision1>" . $this->shiptoStateProvinceCode . "</PoliticalDivision1>
<CountryCode>" . $this->shiptoCountryCode . "</CountryCode>
<PostcodePrimaryLow>" . $this->shiptoPostalCode . "</PostcodePrimaryLow>
</AddressArtifactFormat>
</TransitTo>
<ShipmentWeight>
<UnitOfMeasurement>
<Code>" . $this->weightUnits . "</Code>
</UnitOfMeasurement>
<Weight>" . $weightSum . "</Weight>
</ShipmentWeight>
<PickupDate>" . date('Ymd') . "</PickupDate>
<DocumentsOnlyIndicator />
</TimeInTransitRequest>";
        $curl = Mage::helper('upslabel/help');
        $curl->testing = !$this->testing;
        $result = $curl->curlSend('https://' . $cie . '.ups.com/ups.app/xml/TimeInTransit', $data);
        /*Mage::log($data);
        Mage::log($result);*/
        if (!$curl->error) {
            $xml = $this->xml2array(simplexml_load_string($result));
            if ($xml['Response']['ResponseStatusCode'] == 0 || $xml['Response']['ResponseStatusDescription'] != 'Success') {
                return array('error' => 1);
            } else {
                $countDay = array();
                if (isset($xml['TransitResponse']['ServiceSummary']) && is_array($xml['TransitResponse']['ServiceSummary']) && count($xml['TransitResponse']['ServiceSummary']) > 0) {
                    foreach ($xml['TransitResponse']['ServiceSummary'] AS $v) {
                        $codes = $curl->getUpsCode($v['Service']['Code']);
                        if (!is_array($codes)) {
                            $codes = array($codes);
                        }
                        if (isset($v['EstimatedArrival']['TotalTransitDays'])) {
                            foreach ($codes AS $v2) {
                                $countDay[$v2]['days'] = $v['EstimatedArrival']['TotalTransitDays'];
                                $countDay[$v2]['datetime']['date'] = $v['EstimatedArrival']['Date'];
                                $countDay[$v2]['datetime']['time'] = $v['EstimatedArrival']['Time'];
                            }

                        } else if (isset($v['EstimatedArrival']['BusinessTransitDays'])) {
                            foreach ($codes AS $v2) {
                                $countDay[$v2]['days'] = $v['EstimatedArrival']['BusinessTransitDays'];
                                $countDay[$v2]['datetime']['date'] = $v['EstimatedArrival']['Date'];
                                $countDay[$v2]['datetime']['time'] = $v['EstimatedArrival']['Time'];
                            }
                        }
                    }
                }
                return array('error' => 0, 'days' => $countDay);
            }
        } else {
            return $result;
        }
    }

    public function xml2array($xmlObject, $out = array())
    {
        foreach ((array)$xmlObject as $index => $node) {
            $out[$index] = (is_object($node)) ? $this->xml2array($node) : (is_array($node)) ? $this->xml2array($node) : $node;
        }
        return $out;
    }

    protected function isAdult($typeService)
    {
        if ($this->adult == 4) {
            if ($typeService === "P") {
                return false;
            } else if ($typeService === "S") {
                return true;
            }
        }
        if ($typeService === "S") {
            $this->adult = $this->adult - 1;
        }
        if ($this->adult == 0) {
            return false;
        }

        $adult = 'DC';
        if ($typeService === 'P') {
            if ($this->adult == 2) {
                $adult = 'DC-SR';
            } else if ($this->adult == 3) {
                $adult = 'DC-ASR';
            }
        } else if ($typeService === 'S') {
            if ($this->adult == 1) {
                $adult = 'DC-SR';
            } else if ($this->adult == 2) {
                $adult = 'DC-ASR';
            }
        }

        switch ($this->shipfromCountryCode) {
            case 'US':
            case 'CA':
            case 'PR':
                switch ($this->shiptoCountryCode) {
                    case 'US':
                    case 'PR':
                        if ($typeService === 'P') {
                            return true;
                        }
                        break;
                    default:
                        if ($typeService === 'S' && ($adult === 'DC-SR' || $adult === 'DC-ASR')) {
                            return true;
                        }
                        break;
                }
                break;
            default:
                if ($typeService === 'S' && ($adult === 'DC-SR' || $adult === 'DC-ASR')) {
                    return true;
                }
                break;
        }

        return false;
    }

    private function largePackageIndicator($pv)
    {
        if (isset($pv['weight']) && $pv['weight'] > 0 && isset($pv['height']) && $pv['height'] > 0 && isset($pv['length']) && $pv['length'] > 0) {
            $maxDimension = 130;
            if ($this->unitOfMeasurement == 'CM') {
                $maxDimension = 330;
            }
            if (($pv['weight'] * 2 + $pv['height'] * 2) >= $maxDimension) {
                return '<LargePackageIndicator />';
            }
        }
        return '';
    }
}