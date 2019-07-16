<?php

/**
 * @author    Danail Kyosev <ddkyosev@gmail.com>
 * @copyright 2014, Clippings Ltd.
 * @license   http://spdx.org/licenses/BSD-3-Clause
 */
class Infomodus_Dhllabel_Model_Src_Request_ShipmentRequest extends Infomodus_Dhllabel_Model_Src_Request_AbstractRequest
{
    protected $required = array(
        'RegionCode' => 'EU',
        'LanguageCode' => 'EN',
        'PiecesEnabled' => 'Y',
        'Billing' => null,
        'Consignee' => null,
        'Dutiable' => null,
        'Reference' => null,
        'ShipmentDetails' => null,
        'Shipper' => null,
        'SpecialService' => null,
        'Notification' => null,
        'EProcShip' => null,
        'DocImages' => null,
        'LabelImageFormat' => 'PDF',
        'RequestArchiveDoc' => 'Y',
        'Label' => null,
        'DGs' => null,
    );

    public function setSpecialService($specialService, $amount=null, $curency=null)
    {
        if (!is_array($this->required['SpecialService'])) {
            $this->required['SpecialService'] = array();
        }

        $this->required['SpecialService'][] = array('SpecialServiceType' => $specialService, 'CODAmount' => $amount, 'CODCurrencyCode' => $curency);

        return $this;
    }

    public function setDangerousGoods(Infomodus_Dhllabel_Model_Src_Request_Partials_DangerousGoods $dangerousGoods)
    {
        $this->required['DGs'] = $dangerousGoods;
        return $this;
    }

    protected function buildRoot()
    {
        $root = $this->xml->createElementNS("http://www.dhl.com", 'req:ShipmentRequest');
        $root->setAttributeNS(
            'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation',
            'http://www.dhl.com ship-val-global-req.xsd'
        );
        $root->setAttribute('schemaVersion', '6.21');

        $this->currentRoot = $this->xml->appendChild($root);

        return $this;
    }

    protected function buildRequestType()
    {
        // No request type for shipment
        return $this;
    }

    public function buildDocImages($type, $image, $imageFormat)
    {
        $docImage = new Infomodus_Dhllabel_Model_Src_Request_Partials_DocImages();
        $docImage->setType($type);
        $docImage->setImage($image);
        $docImage->setImageFormat($imageFormat);

        return $this->setDocImage($docImage);
    }

    public function setDocImage(Infomodus_Dhllabel_Model_Src_Request_Partials_DocImages $docImage)
    {
        $this->required['DocImages'] = $docImage;

        return $this;
    }

    public function setLabelImageFormat($labelImageFormat)
    {
        $this->required['LabelImageFormat'] = $labelImageFormat;

        return $this;
    }

    public function setRequestArchiveDoc($requestArchiveDoc)
    {
        $this->required['RequestArchiveDoc'] = $requestArchiveDoc;

        return $this;
    }

    /**
     * @param string $regionCode Indicates the shipment to be routed to the specific region eCom backend.
     *                           Valid values are AP, EU and AM.
     */
    public function setRegionCode($regionCode)
    {
        $this->required['RegionCode'] = $regionCode;

        return $this;
    }

    /**
     * @param string $languageCode ISO language code used by the requestor
     */
    public function setLanguageCode($languageCode)
    {
        $this->required['LanguageCode'] = $languageCode;

        return $this;
    }

    /**
     * @param Partials\Billing $billing Billing information of the shipment
     */
    public function setBilling(Infomodus_Dhllabel_Model_Src_Request_Partials_Billing $billing)
    {
        $this->required['Billing'] = $billing;

        return $this;
    }

    /**
     * @param Partials\Consignee $consignee Shipment receiver information
     */
    public function setConsignee(Infomodus_Dhllabel_Model_Src_Request_Partials_Consignee $consignee)
    {
        $this->required['Consignee'] = $consignee;

        return $this;
    }

    public function setPlace(Place $place)
    {
        $this->required['Place'] = $place;

        return $this;
    }

    public function setLabel(Infomodus_Dhllabel_Model_Src_Request_Partials_Label $format)
    {
        $this->required['Label'] = $format;

        return $this;
    }

    public function setReference(Infomodus_Dhllabel_Model_Src_Request_Partials_Reference $reference)
    {
        $this->required['Reference'] = $reference;

        return $this;
    }

    public function setDutiable(Infomodus_Dhllabel_Model_Src_Request_Partials_Dutiable $dutiable)
    {
        $this->required['Dutiable'] = $dutiable;

        return $this;
    }

    /**
     * @param Partials\ShipmentDetails $shipmentDetails Shipment details
     */
    public function setShipmentDetails(Infomodus_Dhllabel_Model_Src_Request_Partials_ShipmentDetails $shipmentDetails)
    {
        $this->required['ShipmentDetails'] = $shipmentDetails;

        return $this;
    }

