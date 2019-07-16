<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Model_Dhl
{
    public $packages;
    public $weightUnits;
    public $packageWeight;
    public $largePackageIndicator;

    public $packageType;

    public $includeDimensions;
    public $unitOfMeasurement;
    public $depth;
    public $width;
    public $height;

    public $customerContext;
    public $shippernumber;
    public $shipperId;
    public $shipperName;
    public $shipperPhoneNumber;
    public $shipperAddressLine1;
    public $shipperAddressLine2;
    public $shipperAddressLine3;
    public $shipperCity;
    public $shipperStateProvinceName;
    public $shipperStateProvinceCode;
    public $shipperPostalCode;
    public $shipperCountryCode;
    public $shipperCountryName;
    public $shipmentDescription;
    public $shipperAttentionName;

    public $shiptoCompanyName;
    public $shiptoAttentionName;
    public $shiptoPhoneNumber;
    public $shiptoAddressLine1;
    public $shiptoAddressLine2;
    public $shiptoAddressLine3;
    public $shiptoCity;
    public $shiptoStateProvinceName;
    public $shiptoStateProvinceCode;
    public $shiptoPostalCode;
    public $shiptoCountryCode;
    public $shiptoCountryName;
    public $residentialAddress;

    public $shipfromCompanyName;
    public $shipfromAttentionName;
    public $shipfromPhoneNumber;
    public $shipfromAddressLine1;
    public $shipfromCity;
    public $shipfromStateProvinceCode;
    public $shipfromPostalCode;
    public $shipfromCountryCode;

    public $serviceCode;
    public $serviceGlobalCode;
    public $ReferenceId;
    public $doorto;
    public $declaredValue;
    public $print_type;
    public $print_type_format;

    public $codYesNo;
    public $currencyCode;
    public $codMonetaryValue;
    public $codOrderId;
    public $testing;
    public $qvn = 0;
    public $qvn_email_message = '';
    public $qvn_email_shipto = '';
    public $qvn_email_shipper = '';

    public $upsAccount = 0;
    public $upsAccountDuty = 0;
    public $accountData;
    public $accountDataDuty;

    public $dutyPaymentType = null;
    public $dutyAccountNumber = null;

    public $rateErrors;
    public $requestArchiveDoc;

    public $invoicePdf = null;
    public $invoiceProducts = array();
    public $totalWeight = null;
    public $totalQty = null;
    public $totalLength = null;
    public $totalWidth = null;
    public $totalHeight = null;
    public $totalCommodityCode = null;
    public $totalCommodityType = null;
    public $totalCommodityTypes = null;
    public $totalDescription = null;
    public $totalPrice = null;
    public $orderIncrementId = null;
    public $shipmentIncrementId = null;
    public $invoiceIncrementId = null;

    function getShip( /*multistore*/
        $storeId = null /*multistore*/)
    {
        Mage::helper('dhllabel/help')->createMediaFolders();
        $pathXml = Mage::getBaseDir('media') . '/dhllabel/test_xml/';
        if ($this->shipperCountryCode !== "IN" || $this->shiptoCountryCode == "IN") {
            $request = new Infomodus_Dhllabel_Model_Src_Request_ShipmentRequest(
                Mage::getStoreConfig('dhllabel/credentials/userid', $storeId),
                Mage::getStoreConfig('dhllabel/credentials/password', $storeId)
            );
            $request->setLabelImageFormat($this->print_type_format);
            $request->setRequestArchiveDoc($this->requestArchiveDoc);

            $europeCountries = explode(",", Mage::getStoreConfig('general/country/eu_countries', $storeId));

            $destWeightUnit = Mage::helper('dhllabel/help')->getWeightUnitByCountry($this->shiptoCountryCode);
            $weingUnitKoef = 1;
            switch ($destWeightUnit) {
                case 'KG':
                    $destWeightUnit = 'K';
                    break;
                case 'LB':
                    $destWeightUnit = 'L';
                    break;
            }

            if ($destWeightUnit != $this->weightUnits) {
                if ($destWeightUnit == 'L') {
                    $weingUnitKoef = 2.2046;
                } else {
                    $weingUnitKoef = 1 / 2.2046;
                }
            }

            $destDimentionUnit = Mage::helper('dhllabel/help')->getDimensionUnitByCountry($this->shiptoCountryCode);
            switch ($destDimentionUnit) {
                case 'CM':
                    $destDimentionUnit = 'C';
                    break;
                case 'IN':
                    $destDimentionUnit = 'I';
                    break;
            }

            $dimentionUnitKoef = 1;
            if ($destWeightUnit != $this->weightUnits) {
                if ($destDimentionUnit == 'C') {
                    $dimentionUnitKoef = 2.54;
                } else {
                    $dimentionUnitKoef = 1 / 2.54;
                }
            }

            /* Multipackages */
            $pieces = array();
            foreach ($this->packages AS $pv) {
                $piecesTemp = array();
                if (isset($pv['height']) || isset($pv['width']) || isset($pv['depth'])) {
                    $piecesTemp['height'] = round($pv['height'] * $dimentionUnitKoef, 0);
                    $piecesTemp['width'] = round($pv['width'] * $dimentionUnitKoef, 0);
                    $piecesTemp['depth'] = round($pv['depth'] * $dimentionUnitKoef, 0);
                }

                $packweight = array_key_exists('packweight', $pv) ?
                    (float)str_replace(',', '.', $pv['packweight']) * $weingUnitKoef : 0;
                $weight = array_key_exists('weight', $pv) ?
                    (float)str_replace(',', '.', $pv['weight']) * $weingUnitKoef : 0;
                $piecesTemp['weight'] = round(($weight + (is_numeric($packweight) ? $packweight : 0)), 3);
                $pieces[] = $piecesTemp;
            }

            /* END Multipackages */
            $account = $this->accountData;
            $accountNumber = $this->shippernumber;
            if ($this->accountData != "S" && $this->accountData != "R") {
                $account = "T";
                $accountNumber = $this->accountData;
            }
            if($this->codYesNo){
                $this->shippernumber = Mage::getStoreConfig('dhllabel/credentials/shippernumbercod', $storeId);
            }
            else{
                $this->shippernumber = Mage::getStoreConfig('dhllabel/credentials/shippernumber', $storeId);
            }

            $ndc = Mage::getModel('dhllabel/config_dhlmethod')->getContentTypeByMetod($this->serviceGlobalCode);
            $isDutiable = null;
            if ($ndc == 'NONDOC' && $this->shiptoCountryCode != $this->shipperCountryCode) {
                $request->buildDutiable(
                    $this->declaredValue,
                    $this->currencyCode,
                    Mage::getStoreConfig('dhllabel/paperless/terms_of_trade', $storeId)
                );
                $isDutiable = true;
            }

            if ($isDutiable === true) {
                if ($this->dutyPaymentType != "S" && $this->dutyPaymentType != "R") {
                    $this->dutyPaymentType = "T";
                    $this->dutyAccountNumber = $this->accountDataDuty;
                }
                else {
                    if($this->codYesNo){
                        $this->dutyAccountNumber = Mage::getStoreConfig('dhllabel/credentials/shippernumbercod', $storeId);
                    }
                    else{
                        $this->dutyAccountNumber = Mage::getStoreConfig('dhllabel/credentials/shippernumber', $storeId);
                    }
                }

                if ($this->dutyPaymentType != "R" && (!in_array($this->shiptoCountryCode, $europeCountries) || !in_array($this->shipperCountryCode, $europeCountries))) {
                    $request->buildSpecialService('DD');
                }
            } else {
                $this->dutyPaymentType = null;
                $this->dutyAccountNumber = null;
            }

            if (strlen($this->shiptoPhoneNumber) == 0) {
                $this->shiptoPhoneNumber = $this->shipperPhoneNumber;
            }

            $request->buildBilling(
                $this->shippernumber,
                $account,
                $accountNumber,
                $this->dutyPaymentType,
                $this->dutyAccountNumber
            )
                ->setRegionCode(Mage::getStoreConfig('dhllabel/shipping/regioncode', $storeId))
                /* Ship to */
                ->buildConsignee(
                    $this->shiptoCompanyName,
                    $this->shiptoAddressLine1,
                    $this->shiptoAddressLine2,
                    $this->shiptoAddressLine3,
                    $this->shiptoCity,
                    $this->shiptoPostalCode,
                    $this->shiptoCountryCode,
                    $this->shiptoCountryName,
                    $this->shiptoAttentionName,
                    $this->shiptoPhoneNumber,
                    $this->shiptoStateProvinceName,
                    $this->shiptoStateProvinceCode,
                    $this->qvn_email_shipto
                );
            /* END Ship to */
            /*$isCODItaly = null;*/
            if ($this->codYesNo == 1) {
                if ($this->shipperCountryCode == 'IT' || $this->shipperCountryCode == 'AE') {
                    $request->buildSpecialService('KB', $this->codMonetaryValue, $this->currencyCode);
                } else {
                    $order_id = Mage::getModel('sales/order')->load($this->codOrderId)->getIncrementId();
                    $request->buildReference($order_id . '#' . round($this->codMonetaryValue, 2) . '#' . round($this->codMonetaryValue, 2));
                    $this->shipmentDescription = "COD_" . round($this->codMonetaryValue, 2);
                }
            } else if ($this->ReferenceId != '') {
                $request->buildReference($this->ReferenceId);
            }

            $localProductCode = ($this->shiptoCountryCode != $this->shipperCountryCode &&
                Mage::getStoreConfig('dhllabel/paperless/type', $storeId) == 1) ? null : $this->serviceCode;
            $request->buildShipmentDetails(
                $pieces,
                $this->serviceGlobalCode,
                $localProductCode,
                new DateTime(
                    date("Y-m-d", Mage::getModel('core/date')->timestamp(time() + 5 * 60 * 60))
                ),
                $this->shipmentDescription,
                $this->currencyCode,
                $destWeightUnit,
                $destDimentionUnit,
                $this->packageType,
                $this->doorto,
                $isDutiable/*,
                $isCODItaly*/
            )
                /* Shipper */
                ->buildShipper(
                    Mage::getStoreConfig('dhllabel/credentials/shippernumber', $storeId),
                    $this->shipperName,
                    $this->shipperAddressLine1,
                    $this->shipperAddressLine2,
                    $this->shipperAddressLine3,
                    $this->shipperCity,
                    $this->shipperPostalCode,
                    $this->shipperCountryCode,
                    $this->shipperCountryName,
                    $this->shipperAttentionName,
                    $this->shipperPhoneNumber
                );
            /* END Shipper */

            if ($this->qvn == 1) {
                $request->buildNotification(
                    $this->qvn_email_shipto .
                    ($this->qvn_email_shipper !== "" ? ";" . $this->qvn_email_shipper : ""),
                    $this->qvn_email_message
                );
            }

            $request->buildLabelFormat($this->print_type);
            /*if ($this->shiptoCountryCode == 'AU' || $this->shiptoCountryCode == 'NZ') {
                $request->buildSpecialService('SX');
            }*/

            if ($ndc == 'NONDOC'
                && $this->shipperCountryCode != $this->shiptoCountryCode
                && Mage::getStoreConfig('dhllabel/paperless/type', $storeId) == 1) {
                $request->buildSpecialService('WY');
                $request->setEProcShip('N');
                $request->buildDocImages('CIN', base64_encode($this->invoicePdf), 'PDF');
            }

            $request->buildDangerousGoods($this->invoiceProducts, Mage::helper('dhllabel/help')->getWeightUnitByCountry($this->shipperCountryCode), $weingUnitKoef);

            $response = $request->send($this->testing);
            file_put_contents($pathXml . "ShipDirectRequest.xml", $request->responce);

            file_put_contents($pathXml . "ShipDirectResponse.xml", $response);
            $response = (array)simplexml_load_string(utf8_encode($response));
        } elseif ($this->shiptoCountryCode !== "IN" && $this->shipperCountryCode === "IN") {
            /*$localProductCode = ($this->shiptoCountryCode != $this->shipperCountryCode && Mage::getStoreConfig('dhllabel/paperless/type', $storeId) == 1) ? null : $this->serviceCode;*/
            $this->shippernumber = Mage::getStoreConfig('dhllabel/credentials/shippernumber', $storeId);
            $request = array(
                'ShippingPaymentType' => 'S',
                'ShipperAccNumber' => $this->shippernumber,
                'BillingAccNumber' => $this->shippernumber,
                'DutyPaymentType' => 'R',
                'DutyAccNumber' => $this->dutyAccountNumber,
                'ConsigneeCompName' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shiptoCompanyName, 1),
                'ConsigneeAddLine1' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shiptoAddressLine1, 1),
                'ConsigneeAddLine2' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shiptoAddressLine2, 1) == '' ? Infomodus_Dhllabel_Helper_Help::escapeXML($this->shiptoAddressLine1, 1) : Infomodus_Dhllabel_Helper_Help::escapeXML($this->shiptoAddressLine2, 1),
                'ConsigneeAddLine3' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shiptoAddressLine3, 1),
                'ConsigneeCity' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shiptoCity, 1),
                'ConsigneeDivCode' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shiptoStateProvinceCode, 1),
                'PostalCode' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shiptoPostalCode, 1),
                'ConsigneeCountryCode' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shiptoCountryCode, 1),
                'ConsigneeCountryName' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shiptoCountryName, 1),
                'ConsigneeName' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shiptoAttentionName, 1),
                'ConsigneePh' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shiptoPhoneNumber, 1),
                'DutiableDeclaredvalue' => number_format(round(Infomodus_Dhllabel_Helper_Help::escapeXML($this->declaredValue, 1), 2), 2, '.', ''),
                'DutiableDeclaredCurrency' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->currencyCode, 1),
                'ShipNumberOfPieces' => count($this->packages),
                'ShipCurrencyCode' => $this->currencyCode,
                'ShipPieceWt' => $this->getTotalWeight($this->packages),
                'ShipPieceDepth' => $this->getTotalDepth($this->packages),
                'ShipPieceWidth' => $this->getTotalWidth($this->packages),
                'ShipPieceHeight' => $this->getTotalHeight($this->packages),
                'ShipGlobalProductCode' => 'P',
                'ShipLocalProductCode' => 'P',
                'ShipContents' => substr(Infomodus_Dhllabel_Helper_Help::escapeXML($this->getTotalCommodityType($this->invoiceProducts) . '; ' . $this->shipmentDescription, 1), 0, 90),
                'ShipperId' => $this->shippernumber,
                'ShipperCompName' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shipperName, 1),
                'ShipperAddress1' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shipperAddressLine1, 1),
                'ShipperAddress2' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shipperAddressLine2, 1),
                'ShipperAddress3' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shipperAddressLine3, 1),
                'ShipperCountryCode' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shipperCountryCode, 1),
                'ShipperCountryName' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shipperCountryName, 1),
                'ShipperCity' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shipperCity, 1),
                'ShipperPostalCode' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shipperPostalCode, 1),
                'ShipperPhoneNumber' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shipperPhoneNumber, 1),
                'SiteId' => Mage::getStoreConfig('dhllabel/credentials/userid', $storeId),
                'Password' => Mage::getStoreConfig('dhllabel/credentials/password', $storeId),
                'ShipperName' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->shipperAttentionName, 1),
                'ShipperRef' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->ReferenceId, 1),
                'IsResponseRequired' => 'N',
                'LabelReq' => 'Y',
                'SpecialService' => (in_array($this->shiptoCountryCode, array('US', 'CA', 'NZ', 'AU')) ? "SX" : "DS"),
                'IECNo' => Infomodus_Dhllabel_Helper_Help::escapeXML(Mage::getStoreConfig('dhllabel/paperless/IECNo', $storeId), 1),
                'TermsOfInvoice' => Mage::getStoreConfig('dhllabel/paperless/terms_of_trade', $storeId),
                'Usingecommerce' => Mage::getStoreConfig('dhllabel/paperless/usingecommerce', $storeId),
                'IsUnderMEISScheme' => trim(str_repeat('1,', count($this->invoiceProducts)), ','),
                'SerialNumber' => implode(",", range(1, $this->getCountProducts($this->invoiceProducts))),
                'FOBValue' => $this->getTotalPrice($this->invoiceProducts),
                'Description' => Infomodus_Dhllabel_Helper_Help::escapeXML($this->getTotalDescription($this->invoiceProducts), 1),
                'Qty' => $this->getTotalQty($this->invoiceProducts),
                'HSCode' => $this->getTotalCommodityCode($this->invoiceProducts),
                'InsuredAmount' => number_format(round($this->codMonetaryValue, 2), 2, '.', ''),
                'CommodityType' => $this->getTotalCommodityTypes($this->invoiceProducts),
                'GSTIN' => Mage::getStoreConfig('dhllabel/paperless/GSTIN', $storeId),
                'GSTInvNo' => $this->orderIncrementId,
                'GSTInvNoDate' => date("Y-m-d"),
                'NonGSTInvNo' => 'NA',
                'NonGSTInvDate' => date("Y-m-d"),
                'TotalIGST' => number_format(round($this->codMonetaryValue, 2), 2, '.', ''),
                'IsUsingIGST' => Mage::getStoreConfig('dhllabel/paperless/isUsingIGST', $storeId) == 1 ? "YES" : "NO",
                'UsingBondorUT' => Mage::getStoreConfig('dhllabel/paperless/isUsingIGST', $storeId) == 1 ? "NO" : "YES",
                'isIndemnityClauseRead' => 'YES',
            );
            $wsdlBasePath = Mage::getModuleDir('etc', 'Infomodus_Dhllabel') . '/wsdl/';
            $client = new SoapClient($wsdlBasePath . 'Dhlindiaplugin.wsdl', array('trace' => true, 'exceptions' => 0, 'cache_wsdl' => WSDL_CACHE_NONE));
            $client->__setLocation('http://www.dhlindiaplugin.com/DHLService.svc');
            if ($this->getSumWeight($this->packages) < 70
                && Mage::helper('directory')->currencyConvert(number_format(round($this->codMonetaryValue, 2), 2, '.', ''), Mage::getStoreConfig('currency/options/base', $storeId), 'INR') < 25000
            ) {
                $response = $client->PostShipmentWithSpecialService3C($request);
                $response = $response->PostShipmentWithSpecialService3CResult;
            } else {
                $response = $client->PostShipmentWithSpecialService($request);
                $response = $response->PostShipmentWithSpecialServiceResult;
            }
            Mage::log($client->__getLastRequest());
            Mage::log($client->__getLastResponse());
            Mage::log($response);
            if ($response && strpos($response, '<') === false) {
                $pdfData = file_get_contents($response);
                $response = array(
                    'Note' => array(
                        'ActionNote' => 'Success'
                    ),
                    'AirwayBillNumber' => basename($response, '.pdf'),
                    'LabelImage' => array(
                        'OutputFormat' => 'pdf',
                        'OutputImage' => base64_encode($pdfData)
                    )
                );
            } else {
                $responseErrorXml = (array)simplexml_load_string(utf8_encode($response));
                $response = array(
                    'Response' => array(
                        'Status' => array(
                            'Condition' => array(
                                'ConditionData' => implode('; ', $responseErrorXml)
                            )
                        )
                    )
                );
            }
        }

        return $response;
    }

    function getShipFrom( /*multistore*/
        $storeId = null /*multistore*/)
    {
        Mage::helper('dhllabel/help')->createMediaFolders();
        $path_xml = Mage::getBaseDir('media') . '/dhllabel/test_xml/';


        $request = new Infomodus_Dhllabel_Model_Src_Request_ShipmentRequest(
            Mage::getStoreConfig('dhllabel/credentials/userid', $storeId),
            Mage::getStoreConfig('dhllabel/credentials/password', $storeId)
        );
        $request->setRequestArchiveDoc($this->requestArchiveDoc);

        $destWeightUnit = Mage::helper('dhllabel/help')->getWeightUnitByCountry($this->shiptoCountryCode);
        $weingUnitKoef = 1;
        switch ($destWeightUnit) {
            case 'KG':
                $destWeightUnit = 'K';
                break;
            case 'LB':
                $destWeightUnit = 'L';
                break;
        }

        if ($destWeightUnit != $this->weightUnits) {
            if ($destWeightUnit == 'L') {
                $weingUnitKoef = 2.2046;
            } else {
                $weingUnitKoef = 1 / 2.2046;
            }
        }

        $destDimentionUnit = Mage::helper('dhllabel/help')->getDimensionUnitByCountry($this->shiptoCountryCode);
        switch ($destDimentionUnit) {
            case 'CM':
                $destDimentionUnit = 'C';
                break;
            case 'IN':
                $destDimentionUnit = 'I';
                break;
        }

        $dimentionUnitKoef = 1;
        if ($destWeightUnit != $this->weightUnits) {
            if ($destWeightUnit == 'C') {
                $dimentionUnitKoef = 2.54;
            } else {
                $dimentionUnitKoef = 1 / 2.54;
            }
        }

        /* Multipackages */
        $pieces = array();
        foreach ($this->packages AS $pv) {
            $pieces1 = array();
            if (isset($pv['height']) || isset($pv['width']) || isset($pv['depth'])) {
                $pieces1['height'] = round($pv['height'] * $dimentionUnitKoef, 0);
                $pieces1['width'] = round($pv['width'] * $dimentionUnitKoef, 0);
                $pieces1['depth'] = round($pv['depth'] * $dimentionUnitKoef, 0);
            }
            $packweight = array_key_exists('packweight', $pv) ? (float)str_replace(',', '.', $pv['packweight']) * $weingUnitKoef : 0;
            $weight = array_key_exists('weight', $pv) ? (float)str_replace(',', '.', $pv['weight']) * $weingUnitKoef : 0;
            $pieces1['weight'] = round(($weight + (is_numeric($packweight) ? $packweight : 0)), 3);
            $pieces[] = $pieces1;
        }
        /* END Multipackages */

        $shipperNumber = ($this->shipperId == "" ? Mage::getStoreConfig('dhllabel/credentials/shippernumber', $storeId) : $this->shipperId);
        $account = $this->accountData;
        $accountNumber = $shipperNumber;
        if ($this->accountData != "S" && $this->accountData != "R") {
            $account = "T";
            $accountNumber = $this->accountData;
        }

        if (strlen($this->shiptoPhoneNumber) == 0) {
            $this->shiptoPhoneNumber = $this->shipperPhoneNumber;
        }

        $request->buildBilling($shipperNumber, $account, $accountNumber)
            ->setRegionCode(Mage::getStoreConfig('dhllabel/shipping/regioncode', $storeId))
            /* Ship to */
            ->buildConsignee($this->shipperName,
                $this->shipperAddressLine1,
                $this->shipperAddressLine2,
                $this->shipperAddressLine3,
                $this->shipperCity,
                $this->shipperPostalCode,
                $this->shipperCountryCode,
                $this->shipperCountryName,
                $this->shipperAttentionName,
                $this->shipperPhoneNumber,
                $this->shipperStateProvinceName,
                $this->shipperStateProvinceCode,
                $this->qvn_email_shipper
            );

        /* END Ship to */
        if ($this->codYesNo == 1) {
            if ($this->shipperCountryCode == 'IT' || $this->shipperCountryCode == 'AE') {
                /*$isCODItaly = '<' . round($this->codMonetaryValue, 2) . '><' . $this->currencyCode . '><Y>';*/
                $request->buildSpecialService('KB', $this->codMonetaryValue, $this->currencyCode);
            } else {
                $order_id = Mage::getModel('sales/order')->load($this->codOrderId)->getIncrementId();
                $request->buildReference($order_id . '#' . round($this->codMonetaryValue, 2) . '#' . round($this->codMonetaryValue, 2));
                $this->shipmentDescription = "COD_" . round($this->codMonetaryValue, 2);
            }
        }

        if ($this->ReferenceId != '') {
            $request->buildReference($this->ReferenceId);
        }

        $ndc = Mage::getModel('dhllabel/config_dhlmethod')->getContentTypeByMetod($this->serviceGlobalCodeReturn);
        /*$isDutiable = $this->shiptoCountryCode != $this->shipperCountryCode ? true : false;*/
        $isDutiable = null;
        if ($ndc == 'NONDOC' && $this->shiptoCountryCode != $this->shipperCountryCode) {
            $request->buildDutiable($this->declaredValue, $this->currencyCode, null);
            $isDutiable = true;
        }

        $request->buildShipmentDetails(
            $pieces,
            $this->serviceGlobalCodeReturn,
            $this->serviceCodeReturn,
            new DateTime(date("Y-m-d", Mage::getModel('core/date')->timestamp(time() + 5 * 60 * 60))),
            $this->shipmentDescription,
            $this->currencyCode,
            $destWeightUnit,
            $destDimentionUnit,
            $this->packageType,
            $this->doorto,
            $isDutiable)
            /* Shipper */
            ->buildShipper(
                ($this->shipperId == "" ? Mage::getStoreConfig('dhllabel/credentials/shippernumber', $storeId) : $this->shipperId),
                $this->shiptoCompanyName,
                $this->shiptoAddressLine1,
                $this->shiptoAddressLine2,
                $this->shiptoAddressLine3,
                $this->shiptoCity,
                $this->shiptoPostalCode,
                $this->shiptoCountryCode,
                $this->shiptoCountryName,
                $this->shiptoAttentionName,
                $this->shiptoPhoneNumber,
                $this->shiptoStateProvinceName,
                $this->shiptoStateProvinceCode
            );
        /* END Shipper */

        if ($this->qvn == 1) {
            $request->buildNotification($this->qvn_email_shipto . ($this->qvn_email_shipper !== "" ? ";" . $this->qvn_email_shipper : ""), $this->qvn_email_message);
        }

        $request->buildLabelFormat('8X4_A4_PDF');

        if (Mage::getStoreConfig('dhllabel/return/return_time_old') != '') {
            $request->buildSpecialService(Mage::getStoreConfig('dhllabel/return/return_time_old'));
        }

        $request->buildDangerousGoods($this->invoiceProducts, Mage::helper('dhllabel/help')->getWeightUnitByCountry($this->shipperCountryCode), $weingUnitKoef);

        $response = $request->send($this->testing);

        file_put_contents($path_xml . "ShipReturnRequest.xml", $request->responce);

        file_put_contents($path_xml . "ShipReturnResponse.xml", $response);
        $response = (array)simplexml_load_string(utf8_encode($response));
        return $response;
    }

    function getShipPrice($isDutiable = false/*multistore*/, $storeId = null /*multistore*/)
    {
        /*if (($isDutiable === true && $this->shiptoCountryCode == $this->shipperCountryCode)
            || ($isDutiable === false && $this->shiptoCountryCode != $this->shipperCountryCode)
        ) {
            return false;
        }*/

        $request = new Infomodus_Dhllabel_Model_Src_Request_GetQuoteRequest(
            Mage::getStoreConfig('dhllabel/credentials/userid', $storeId),
            Mage::getStoreConfig('dhllabel/credentials/password', $storeId)
        );

        $destWeightUnit = Mage::helper('dhllabel/help')->getWeightUnitByCountry($this->shiptoCountryCode);
        $weingUnitKoef = 1;
        switch ($destWeightUnit) {
            case 'KG':
                $destWeightUnit = 'K';
                break;
            case 'LB':
                $destWeightUnit = 'L';
                break;
        }

        if ($destWeightUnit != $this->weightUnits) {
            if ($destWeightUnit == 'L') {
                $weingUnitKoef = 2.2046;
            } else {
                $weingUnitKoef = 1 / 2.2046;
            }
        }

        $destDimentionUnit = Mage::helper('dhllabel/help')->getDimensionUnitByCountry($this->shiptoCountryCode);
        switch ($destDimentionUnit) {
            case 'CM':
                $destDimentionUnit = 'C';
                break;
            case 'IN':
                $destDimentionUnit = 'I';
                break;
        }

        $dimentionUnitKoef = 1;
        if ($destWeightUnit != $this->weightUnits) {
            if ($destWeightUnit == 'C') {
                $dimentionUnitKoef = 2.54;
            } else {
                $dimentionUnitKoef = 1 / 2.54;
            }
        }

        /* Multipackages */
        $pieces = array();
        foreach ($this->packages AS $pv) {
            $pieces1 = array();
            if (isset($pv['height']) || isset($pv['width']) || isset($pv['depth'])) {
                $pieces1['height'] = round($pv['height'] * $dimentionUnitKoef, 0);
                $pieces1['width'] = round($pv['width'] * $dimentionUnitKoef, 0);
                $pieces1['depth'] = round($pv['depth'] * $dimentionUnitKoef, 0);
            }

            $packweight = array_key_exists('packweight', $pv) ? (float)str_replace(',', '.', $pv['packweight']) * $weingUnitKoef : 0;
            $weight = array_key_exists('weight', $pv) ? (float)str_replace(',', '.', $pv['weight']) * $weingUnitKoef : 0;
            $pieces1['weight'] = round(($weight + (is_numeric($packweight) ? $packweight : 0)), 3);
            $pieces[] = $pieces1;
        }

        /* END Multipackages */

        /*$eu_countries = Mage::getStoreConfig('general/country/eu_countries');
        $eu_countries_array = explode(',', $eu_countries);
        $isDutiable = ($this->shiptoCountryCode != $this->shipperCountryCode && (!in_array($this->shiptoCountryCode, $eu_countries_array) || in_array($this->shipperCountryCodee, $eu_countries_array))) ? true : false;*/
        $request->buildFrom($this->shipperCountryCode, $this->shipperPostalCode, $this->shipperCity)
            ->buildBkgDetails($this->shipperCountryCode, new DateTime(date("Y-m-d", Mage::getModel('core/date')->timestamp(time() + 5 * 60 * 60))),
                $pieces, 'PT10H21M', ($destDimentionUnit == 'C' ? 'CM' : 'IN'), ($destWeightUnit == 'K' ? 'KG' : 'LB'), $isDutiable, Mage::getStoreConfig('dhllabel/credentials/shippernumber', $storeId))
            ->buildTo($this->shiptoCountryCode, $this->shiptoPostalCode, $this->shiptoCity);
        if ($isDutiable) {
            $request->buildDutiable($this->declaredValue, $this->currencyCode);
        }

        /*if($this->shipperCountryCode != $this->shiptoCountryCode && Mage::getStoreConfig('dhllabel/paperless/type', $storeId) == 1){
            $request->buildSpecialService('WY');
            $request->setEProcShip('N');
            $request->buildDocImages('CIN', base64_encode($this->invoicePdf), 'PDF');
        }*/

        $requestData = $request->send($this->testing);
        Mage::helper('dhllabel/help')->createMediaFolders();
        $path_xml = Mage::getBaseDir('media') . '/dhllabel/test_xml/';
        $dopName = '';
        if ($isDutiable == true) {
            $dopName = 'withDutiable';
        }

        file_put_contents($path_xml . "CapabilityRequest" . $dopName . ".xml", $request->responce);

        file_put_contents($path_xml . "CapabilityResponse" . $dopName . ".xml", $requestData);

        $response = new Infomodus_Dhllabel_Model_Src_Response_GetQuoteResponse($requestData);
        $this->rateErrors = $response->getErrors();
        return $response->getPrices();
    }

    function getReturnPrice($isDutiable = false/*multistore*/,
                            $storeId = null /*multistore*/)
    {
        /*if (($isDutiable === true && $this->shiptoCountryCode == $this->shipperCountryCode)
            || ($isDutiable === false && $this->shiptoCountryCode != $this->shipperCountryCode)
        ) {
            return false;
        }*/

        $request = new Infomodus_Dhllabel_Model_Src_Request_GetQuoteRequest(
            Mage::getStoreConfig('dhllabel/credentials/userid', $storeId),
            Mage::getStoreConfig('dhllabel/credentials/password', $storeId)
        );

        $destWeightUnit = Mage::helper('dhllabel/help')->getWeightUnitByCountry($this->shipperCountryCode);
        $weingUnitKoef = 1;
        switch ($destWeightUnit) {
            case 'KG':
                $destWeightUnit = 'K';
                break;
            case 'LB':
                $destWeightUnit = 'L';
                break;
        }

        if ($destWeightUnit != $this->weightUnits) {
            if ($destWeightUnit == 'L') {
                $weingUnitKoef = 2.2046;
            } else {
                $weingUnitKoef = 1 / 2.2046;
            }
        }

        $destDimentionUnit = Mage::helper('dhllabel/help')->getDimensionUnitByCountry($this->shipperCountryCode);
        switch ($destDimentionUnit) {
            case 'CM':
                $destDimentionUnit = 'C';
                break;
            case 'IN':
                $destDimentionUnit = 'I';
                break;
        }

        $dimentionUnitKoef = 1;
        if ($destWeightUnit != $this->weightUnits) {
            if ($destWeightUnit == 'C') {
                $dimentionUnitKoef = 2.54;
            } else {
                $dimentionUnitKoef = 1 / 2.54;
            }
        }

        /* Multipackages */
        $pieces = array();
        foreach ($this->packages AS $pv) {
            $pieces1 = array();
            if (isset($pv['height']) || isset($pv['width']) || isset($pv['depth'])) {
                $pieces1['height'] = round($pv['height'] * $dimentionUnitKoef, 0);
                $pieces1['width'] = round($pv['width'] * $dimentionUnitKoef, 0);
                $pieces1['depth'] = round($pv['depth'] * $dimentionUnitKoef, 0);
            }
            $packweight = array_key_exists('packweight', $pv) ? (float)str_replace(',', '.', $pv['packweight']) * $weingUnitKoef : 0;
            $weight = array_key_exists('weight', $pv) ? (float)str_replace(',', '.', $pv['weight']) * $weingUnitKoef : 0;
            $pieces1['weight'] = round(($weight + (is_numeric($packweight) ? $packweight : 0)), 3);
            $pieces[] = $pieces1;
        }

        /* END Multipackages */
        /*$eu_countries = Mage::getStoreConfig('general/country/eu_countries');
        $eu_countries_array = explode(',', $eu_countries);
        $isDutiable = ($this->shiptoCountryCode != $this->shipperCountryCode && (!in_array($this->shiptoCountryCode, $eu_countries_array) || in_array($this->shipperCountryCodee, $eu_countries_array))) ? true : false;*/
        $request->buildFrom($this->shiptoCountryCode, $this->shiptoPostalCode, $this->shiptoCity)
            ->buildBkgDetails($this->shiptoCountryCode, new DateTime(date("Y-m-d", Mage::getModel('core/date')->timestamp(time() + 5 * 60 * 60))),
                $pieces, 'PT10H21M', ($destDimentionUnit == 'C' ? 'CM' : 'IN'), ($destWeightUnit == 'K' ? 'KG' : 'LB'), $isDutiable, Mage::getStoreConfig('dhllabel/credentials/shippernumber', $storeId))
            ->buildTo($this->shipperCountryCode, $this->shipperPostalCode, $this->shipperCity);
        if ($isDutiable) {
            $request->buildDutiable($this->declaredValue, $this->currencyCode);
        }

        $requestData = $request->send($this->testing);
        Mage::helper('dhllabel/help')->createMediaFolders();
        $path_xml = Mage::getBaseDir('media') . '/dhllabel/test_xml/';
        $dopName = '';
        if ($isDutiable == true) {
            $dopName = 'withDutiable';
        }

        file_put_contents($path_xml . "CapabilityReturnRequest" . $dopName . ".xml", $request->responce);

        file_put_contents($path_xml . "CapabilityReturnResponse" . $dopName . ".xml", $requestData);

        $response = new Infomodus_Dhllabel_Model_Src_Response_GetQuoteResponse($requestData);
        $this->rateErrors = $response->getErrors();
        return $response->getPrices();
    }

    public function deleteLabel()
    {
        return true;
    }

    protected function getSumWeight($packages)
    {
        if ($this->totalWeight === null) {
            $weight = array();
            foreach ($packages AS $item) {
                if (isset($item['weight'])) {
                    $weight[] = $item['weight'];
                }
            }

            if (!empty($weight)) {
                $this->totalWeight = implode(',', $weight);
            }
        }

        return $this->totalWeight;
    }

    protected function getTotalWeight($packages)
    {
        if ($this->totalWeight === null) {
            $weight = array();
            foreach ($packages AS $item) {
                if (isset($item['weight'])) {
                    $weight[] = $item['weight'];
                }
            }

            if (!empty($weight)) {
                $this->totalWeight = implode(',', $weight);
            }
        }

        return $this->totalWeight;
    }

    protected function getTotalDepth($packages)
    {
        if ($this->totalLength === null) {
            $weight = array();
            foreach ($packages AS $item) {
                if (isset($item['depth'])) {
                    $weight[] = $item['depth'];
                }
            }

            if (!empty($weight)) {
                $this->totalLength = implode(',', $weight);
            }
        }

        return $this->totalLength;
    }

    protected function getTotalWidth($packages)
    {
        if ($this->totalWidth === null) {
            $weight = array();
            foreach ($packages AS $item) {
                if (isset($item['width'])) {
                    $weight[] = $item['width'];
                }
            }

            if (!empty($weight)) {
                $this->totalWidth = implode(',', $weight);
            }
        }

        return $this->totalLength;
    }

    protected function getTotalHeight($packages)
    {
        if ($this->totalHeight === null) {
            $weight = array();
            foreach ($packages AS $item) {
                if (isset($item['height'])) {
                    $weight[] = $item['height'];
                }
            }

            if (!empty($weight)) {
                $this->totalHeight = implode(',', $weight);
            }
        }

        return $this->totalHeight;
    }

    protected function getTotalCommodityCode($packages)
    {
        if ($this->totalCommodityCode === null) {
            $weight = array();
            foreach ($packages AS $item) {
                if (!empty($item['enable']) && isset($item['commodity_code'])) {
                    $weight[] = $item['commodity_code'];
                }
            }

            if (!empty($weight)) {
                $this->totalCommodityCode = implode(',', $weight);
            }
        }

        return $this->totalCommodityCode;
    }

    protected function getTotalCommodityTypes($packages)
    {
        if ($this->totalCommodityTypes === null) {
            $weight = array();
            foreach ($packages AS $item) {
                if (!empty($item['enable']) && isset($item['commodity_type'])) {
                    $weight[] = $item['commodity_type'];
                }
            }

            if (!empty($weight)) {
                $this->totalCommodityTypes = implode(',', $weight);
            }
        }

        return $this->totalCommodityTypes;
    }

    protected function getTotalCommodityType($packages)
    {
        if ($this->totalCommodityType === null) {
            $weight = array();
            foreach ($packages AS $item) {
                if (!empty($item['enable']) && isset($item['commodity_type'])) {
                    if (!in_array($item['commodity_type'], $weight)) {
                        $weight[] = $item['commodity_type'];
                    }
                }
            }

            if (!empty($weight)) {
                $this->totalCommodityType = implode(',', $weight);
            }
        }

        return $this->totalCommodityType;
    }

    protected function getTotalDescription($packages)
    {
        if ($this->totalDescription === null) {
            $weight = array();
            foreach ($packages AS $item) {
                if (!empty($item['enable']) && isset($item['name'])) {
                    $weight[] = $item['name'];
                }
            }

            if (!empty($weight)) {
                $this->totalDescription = implode(',', $weight);
            }
        }

        return $this->totalDescription;
    }

    protected function getTotalPrice($packages)
    {
        if ($this->totalPrice === null) {
            $weight = array();
            foreach ($packages AS $item) {
                if (!empty($item['enable']) && isset($item['price'])) {
                    $weight[] = number_format(round($item['price'], 2), 2, '.', '');
                }
            }

            if (!empty($weight)) {
                $this->totalPrice = implode(',', $weight);
            }
        }

        return $this->totalPrice;
    }

    protected function getTotalQty($packages)
    {
        if ($this->totalQty === null) {
            $weight = array();
            foreach ($packages AS $item) {
                if (!empty($item['enable']) && isset($item['qty'])) {
                    $weight[] = (int)$item['qty'];
                }
            }

            if (!empty($weight)) {
                $this->totalQty = implode(',', $weight);
            }
        }

        return $this->totalQty;
    }

    protected function getCountProducts($packages)
    {
        $weight = 0;
        foreach ($packages AS $item) {
            if (!empty($item['enable'])) {
                $weight++;
            }
        }

        return $weight;
    }
}