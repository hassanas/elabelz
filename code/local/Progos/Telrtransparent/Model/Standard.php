<?php

class Progos_Telrtransparent_Model_Standard extends Mage_Payment_Model_Method_Ccsave
{

    protected $_code = 'telrtransparent';
    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = false;
    protected $_canAuthorize = false;
    protected $_formBlockType = 'telrtransparent/telrtransparent';


    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();

        $ccType = $data->getCcType();
        $info->setCcType($ccType)
            ->setCcOwner($data->getCcOwner())
            ->setCcNumber($data->getCcNumber())
            ->setCcCid($data->getCcCid())
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear());

        $_SESSION['_cardSession'] = array();
        $_SESSION['_cardSession']['cc_cid'] = $data->getCcCid();

        return $this;
    }

    /**
     * Validate payment method information object
     *
     * @param   Mage_Payment_Model_Info $info
     * @return  Mage_Payment_Model_Abstract
     */
    public function validate()
    {
        /*
        * calling parent validate function
        */
        //parent::validate();

        $info = $this->getInfoInstance();
        $errorMsg = false;
        $availableTypes = explode(',', $this->getConfigData('cctypes'));

        $ccNumber = $info->getCcNumber();

        // remove credit card number delimiters such as "-" and space
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $info->setCcNumber($ccNumber);

        $ccType = '';

        if (in_array($info->getCcType(), $availableTypes)) {
            if ($this->validateCcNum($ccNumber)
                // Other credit card type number validation
                || ($this->OtherCcType($info->getCcType()) && $this->validateCcNumOther($ccNumber))
            ) {

                $ccType = 'OT';
                $discoverNetworkRegexp = '/^(30[0-5]\d{13}|3095\d{12}|35(2[8-9]\d{12}|[3-8]\d{13})|36\d{12}'
                    . '|3[8-9]\d{14}|6011(0\d{11}|[2-4]\d{11}|74\d{10}|7[7-9]\d{10}|8[6-9]\d{10}|9\d{11})'
                    . '|62(2(12[6-9]\d{10}|1[3-9]\d{11}|[2-8]\d{12}|9[0-1]\d{11}|92[0-5]\d{10})|[4-6]\d{13}'
                    . '|8[2-8]\d{12})|6(4[4-9]\d{13}|5\d{14}))$/';
                $ccTypeRegExpList = array(
                    //Solo, Switch or Maestro. International safe
                    /*
                    // Maestro / Solo
                    'SS'  => '/^((6759[0-9]{12})|(6334|6767[0-9]{12})|(6334|6767[0-9]{14,15})'
                               . '|(5018|5020|5038|6304|6759|6761|6763[0-9]{12,19})|(49[013][1356][0-9]{12})'
                               . '|(633[34][0-9]{12})|(633110[0-9]{10})|(564182[0-9]{10}))([0-9]{2,3})?$/',
                    */
                    // Solo only
                    'SO' => '/(^(6334)[5-9](\d{11}$|\d{13,14}$))|(^(6767)(\d{12}$|\d{14,15}$))/',
                    // Visa
                    'VI' => '/^4[0-9]{12}([0-9]{3})?$/',
                    // Master Card
                    'MC' => '/^5[1-5][0-9]{14}$/',
                    // American Express
                    'AE' => '/^3[47][0-9]{13}$/',
                    // Discover Network
                    'DI' => $discoverNetworkRegexp,
                    // Dinners Club (Belongs to Discover Network)
                    'DICL' => $discoverNetworkRegexp,
                    // JCB (Belongs to Discover Network)
                    'JCB' => $discoverNetworkRegexp,

                    // Maestro & Switch
                    'SM' => '/(^(5[0678])\d{11,18}$)|(^(6[^05])\d{11,18}$)|(^(601)[^1]\d{9,16}$)|(^(6011)\d{9,11}$)'
                        . '|(^(6011)\d{13,16}$)|(^(65)\d{11,13}$)|(^(65)\d{15,18}$)'
                        . '|(^(49030)[2-9](\d{10}$|\d{12,13}$))|(^(49033)[5-9](\d{10}$|\d{12,13}$))'
                        . '|(^(49110)[1-2](\d{10}$|\d{12,13}$))|(^(49117)[4-9](\d{10}$|\d{12,13}$))'
                        . '|(^(49118)[0-2](\d{10}$|\d{12,13}$))|(^(4936)(\d{12}$|\d{14,15}$))/'
                );

                $specifiedCCType = $info->getCcType();
                if (array_key_exists($specifiedCCType, $ccTypeRegExpList)) {
                    $ccTypeRegExp = $ccTypeRegExpList[$specifiedCCType];
                    if (!preg_match($ccTypeRegExp, $ccNumber)) {
                        $errorMsg = Mage::helper('payment')->__('Credit card number mismatch with credit card type.');
                    }
                }
            } else {
                $errorMsg = Mage::helper('payment')->__('Invalid Credit Card Number');
            }

        } else {
            $errorMsg = Mage::helper('payment')->__('Credit card type is not allowed for this payment method.');
        }

        //validate credit card verification number
        if ($errorMsg === false && $this->hasVerification()) {
            $verifcationRegEx = $this->getVerificationRegEx();
            $regExp = isset($verifcationRegEx[$info->getCcType()]) ? $verifcationRegEx[$info->getCcType()] : '';
            if (!$info->getCcCid() || !$regExp || !preg_match($regExp, $info->getCcCid())) {
                //$errorMsg = '';
            }
        }

        if ($ccType != 'SS' && !$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
            $errorMsg = Mage::helper('payment')->__('Incorrect credit card expiration date.');
        }

        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }

        //This must be after all validation conditions
        if ($this->getIsCentinelValidationEnabled()) {
            $this->getCentinelValidator()->validate($this->getCentinelValidationData());
        }

        return $this;
    }

    /**
     * Validate CC details Only
     *
     * @param   cc detail
     * @return  boolean true or error string
     */
    public function validateOnly($dataArr)
    {
        $errorMsg = false;
        $availableTypes = explode(',', $this->getConfigData('cctypes'));
        $ccNumber = $dataArr['cc_number'];
        $ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
        $ccType = '';
        if (in_array($dataArr['cc_type'], $availableTypes)) {
            if ($this->validateCcNum($ccNumber)
                || ($this->OtherCcType($dataArr['cc_type']) && $this->validateCcNumOther($ccNumber))
            ) {

                $ccType = 'OT';
                $discoverNetworkRegexp = '/^(30[0-5]\d{13}|3095\d{12}|35(2[8-9]\d{12}|[3-8]\d{13})|36\d{12}'
                    . '|3[8-9]\d{14}|6011(0\d{11}|[2-4]\d{11}|74\d{10}|7[7-9]\d{10}|8[6-9]\d{10}|9\d{11})'
                    . '|62(2(12[6-9]\d{10}|1[3-9]\d{11}|[2-8]\d{12}|9[0-1]\d{11}|92[0-5]\d{10})|[4-6]\d{13}'
                    . '|8[2-8]\d{12})|6(4[4-9]\d{13}|5\d{14}))$/';
                $ccTypeRegExpList = array(
                    // Solo only
                    'SO' => '/(^(6334)[5-9](\d{11}$|\d{13,14}$))|(^(6767)(\d{12}$|\d{14,15}$))/',
                    // Visa
                    'VI' => '/^4[0-9]{12}([0-9]{3})?$/',
                    // Master Card
                    'MC' => '/^5[1-5][0-9]{14}$/',
                    // American Express
                    'AE' => '/^3[47][0-9]{13}$/',
                    // Discover Network
                    'DI' => $discoverNetworkRegexp,
                    // Dinners Club (Belongs to Discover Network)
                    'DICL' => $discoverNetworkRegexp,
                    // JCB (Belongs to Discover Network)
                    'JCB' => $discoverNetworkRegexp,

                    // Maestro & Switch
                    'SM' => '/(^(5[0678])\d{11,18}$)|(^(6[^05])\d{11,18}$)|(^(601)[^1]\d{9,16}$)|(^(6011)\d{9,11}$)'
                        . '|(^(6011)\d{13,16}$)|(^(65)\d{11,13}$)|(^(65)\d{15,18}$)'
                        . '|(^(49030)[2-9](\d{10}$|\d{12,13}$))|(^(49033)[5-9](\d{10}$|\d{12,13}$))'
                        . '|(^(49110)[1-2](\d{10}$|\d{12,13}$))|(^(49117)[4-9](\d{10}$|\d{12,13}$))'
                        . '|(^(49118)[0-2](\d{10}$|\d{12,13}$))|(^(4936)(\d{12}$|\d{14,15}$))/'
                );

                $specifiedCCType = $dataArr['cc_type'];
                if (array_key_exists($specifiedCCType, $ccTypeRegExpList)) {
                    $ccTypeRegExp = $ccTypeRegExpList[$specifiedCCType];
                    if (!preg_match($ccTypeRegExp, $ccNumber)) {
                        $errorMsg = Mage::helper('payment')->__('Credit card number mismatch with credit card type.');
                    }
                }
            } else {
                $errorMsg = Mage::helper('payment')->__('Invalid Credit Card Number');
            }

        } else {
            $errorMsg = Mage::helper('payment')->__('Credit card type is not allowed for this payment method.');
        }

        //validate credit card verification number
        if ($errorMsg === false && $this->hasVerification()) {
            $verifcationRegEx = $this->getVerificationRegEx();
            $regExp = isset($verifcationRegEx[$dataArr['cc_type']]) ? $verifcationRegEx[$dataArr['cc_type']] : '';
            if (!$dataArr['cc_cvv'] || !$regExp || !preg_match($regExp, $dataArr['cc_cvv'])) {
                //$errorMsg = '';
            }
        }

        if ($ccType != 'SS' && !$this->_validateExpDate($dataArr['cc_exy'], $dataArr['cc_exm'])) {
            $errorMsg = Mage::helper('payment')->__('Incorrect credit card expiration date.');
        }

        if ($errorMsg) {
            Mage::throwException($errorMsg);
        }
        return $this;
    }

    public function getOrderPlaceRedirectUrl()
    {
        $data = $this->getCentinelValidationData();
        $orderId = $data->getOrderNumber();
        return Mage::getUrl('telrtransparent/payment/redirect', array('_secure' => true, '_query' => 'orderId=' . $orderId));
    }
}

?>