    /**
     * @param Partials\Shipper $shipper Shipper information
     */
    public function setShipper(Infomodus_Dhllabel_Model_Src_Request_Partials_Shipper $shipper)
    {
        $this->required['Shipper'] = $shipper;

        return $this;
    }

    public function setNotification($notification)
    {
        if (!is_array($this->required['Notification'])) {
            $this->required['Notification'] = array();
        }

        $this->required['Notification'][] = $notification;

        return $this;
    }

    public function setEProcShip($eProcShip)
    {
        $this->required['EProcShip'] = $eProcShip;

        return $this;
    }

    public function buildBilling($shipperAccountNumber, $shippingPaymentType, $billingAccountNumber = null, $dutyPaymentType = null, $dutyAccountNumber = null)
    {
        $billing = new Infomodus_Dhllabel_Model_Src_Request_Partials_Billing();
        $billing->setShipperAccountNumber($shipperAccountNumber)
            ->setShippingPaymentType($shippingPaymentType);
        if ($billingAccountNumber /*&& $billingAccountNumber != "T"*/ && $shippingPaymentType != "S") {
            $billing->setBillingAccountNumber($billingAccountNumber);
        }

        if ($dutyPaymentType !== null && $dutyAccountNumber !== null) {
            $billing->setDutyPaymentType($dutyPaymentType);
            $billing->setDutyAccountNumber($dutyAccountNumber);
        }

        return $this->setBilling($billing);
    }

    public function buildConsignee(
        $companyName,
        $addressLine,
        $addressLine2 = "",
        $addressLine3 = "",
        $city,
        $postalCode,
        $countryCode,
        $countryName,
        $contactName,
        $contactPhoneNumber,
        $divisionName = null,
        $divisionCode = null,
        $email = null
    )
    {
        $consignee = new Infomodus_Dhllabel_Model_Src_Request_Partials_Consignee();
        $consignee->setCompanyName($companyName)
            ->setAddressLine($addressLine);
        if (strlen($addressLine2) > 0) {
            $consignee->setAddressLine2($addressLine2);
            if (strlen($addressLine3) > 0) {
                $consignee->setAddressLine3($addressLine3);
            }
        }
        $consignee->setCity($city)
            ->setPostalCode($postalCode)
            ->setCountryCode($countryCode)
            ->setCountryName($countryName)
            ->setDivision($divisionName)
            ->setDivisionCode($divisionCode);

        $contact = new Infomodus_Dhllabel_Model_Src_Request_Partials_Contact();
        $contact->setPersonName($contactName)
            ->setPhoneNumber($contactPhoneNumber)
            ->setEmail($email);

        $consignee->setContact($contact);

        return $this->setConsignee($consignee);
    }

    public function buildPlace(
        $residenceOrBusiness,
        $companyName,
        $addressLine,
        $city,
        $postalCode,
        $countryCode,
        $countryName,
        $contactName,
        $contactPhoneNumber,
        $division = ""
    )
    {
        $consignee = new Infomodus_Dhllabel_Model_Src_Request_Partials_Place();
        $consignee->setResidenceOrBusiness($residenceOrBusiness)
            ->setCompanyName($companyName)
            ->setAddressLine($addressLine)
            ->setCity($city)
            ->setPostalCode($postalCode)
            ->setCountryCode($countryCode)
            ->setCountryName($countryName);
        if ($division != "") {
            $consignee->setDivision($division);
        }

        $contact = new Infomodus_Dhllabel_Model_Src_Request_Partials_Contact();
        $contact->setPersonName($contactName)
            ->setPhoneNumber($contactPhoneNumber);

        $consignee->setContact($contact);

        return $this->setPlace($consignee);
    }

    public function buildShipmentDetails(
        array $pieces,
        $globalProductCode,
        $localProductCode,
        $date,
        $contents,
        $currencyCode,
        $weightUnit = 'K',
        $dimensionUnit = 'C',
        $packageType = null,
        $doorto = null,
        $isDutiable = null/*,
        $isCODItaly = null*/
    )
    {
        $shipmentDetails = new Infomodus_Dhllabel_Model_Src_Request_Partials_ShipmentDetails();
        $shipmentDetails->setGlobalProductCode($globalProductCode)
            ->setLocalProductCode($localProductCode)
            ->setDate($date)
            ->setContents($contents)
            ->setCurrencyCode($currencyCode)
            ->setWeightUnit($weightUnit)
            ->setDimensionUnit($dimensionUnit)
            ->setPackageType($packageType);

        $pieceId = 0;
        $weight = 0;
        foreach ($pieces as $pieceData) {
            $piece = new Infomodus_Dhllabel_Model_Src_Request_Partials_ShipmentPiece();
            $piece->setPieceId(++$pieceId);
            if (array_key_exists('height', $pieceData)) {
                $piece->setPackageType($packageType)
                    ->setHeight($pieceData['height'])
                    ->setDepth($pieceData['depth'])
                    ->setWidth($pieceData['width']);
            }

            $piece->setWeight($pieceData['weight']);
            /*if ($isCODItaly !== null) {
                $pieceReference = new Infomodus_Dhllabel_Model_Src_Request_Partials_PieceReference();
                $pieceReference->setReferenceType('CU');
                $pieceReference->setReferenceId($isCODItaly);
                $piece->setPieceReference($pieceReference);
            }*/

            $shipmentDetails->addPiece($piece);
            $weight += (float)$pieceData['weight'];
        }

        $shipmentDetails->setNumberOfPieces($pieceId)
            ->setWeight($weight);
        $shipmentDetails->setDoorTo($doorto);
        $shipmentDetails->setIsDutiable($isDutiable);

        return $this->setShipmentDetails($shipmentDetails);
    }

    public function buildShipper(
        $shipperId,
        $companyName,
        $addressLine,
        $addressLine2 = "",
        $addressLine3 = "",
        $city,
        $postalCode,
        $countryCode,
        $countryName,
        $contactName,
        $contactPhoneNumber,
        $divisionName = null,
        $divisionCode = null
    )
    {
        $shipper = new Infomodus_Dhllabel_Model_Src_Request_Partials_Shipper();
        $shipper->setShipperId($shipperId)
            ->setCompanyName($companyName)
            ->setAddressLine($addressLine);
        if (strlen($addressLine2) > 0) {
            $shipper->setAddressLine2($addressLine2);
            if (strlen($addressLine3) > 0) {
                $shipper->setAddressLine3($addressLine3);
            }
        }

        $shipper->setCity($city)
            ->setPostalCode($postalCode)
            ->setCountryCode($countryCode)
            ->setCountryName($countryName)
            ->setDivision($divisionName)
            ->setDivisionCode($divisionCode);

        $contact = new Infomodus_Dhllabel_Model_Src_Request_Partials_Contact();
        $contact->setPersonName($contactName)
            ->setPhoneNumber($contactPhoneNumber);

        $shipper->setContact($contact);

        return $this->setShipper($shipper);
    }

    public function buildNotification($emailAddress, $message)
    {
        return $this->setNotification(
            array(
                'EmailAddress' => $emailAddress,
                'Message' => $message
            )
        );
    }

    public function buildLabelFormat($format)
    {
        $label = new Infomodus_Dhllabel_Model_Src_Request_Partials_Label();
        $label->setLabelTemplate($format);

        return $this->setLabel($label);
    }

    public function buildReference($referenceId)
    {
        $reference = new Infomodus_Dhllabel_Model_Src_Request_Partials_Reference();
        $reference->setReferenceId($referenceId);

        return $this->setReference($reference);
    }

    public function buildDutiable($declaredValue, $declaredCurrency, $termsOfTrade)
    {
        $dutiable = new Infomodus_Dhllabel_Model_Src_Request_Partials_Dutiable();
        $dutiable->setDeclaredValue($declaredValue);
        $dutiable->setDeclaredCurrency($declaredCurrency);
        $dutiable->setTermsOfTrade($termsOfTrade);

        return $this->setDutiable($dutiable);
    }

    public function buildSpecialService($specialType, $amount=null, $curency=null)
    {
        return $this->setSpecialService($specialType, $amount, $curency);
    }

    public function buildDangerousGoods($pieces, $weightUnit, $weingUnitKoef)
    {
        $dangerousGoods = new Infomodus_Dhllabel_Model_Src_Request_Partials_DangerousGoods();
        $isLeastOneDangerous = false;
        foreach ($pieces as $pieceData) {
            if (isset($pieceData['enable'])
                && $pieceData['enable'] == 1
                && isset($pieceData['dangerous_goods'])
                && $pieceData['dangerous_goods'] == 1) {
                $piece = new Infomodus_Dhllabel_Model_Src_Request_Partials_DgPiece();
                $piece->setContentID($pieceData['dg_attribute_content_id']);
                $piece->setLabelDesc($pieceData['dg_attribute_label']);
                $piece->setNetWeight(round($pieceData['weight'] * $weingUnitKoef, 2));
                $piece->setUOM($weightUnit);
                $piece->setUNCode($pieceData['dg_attribute_uncode']);
                $dangerousGoods->addPiece($piece);
                $isLeastOneDangerous = true;
            }
        }

        if ($isLeastOneDangerous === false) {
            return $this;
        }

        return $this->setDangerousGoods($dangerousGoods);
    }
}
