<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Upslabel_Adminhtml_Upslabel_UpslabelController extends Mage_Adminhtml_Controller_Action
{

    public $defConfParams;
    public $defParams;
    public $upsl = array();
    public $upsl2;
    
    public $paymentmethod;
    public $shipByUps;
    public $shipByUpsCode;
    public $shipByUpsMethodName;
    public $totalWeight;
    public $imShipment = NULL;
    public $sku = array();
    public $imOrder;
    public $shippingAmount;
    public $upsAccounts;
    public $shipmentTotalPrice;
    public $shippingAddress;
    public $configMethod;
    public $configOptions;
    private $shipment_id = NULL;

    protected $_publicActions = array('intermediate');

    public function __construct($op1 = NULL, $op2 = NULL, $op3 = array())
    {
        if ($op1 != NULL) {
            return parent::__construct($op1, $op2, $op3);
        } else {
            return $this;
        }
    }

    protected function _isAllowed()
    {
        return true;
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('upslabel/items')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()
            ->renderLayout();
    }

    public function showlabelAction()
    {
        /*if (strlen(Mage::getStoreConfig('upslabel/additional_settings/order_number')) > 1) {*/
        $order_id = $this->getRequest()->getParam('order_id');
        $type = $this->getRequest()->getParam('type');
        $shipment_id = $this->getRequest()->getParam('shipment_id', null);
        $params = $this->getRequest()->getParams();
        $this->imOrder = Mage::getModel('sales/order')->load($order_id);
        $storeId = NULL;
        

        $this->loadLayout();
        $AccessLicenseNumber = Mage::getStoreConfig('upslabel/credentials/accesslicensenumber');
        $UserId = Mage::getStoreConfig('upslabel/credentials/userid');
        $Password = Mage::getStoreConfig('upslabel/credentials/password');
        $shipperNumber = Mage::getStoreConfig('upslabel/credentials/shippernumber');

        $lbl = Mage::getModel('upslabel/ups');
        $lbl->setCredentials($AccessLicenseNumber, $UserId, $Password, $shipperNumber);
        if ($shipment_id !== null) {
            $collections = Mage::getModel('upslabel/upslabel');
            $collection = $collections->getCollection()->addFieldToFilter('shipment_id', $shipment_id)->addFieldToFilter('type', $type)->addFieldToFilter('status', 0);
            $firstItem = $collection->getFirstItem();
            if ($type == 'shipment') {
                $backLink = $this->getUrl('adminhtml/sales_order_shipment/view/shipment_id/' . $shipment_id);
            } else {
                $backLink = $this->getUrl('adminhtml/sales_order_creditmemo/view/creditmemo_id/' . $shipment_id);
            }
        } else {
            $backLink = $this->getUrl('adminhtml/sales_order/view/order_id/' . $order_id);
        }
        if ($shipment_id === null || $firstItem->getShipmentId() != $shipment_id) {
            $arrPackagesOld = $this->getRequest()->getParam('package');
            $arrPackages = array();
            if (count($arrPackagesOld) > 0) {
                foreach ($arrPackagesOld AS $k => $v) {
                    $i = 0;
                    foreach ($v AS $d => $f) {
                        $arrPackages[$i][$k] = $f;
                        $i += 1;
                    }
                }
                unset($v, $k, $i, $d, $f);
            }
            $lbl = $this->setParams($lbl, $params, $arrPackages );
            $upsl2 = null;
            if ($type == 'shipment' || $type == 'invert') {
                $upsl = $lbl->getShip($type);
                if (isset($params['default_return']) && $params['default_return'] == 1) {
                    $lbl->serviceCode = array_key_exists('default_return_servicecode', $params) ? $params['default_return_servicecode'] : '';
                    $upsl2 = $lbl->getShipFrom();
                }
            } else if ($type == 'refund') {
                $upsl = $lbl->getShipFrom($type);
            } else if ($type == 'ajaxprice_shipment') {
                $upsl = $lbl->getShipPrice($type);
                if (isset($params['default_return']) && $params['default_return'] == 1) {
                    $lbl->serviceCode = array_key_exists('default_return_servicecode', $params) ? $params['default_return_servicecode'] : '';
                    $upsl2 = $lbl->getShipPriceFrom($type);
                    $upsl = '{"0":' . $upsl . ', "1":' . $upsl2 . '}';
                }
                $this->getResponse()->setHeader('Content-Type', 'text/json')->setBody($upsl);
                return;
            } else if ($type == 'ajaxprice_invert') {
                $this->getResponse()->setHeader('Content-Type', 'text/json')->setBody($lbl->getShipPrice($type));
                return;
            } else if ($type == 'ajaxprice_refund') {
                $this->getResponse()->setHeader('Content-Type', 'text/json')->setBody($lbl->getShipPriceFrom($type));
                return;
            }

            $this->saveDB($upsl, $upsl2, $params, $order_id, $shipment_id, $type);
        }

        Mage::register('order_id', $order_id);
        Mage::register('shipment_id', $shipment_id);
        $collections = Mage::getModel('upslabel/upslabel');
        $collection = $collections->getCollection()->addFieldToFilter('order_id', $order_id)->addFieldToFilter('type', $type);
        if ($this->shipment_id !== null) {
            $collection->addFieldToFilter('shipment_id', $this->shipment_id);
        }
        Mage::register('upsl', $collection);

        Mage::register('type', $type);
        Mage::register('backLink', $backLink);

        if ($shipment_id === null && (!array_key_exists('error', $upsl) || !$upsl['error'])) {
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('upslabel')->__('UPS label(s) was created for order ' . $this->imOrder->getIncrementId()));
            $this->_redirect('adminhtml/upslabel_lists/index');
        }
        $this->renderLayout();
        /* } else {
             echo Mage::helper('upslabel')->__('Error: Required to fill in the Order number in the module configuration');
         }*/
    }

    public
    function saveDB($upsl, $upsl2 = NULL, $params, $order_id, $shipment_id, $type)
    {
        $this->imOrder = Mage::getModel('sales/order')->load($order_id);
        $storeId = NULL;
        
        $upslabel = Mage::getModel('upslabel/upslabel');
        $colls2 = $upslabel->getCollection()->addFieldToFilter('order_id', $order_id)/*->addFieldToFilter('type', $type)*/
        ->addFieldToFilter('status', 1);
        if (count($colls2) > 0) {
            foreach ($colls2 AS $c) {
                $c->delete();
            }
        }
        if (!array_key_exists('error', $upsl) || !$upsl['error']) {
            if ($shipment_id === null && $type == "shipment") {
                if ($this->imOrder->canShip()) {
                    $shipmentId = Mage::getModel('sales/order_shipment_api_v2')->create($this->imOrder->getIncrementId(), array(), NULL, false, false);
                    $shipment_id = Mage::getModel('sales/order_shipment')->load($shipmentId, 'increment_id')->getId();
                } else {
                    $shipment = $this->imOrder->getShipmentsCollection()->getFirstItem();
                    $shipment_id = $shipment->getId();
                }
                $this->shipment_id = $shipment_id;
            }

            foreach ($upsl['arrResponsXML'] AS $upsl_one) {
                $upslabel = Mage::getModel('upslabel/upslabel');
                $upslabel->setTitle('Order ' . $order_id . ' TN' . $upsl_one['trackingnumber']);
                $upslabel->setOrderId($order_id);
                $upslabel->setShipmentId($shipment_id);
                $upslabel->setType($type);
                $upslabel->setTrackingnumber($upsl_one['trackingnumber']);
                $upslabel->setShipmentidentificationnumber($upsl['shipidnumber']);
                $upslabel->setShipmentdigest($upsl['digest']);
                if ($upsl_one['type_print'] !== 'link') {
                    $upslabel->setLabelname('label' . $upsl_one['trackingnumber'] . '.' . strtolower($upsl_one['type_print']));
                } else {
                    $upslabel->setLabelname($upsl_one['labelname']);
                }
                $upslabel->setStatustext('Successfully');
                $upslabel->setTypePrint($upsl_one['type_print']);
                $upslabel->setStatus(0);
                if (isset($upsl['inter_invoice']) && $upsl['inter_invoice'] !== NULL) {
                    $upslabel->setInternationalInvoice(1);
                }
                $upslabel->setCreatedTime(date("Y-m-d H:i:s"));
                $upslabel->setUpdateTime(date("Y-m-d H:i:s"));
                if ($upslabel->save() !== FALSE
                    && Mage::getStoreConfig('upslabel/printing/automatic_printing') == 1
                    && Mage::getStoreConfig('upslabel/printing/printer') != "GIF"
                    && isset($upsl_one['graphicImage'])
                    && $upsl_one['type_print'] != 'link'
                ) {
                    Mage::helper('upslabel/help')->sendPrint($upsl_one['graphicImage']);
                    $upslabel->setRvaPrinted(1)->save();
                }

                $upslabel = Mage::getModel('upslabel/labelprice');
                $upslabel->setOrderId($order_id);
                $upslabel->setShipmentId($shipment_id);
                $upslabel->setPrice($upsl['price']['price'] . " " . $upsl['price']['currency']);
                $upslabel->setType($type);
                $upslabel->save();
                $this->upsl[] = $upslabel;
            }
            $path = Mage::getBaseDir('media') . DS . 'upslabel' . DS . 'inter_pdf' . DS;
            if (!is_dir($path)) {
                mkdir($path, 0777);
            }
            if (isset($upsl['inter_invoice']) && $upsl['inter_invoice'] !== NULL) {
                file_put_contents($path . $upsl['shipidnumber'] . ".pdf", base64_decode($upsl['inter_invoice']));
            }
            $pathTurnInPage = Mage::getBaseDir('media') . DS . 'upslabel' . DS . 'turn_in_page' . DS;
            if (!is_dir($pathTurnInPage)) {
                mkdir($pathTurnInPage, 0777);
            }
            if (isset($upsl['turn_in_page']) && $upsl['turn_in_page'] !== NULL) {
                file_put_contents($pathTurnInPage . $upsl['shipidnumber'] . ".html", base64_decode($upsl['turn_in_page']));
            }
            if (isset($params['default_return']) && $params['default_return'] == 1) {
                if (isset($upsl2) && !empty($upsl2) && (!array_key_exists('error', $upsl2) || !$upsl2['error'])) {
                    foreach ($upsl2['arrResponsXML'] AS $upsl_one) {
                        $upslabel = Mage::getModel('upslabel/upslabel');
                        $upslabel->setTitle('Order ' . $order_id . ' TN' . $upsl_one['trackingnumber']);
                        $upslabel->setOrderId($order_id);
                        $upslabel->setShipmentId($shipment_id);
                        $upslabel->setType($type);
                        $upslabel->setTrackingnumber($upsl_one['trackingnumber']);
                        $upslabel->setShipmentidentificationnumber($upsl2['shipidnumber']);
                        $upslabel->setShipmentdigest($upsl2['digest']);
                        if ($upsl_one['type_print'] !== 'link') {
                            $upslabel->setLabelname('label' . $upsl_one['trackingnumber'] . '.' . strtolower($upsl_one['type_print']));
                        } else {
                            $upslabel->setLabelname($upsl_one['labelname']);
                        }
                        $upslabel->setStatustext('Successfully');
                        $upslabel->setTypePrint($upsl_one['type_print']);
                        $upslabel->setStatus(0);
                        $upslabel->setCreatedTime(date("Y-m-d H:i:s"));
                        $upslabel->setUpdateTime(date("Y-m-d H:i:s"));
                        if ($upslabel->save() !== FALSE
                            && Mage::getStoreConfig('upslabel/printing/automatic_printing') == 1
                            && Mage::getStoreConfig('upslabel/printing/printer') != "GIF"
                            && $upsl_one['type_print'] != 'virtual'
                            && $upsl_one['type_print'] != 'link'
                        ) {
                            Mage::helper('upslabel/help')->sendPrint($upsl_one['graphicImage']);
                            $upslabel->setRvaPrinted(1)->save();
                        }

                        $upslabel = Mage::getModel('upslabel/labelprice');
                        $upslabel->setOrderId($order_id);
                        $upslabel->setShipmentId($shipment_id);
                        $upslabel->setPrice($upsl2['price']['price'] . " " . $upsl2['price']['currency']);
                        $upslabel->setType($type);
                        $upslabel->save();
                        $this->upsl2 = $upslabel;
                    }
                    if (isset($params['addtrack']) && $params['addtrack'] == 1 && $type == 'shipment') {
                        $trTitle = 'United Parcel Service (return)';
                        $shipment = Mage::getModel('sales/order_shipment')->load($shipment_id);
                        if($shipment) {
                            foreach ($upsl2['arrResponsXML'] AS $upsl_one1) {
                                $track = Mage::getModel('sales/order_shipment_track')
                                    ->setNumber(trim($upsl_one1['trackingnumber']))
                                    ->setCarrierCode('ups')
                                    ->setTitle($trTitle);
                                $shipment->addTrack($track);
                            }
                            $shipment->save();
                        }
                    }
                } else {
                    $upslabel = Mage::getModel('upslabel/upslabel');
                    $upslabel->setTitle('Order ' . $order_id);
                    $upslabel->setOrderId($order_id);
                    $upslabel->setShipmentId($shipment_id);
                    $upslabel->setType($type);
                    $upslabel->setStatustext($upsl2['errordesc']);
                    $upslabel->setStatus(1);
                    $upslabel->setRequest($upsl2['request']);
                    $upslabel->setResponse($upsl2['response']);
                    $upslabel->setCreatedTime(date("Y-m-d H:i:s"));
                    $upslabel->setUpdateTime(date("Y-m-d H:i:s"));
                    $upslabel->save();
                    $this->upsl2 = $upslabel;
                }
            }

            if (isset($params['addtrack']) && $params['addtrack'] == 1 && $type == 'shipment') {
                $trTitle = 'United Parcel Service';
                $shipment = Mage::getModel('upslabel/shipment')->load($shipment_id);
                if($shipment) {
                    foreach ($upsl['arrResponsXML'] AS $upsl_one1) {
                        $track = Mage::getModel('sales/order_shipment_track')
                            ->setNumber(trim($upsl_one1['trackingnumber']))
                            ->setCarrierCode('ups')
                            ->setTitle($trTitle);
                        $shipment->addTrack($track);
                    }
                    if (Mage::getStoreConfig('upslabel/shipping/track_send') == 1) {
                        $shipment->sendEmail(true, '');
                        $shipment->setEmailSent(true);
                    }
                    $shipment->save();
                }
            }
            if (Mage::getStoreConfig('upslabel/additional_settings/orderstatuses') != '') {
                $this->imOrder->setStatus(Mage::getStoreConfig('upslabel/additional_settings/orderstatuses'));
                $history = $this->imOrder->addStatusHistoryComment('UPS label created', false);
                $history->setIsCustomerNotified(false);
                $this->imOrder->save();
            }
        } else {
            $upslabel = Mage::getModel('upslabel/upslabel');
            $upslabel->setTitle('Order ' . $order_id);
            $upslabel->setOrderId($order_id);
            $upslabel->setShipmentId($shipment_id);
            $upslabel->setType($type);
            $upslabel->setStatustext($upsl['errordesc']);
            $upslabel->setStatus(1);
            $upslabel->setRequest($upsl['request']);
            $upslabel->setResponse($upsl['response']);
            $upslabel->setCreatedTime(date("Y-m-d H:i:s"));
            $upslabel->setUpdateTime(date("Y-m-d H:i:s"));
            $upslabel->save();
            $this->upsl[] = $upslabel;
        }
        return $upslabel;
    }

    public
    function setParams($lbl, $params, $packages )
    {
        $configOptions = new Infomodus_Upslabel_Model_Config_Options;
        $configMethod = new Infomodus_Upslabel_Model_Config_Upsmethod;
        $this->configMethod = $configMethod;
        $lbl->packages = $packages;
        
        $lbl->shipmentDescription = Infomodus_Upslabel_Helper_Help::escapeXML($params['shipmentdescription']);
        $lbl->shipperName = Infomodus_Upslabel_Helper_Help::escapeXML(Mage::getStoreConfig('upslabel/address_' . $params['shipper_no'] . '/companyname'));
        $lbl->shipperAttentionName = Infomodus_Upslabel_Helper_Help::escapeXML(Mage::getStoreConfig('upslabel/address_' . $params['shipper_no'] . '/attentionname'));
        $lbl->shipperPhoneNumber = Infomodus_Upslabel_Helper_Help::escapeXML(Mage::getStoreConfig('upslabel/address_' . $params['shipper_no'] . '/phonenumber'));
        $lbl->shipperAddressLine1 = Infomodus_Upslabel_Helper_Help::escapeXML(Mage::getStoreConfig('upslabel/address_' . $params['shipper_no'] . '/addressline1'));
        $lbl->shipperCity = Infomodus_Upslabel_Helper_Help::escapeXML(Mage::getStoreConfig('upslabel/address_' . $params['shipper_no'] . '/city'));
        $lbl->shipperStateProvinceCode = Infomodus_Upslabel_Helper_Help::escapeXML($configOptions->getProvinceCode(Mage::getStoreConfig('upslabel/address_' . $params['shipper_no'] . '/stateprovincecode'), Mage::getStoreConfig('upslabel/address_' . $params['shipper_no'] . '/countrycode')));
        $lbl->shipperPostalCode = Infomodus_Upslabel_Helper_Help::escapeXML(Mage::getStoreConfig('upslabel/address_' . $params['shipper_no'] . '/postalcode'));
        $lbl->shipperCountryCode = Infomodus_Upslabel_Helper_Help::escapeXML(Mage::getStoreConfig('upslabel/address_' . $params['shipper_no'] . '/countrycode'));

        $lbl->shiptoCompanyName = Infomodus_Upslabel_Helper_Help::escapeXML($params['shiptocompanyname']);
        $lbl->shiptoAttentionName = Infomodus_Upslabel_Helper_Help::escapeXML($params['shiptoattentionname']);
        $lbl->shiptoPhoneNumber = Infomodus_Upslabel_Helper_Help::escapeXML($params['shiptophonenumber']);
        $lbl->shiptoAddressLine1 = trim(Infomodus_Upslabel_Helper_Help::escapeXML($params['shiptoaddressline1']));
        $lbl->shiptoAddressLine2 = isset($params['shiptoaddressline2']) ? trim(Infomodus_Upslabel_Helper_Help::escapeXML($params['shiptoaddressline2'])) : "";
        $lbl->shiptoCity = Infomodus_Upslabel_Helper_Help::escapeXML($params['shiptocity']);
        $lbl->shiptoStateProvinceCode = Infomodus_Upslabel_Helper_Help::escapeXML($configOptions->getProvinceCode($params['shiptostateprovincecode'], $params['shiptocountrycode']));
        $lbl->shiptoPostalCode = Infomodus_Upslabel_Helper_Help::escapeXML($params['shiptopostalcode']);
        $lbl->shiptoCountryCode = Infomodus_Upslabel_Helper_Help::escapeXML($params['shiptocountrycode']);
        $lbl->residentialAddress = isset($params['residentialaddress']) ? $params['residentialaddress'] : '';

        $lbl->shipfromCompanyName = Infomodus_Upslabel_Helper_Help::escapeXML(Mage::getStoreConfig('upslabel/address_' . $params['shipfrom_no'] . '/companyname'));
        $lbl->shipfromAttentionName = Infomodus_Upslabel_Helper_Help::escapeXML(Mage::getStoreConfig('upslabel/address_' . $params['shipfrom_no'] . '/attentionname'));
        $lbl->shipfromPhoneNumber = Infomodus_Upslabel_Helper_Help::escapeXML(Mage::getStoreConfig('upslabel/address_' . $params['shipfrom_no'] . '/phonenumber'));
        $lbl->shipfromAddressLine1 = Infomodus_Upslabel_Helper_Help::escapeXML(Mage::getStoreConfig('upslabel/address_' . $params['shipfrom_no'] . '/addressline1'));
        $lbl->shipfromCity = Infomodus_Upslabel_Helper_Help::escapeXML(Mage::getStoreConfig('upslabel/address_' . $params['shipfrom_no'] . '/city'));
        $lbl->shipfromStateProvinceCode = Infomodus_Upslabel_Helper_Help::escapeXML($configOptions->getProvinceCode(Mage::getStoreConfig('upslabel/address_' . $params['shipfrom_no'] . '/stateprovincecode'), Mage::getStoreConfig('upslabel/address_' . $params['shipfrom_no'] . '/countrycode')));
        $lbl->shipfromPostalCode = Infomodus_Upslabel_Helper_Help::escapeXML(Mage::getStoreConfig('upslabel/address_' . $params['shipfrom_no'] . '/postalcode'));
        $lbl->shipfromCountryCode = Infomodus_Upslabel_Helper_Help::escapeXML(Mage::getStoreConfig('upslabel/address_' . $params['shipfrom_no'] . '/countrycode'));

        $lbl->serviceCode = array_key_exists('serviceCode', $params) ? $params['serviceCode'] : '';
        $lbl->serviceDescription = $configMethod->getUpsMethodName((array_key_exists('serviceCode', $params) ? $params['serviceCode'] : ''));

        /*$defaultWeightUnits = array_key_exists('weightunits', $params) ? $params['weightunits'] : '';*/
        $lbl->weightUnits = array_key_exists('weightunits', $params) ? $params['weightunits'] : '';
        $lbl->weightUnitsDescription = Infomodus_Upslabel_Helper_Help::escapeXML(array_key_exists('weightunitsdescription', $params) ? $params['weightunitsdescription'] : '');

        $lbl->includeDimensions = array_key_exists('includedimensions', $params) ? $params['includedimensions'] : 0;

        /*$defaultUnitOfMeasurement = array_key_exists('unitofmeasurement', $params) ? $params['unitofmeasurement'] : '';*/
        $lbl->unitOfMeasurement = array_key_exists('unitofmeasurement', $params) ? $params['unitofmeasurement'] : '';
        $lbl->unitOfMeasurementDescription = Infomodus_Upslabel_Helper_Help::escapeXML(array_key_exists('unitofmeasurementdescription', $params) ? $params['unitofmeasurementdescription'] : '');

        /*$lbl->weightUnits = Mage::helper('upslabel/help')->getWeightUnitByCountry($lbl->shiptoCountryCode);
        if ($defaultWeightUnits != $lbl->weightUnits) {
            if ($lbl->weightUnits == 'KGS') {
                $lbl->weightUnitKoef = 2.2046;
            } else {
                $lbl->weightUnitKoef = 1 / 2.2046;
            }
        }
        $lbl->unitOfMeasurement = Mage::helper('upslabel/help')->getDimensionUnitByCountry($lbl->shiptoCountryCode);
        if ($defaultUnitOfMeasurement != $lbl->unitOfMeasurement) {
            if ($lbl->unitOfMeasurement == 'CM') {
                $lbl->dimentionUnitKoef = 2.54;
            } else {
                $lbl->dimentionUnitKoef = 1 / 2.54;
            }
        }*/

        if ($params['adult'] != 1 || strpos(Mage::getStoreConfig('upslabel/quantum/adult_allow_country'), $lbl->shiptoCountryCode) !== FALSE) {
            $lbl->adult = Infomodus_Upslabel_Helper_Help::escapeXML($params['adult']);
        }

        $lbl->codYesNo = array_key_exists('cod', $params) ? $params['cod'] : '';
        $lbl->currencyCode = array_key_exists('currencycode', $params) ? $params['currencycode'] : '';
        $lbl->currencyCodeByInvoice = array_key_exists('currencyCodeByInvoice', $params) ? $params['currencyCodeByInvoice'] : '';
        $lbl->codMonetaryValue = array_key_exists('codmonetaryvalue', $params) ? $params['codmonetaryvalue'] : '';
        $lbl->codFundsCode = array_key_exists('codfundscode', $params) ? $params['codfundscode'] : '';
        $lbl->carbon_neutral = array_key_exists('carbon_neutral', $params) ? $params['carbon_neutral'] : '';
        $lbl->direct_delivery_only = array_key_exists('direct_delivery_only', $params) ? $params['direct_delivery_only'] : 0;

        if (array_key_exists('qvn', $params) && $params['qvn'] > 0) {
            $lbl->qvn = 1;
            $lbl->qvn_code = isset($params['qvn_code']) ? $params['qvn_code'] : '';
            $lbl->qvn_lang = $params['qvn_lang'];
        }
        $lbl->qvn_email_shipper = $params['qvn_email_shipper'];
        $lbl->qvn_email_shipto = $params['qvn_email_shipto'];

        if ($lbl->shipfromCountryCode != $lbl->shiptoCountryCode) {
            $lbl->shipmentcharge = array_key_exists('shipmentcharge', $params) ? $params['shipmentcharge'] : 0;
        }

        if (array_key_exists('invoicelinetotalyesno', $params) && $params['invoicelinetotalyesno'] > 0) {
            $lbl->invoicelinetotal = array_key_exists('invoicelinetotal', $params) ? $params['invoicelinetotal'] : '';
        } else {
            $lbl->invoicelinetotal = '';
        }
        if (isset($params['upsaccount']) && $params['upsaccount'] != 0) {
            $lbl->upsAccount = 1;
            $lbl->accountData = Mage::getModel('upslabel/account')->load($params['upsaccount']);
        }

        $lbl->movement_reference_number = isset($params['movement_reference_number_enabled']) && isset($params['movement_reference_number']) && $params['movement_reference_number_enabled'] == 1 ? $params['movement_reference_number'] : '';

        $lbl->international_invoice = 0;
        if ($lbl->shipfromCountryCode != $lbl->shiptoCountryCode && isset($params['international_invoice']) && $params['international_invoice'] == 1) {
            $lbl->international_invoice = Infomodus_Upslabel_Helper_Help::escapeXML($params['international_invoice']);
            $lbl->international_comments = Infomodus_Upslabel_Helper_Help::escapeXML($params['international_comments']);
            $lbl->international_invoicenumber = Infomodus_Upslabel_Helper_Help::escapeXML($params['international_invoicenumber']);
            $lbl->international_invoicedate = Infomodus_Upslabel_Helper_Help::escapeXML($params['international_invoicedate']);
            $lbl->international_reasonforexport = Infomodus_Upslabel_Helper_Help::escapeXML($params['international_reasonforexport']);
            $lbl->international_termsofshipment = Infomodus_Upslabel_Helper_Help::escapeXML($params['international_termsofshipment']);
            $lbl->international_purchaseordernumber = Infomodus_Upslabel_Helper_Help::escapeXML($params['international_purchaseordernumber']);
            $lbl->international_products = $params['international_products'];
            $lbl->soldToType = $params['international_soldtotype'];
            $lbl->declaration_statement = isset($params['declaration_statement']) ? $params['declaration_statement'] : '';
        }

        $lbl->testing = $params['testing'];

        $lbl->saturdayDelivery = isset($params['saturday_delivery']) ? $params['saturday_delivery'] : "";
        $lbl->negotiated_rates = Mage::getStoreConfig('upslabel/ratepayment/negotiatedratesindicator');
        if (isset($params['accesspoint'])) {
            $lbl->accesspoint = $params['accesspoint'];
            if ($lbl->accesspoint == 1) {
                $lbl->accesspoint_type = Infomodus_Upslabel_Helper_Help::escapeXML($params['accesspoint_type']);
                $lbl->accesspoint_name = isset($params['accesspoint_name']) ? Infomodus_Upslabel_Helper_Help::escapeXML($params['accesspoint_name']) : '';
                $lbl->accesspoint_atname = isset($params['accesspoint_atname']) ? Infomodus_Upslabel_Helper_Help::escapeXML($params['accesspoint_atname']) : '';
                $lbl->accesspoint_appuid = isset($params['accesspoint_appuid']) ? Infomodus_Upslabel_Helper_Help::escapeXML($params['accesspoint_appuid']) : '';
                $lbl->accesspoint_street = Infomodus_Upslabel_Helper_Help::escapeXML($params['accesspoint_street']);
                $lbl->accesspoint_street1 = isset($params['accesspoint_street1']) ? Infomodus_Upslabel_Helper_Help::escapeXML($params['accesspoint_street1']) : '';
                $lbl->accesspoint_street2 = isset($params['accesspoint_street2']) ? Infomodus_Upslabel_Helper_Help::escapeXML($params['accesspoint_street2']) : '';
                $lbl->accesspoint_city = Infomodus_Upslabel_Helper_Help::escapeXML($params['accesspoint_city']);
                $lbl->accesspoint_provincecode = isset($params['accesspoint_provincecode']) ? $params['accesspoint_provincecode'] : '';
                $lbl->accesspoint_postal = Infomodus_Upslabel_Helper_Help::escapeXML($params['accesspoint_postal']);
                $lbl->accesspoint_country = Infomodus_Upslabel_Helper_Help::escapeXML($params['accesspoint_country']);
            }
        }
        return $lbl;
    }

    public
    function intermediateAction()
    {
        /* if (strlen(Mage::getStoreConfig('upslabel/additional_settings/order_number')) > 1) {*/
        //$block = $this->getLayout()->getBlock('intermediate');
        $order_id = $this->getRequest()->getParam('order_id');
        $type = $this->getRequest()->getParam('type');
        $shipment_id = $this->getRequest()->getParam('shipment_id', NULL);
        $this->intermediatehandy($order_id, $type, $shipment_id);
        Mage::register('order', $this->imOrder);
        Mage::register('shippingAmount', $this->shippingAmount['shipping_amount']);
        Mage::register('shipment', $this->imShipment);
        Mage::register('type', $type);
        Mage::register('upsAccounts', $this->upsAccounts);
        Mage::register('shipmentTotalPrice', round($this->shipmentTotalPrice, 2));
        Mage::register('shipmentTotalWeight', $this->totalWeight);
        Mage::register('shipByUps', $this->shipByUps);
        Mage::register('shipByUpsCode', $this->shipByUpsCode);
        Mage::register('shipTo', $this->shippingAddress);
        Mage::register('shipByUpsMethodName', $this->shipByUpsMethodName);
        Mage::register('shipByUpsMethods', $this->configMethod->getUpsMethods());
        Mage::register('shipByUpsMethodsReturn', Mage::getModel('upslabel/config_upsmethodReturn')->getUpsMethods());
        Mage::register('unitofmeasurement', $this->configOptions->getUnitOfMeasurement());
        Mage::register('sku', $this->sku);
        Mage::register('defParams', $this->defParams);

        $AccessLicenseNumber = Mage::getStoreConfig('upslabel/credentials/accesslicensenumber');
        $UserId = Mage::getStoreConfig('upslabel/credentials/userid');
        $Password = Mage::getStoreConfig('upslabel/credentials/password');
        $shipperNumber = Mage::getStoreConfig('upslabel/credentials/shippernumber');

        $lbl = Mage::getModel('upslabel/ups');
        $lbl->setCredentials($AccessLicenseNumber, $UserId, $Password, $shipperNumber);
        $this->setParams($lbl, $this->defConfParams, $this->defParams);
        $ratesDirect = $lbl->getShipPrice('ajaxprice_shipment');
        $ratesReturn = $lbl->getShipPriceFrom('ajaxprice_refund');
        Mage::register('ratesDirect', $ratesDirect);
        Mage::register('ratesReturn', $ratesReturn);
        $ratesDirect = json_decode($ratesDirect, true);
        $ratesReturn = json_decode($ratesReturn, true);
        if (isset($ratesDirect['error'])) {
            Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('upslabel')->__('Possible mistakes: ' . $ratesDirect['error'][0]));
        }
        if (isset($ratesReturn['error'])) {
            Mage::getSingleton('adminhtml/session')->addWarning(Mage::helper('upslabel')->__('Possible mistakes for return label: ' . $ratesReturn['error'][0]));
        }
        $allowedMethods = isset($ratesDirect['methods'])?$this->implodeServiceCodeFromXmlArray($ratesDirect['methods']):array();
        if(!in_array($this->defConfParams['serviceCode'], $allowedMethods)){
            $storeId = NULL;
            
            $shippingInternational = $this->shippingAddress->getCountryId() == Mage::getStoreConfig('upslabel/address_' . Mage::getStoreConfig('upslabel/shipping/defaultshipfrom') . '/countrycode') ? 0 : 1;
            $this->defConfParams['serviceCode'] = $shippingInternational == 0 ?
            Mage::getStoreConfig('upslabel/shipping/defaultshipmentmethod') :
            Mage::getStoreConfig('upslabel/shipping/defaultshipmentmethodworld');
        }
        Mage::register('defConfParams', $this->defConfParams);
        $this->loadLayout();
        $this->renderLayout();
        /*} else {
            echo Mage::helper('upslabel')->__('Error: Required to fill in the Order number in the module configuration');
        }*/

    }

    public
    function intermediatehandy($order_id, $type, $shipment_id = NULL)
    {
        $this->configOptions = new Infomodus_Upslabel_Model_Config_Options;
        $this->configMethod = new Infomodus_Upslabel_Model_Config_Upsmethod;
        $this->defConfParams = array();
        $this->imOrder = Mage::getModel('sales/order')->load($order_id);
        if (!$this->imOrder) {
            return false;
        }
        $storeId = NULL;
        
        if(is_object($this->imOrder->getPayment())) {
            $this->paymentmethod = $this->imOrder->getPayment()->getData();
            $this->paymentmethod = $this->paymentmethod['method'];
        } else {
            $this->paymentmethod = "";
        }
        $this->shippingAddress = $this->imOrder->getShippingAddress();
        if (Mage::getStoreConfig('carriers/upsap/active') == 1 && Mage::helper('core')->isModuleOutputEnabled("Infomodus_Upsap")) {
            $modelAccessPoint = Mage::getModel("upsap/accesspoint")->getCollection()->addFieldToFilter('order_id', $order_id)->getFirstItem()->getData();
            if (count($modelAccessPoint) > 0) {
                $this->shippingAddress = $this->imOrder->getBillingAddress();
            }
        }

        if ($shipment_id !== NULL) {
            if ($type == 'shipment') {
                $this->imShipment = Mage::getModel('sales/order_shipment')->load($shipment_id);
                if ($this->imShipment) {
                    $this->shippingAmount = $this->imShipment->getShippingAddress()->getOrder()->getData();
                }
            } else {
                $this->imShipment = Mage::getModel('sales/order_creditmemo')->load($shipment_id);
                if ($this->imShipment) {
                    $this->shippingAmount = $this->imShipment->getShippingAddress()->getOrder()->getData();
                }
            }
            $shipmentAllItems = $this->imShipment->getAllItems();
        } else {
            $this->shippingAmount = $this->imOrder->getData();
            $shipmentAllItems = $this->imOrder->getAllVisibleItems();
        }
        /*print_r($this->shippingAmount);*/
        $totalPrice = 0;
        $this->totalWeight = 0;
        $totalShipmentQty = 0;
        $this->sku = array();
        $pi = 1;
        $this->defConfParams['currencyCodeByInvoice'] = $this->imOrder->getBaseCurrencyCode();
        $priceKoef = $this->imOrder->getBaseToOrderRate();
        foreach ($shipmentAllItems AS $item) {
            if (!$item->isDeleted() && ($type == "refund" || !$item->getParentItemId())) {
                $itemData = $item->getData();
                $this->sku[] = $itemData['sku'];
                if (!isset($itemData['qty'])) {
                    $itemData['qty'] = $itemData['qty_ordered'];
                }
                if (!isset($itemData['weight'])) {
                    $wItems = $this->imOrder->getAllVisibleItems();
                    foreach ($wItems AS $w) {
                        if ($w->getProductId() == $itemData["product_id"]) {
                            $itemData['weight'] = $w->getWeight();
                            break;
                        }
                    }
                }
                $totalPrice += $item->getPrice() * $itemData['qty'] / $priceKoef;
                $this->totalWeight += $itemData['weight'] * $itemData['qty'];
                $totalShipmentQty += $itemData['qty'];

                if (Mage::getStoreConfig('upslabel/paperless/enable') == 1) {
                    $productOrigin = Mage::getModel('catalog/product')->load($itemData["product_id"])->getData();
                    $commoditycode = (strlen(Mage::getStoreConfig('upslabel/paperless/international_commodity_id')) > 0 && isset($productOrigin[Mage::getStoreConfig('upslabel/paperless/international_commodity_id')])) ? $productOrigin[Mage::getStoreConfig('upslabel/paperless/international_commodity_id')] : '';

                    $scheduleBNumber = Mage::getStoreConfig('upslabel/paperless/schedule_b') == 1 ? (
                    Mage::getStoreConfig('upslabel/paperless/schedule_b_number_attribute_id') != '' ? (
                    (isset($productOrigin[Mage::getStoreConfig('upslabel/paperless/schedule_b_number_attribute_id')]) && $productOrigin[Mage::getStoreConfig('upslabel/paperless/schedule_b_number_attribute_id')] != '') ? $productOrigin[Mage::getStoreConfig('upslabel/paperless/schedule_b_number_attribute_id')] : (
                    Mage::getStoreConfig('upslabel/paperless/schedule_b_number_default')
                    )
                    ) : Mage::getStoreConfig('upslabel/paperless/schedule_b_number_default')
                    ) : '';
                    $scheduleBUnit = Mage::getStoreConfig('upslabel/paperless/schedule_b') == 1 ? (
                    Mage::getStoreConfig('upslabel/paperless/schedule_b_unitOfMeasurement_attribute_id') != '' ? (
                    (isset($productOrigin[Mage::getStoreConfig('upslabel/paperless/schedule_b_unitOfMeasurement_attribute_id')]) && $productOrigin[Mage::getStoreConfig('upslabel/paperless/schedule_b_unitOfMeasurement_attribute_id')] != '') ? $productOrigin[Mage::getStoreConfig('upslabel/paperless/schedule_b_unitOfMeasurement_attribute_id')] : (
                    Mage::getStoreConfig('upslabel/paperless/schedule_b_unitOfMeasurement')
                    )
                    ) : Mage::getStoreConfig('upslabel/paperless/schedule_b_unitOfMeasurement')
                    ) : '';
                    $this->defConfParams['international_products'][] = array(
                        'description' => strlen(Mage::getStoreConfig('upslabel/paperless/product_description')) > 0 ? Mage::getStoreConfig('upslabel/paperless/product_description') : $productOrigin['name'],
                        'country_code' => (isset($productOrigin['country_of_manufacture']) && $productOrigin['country_of_manufacture'] != '') ? $productOrigin['country_of_manufacture'] : Mage::getStoreConfig('upslabel/paperless/product_origin_country'),
                        'qty' => (int)$itemData['qty'],
                        'amount' => round($item->getPrice() / $priceKoef, 2),
                        'unit_of_measurement' => Mage::getStoreConfig('upslabel/paperless/international_unitofmeasurement'),
                        'unit_of_measurement_desc' => Mage::getStoreConfig('upslabel/paperless/international_unitofmeasurementdesc'),
                        'commoditycode' => $commoditycode,
                        'partnumber' => $pi,
                        'scheduleB_number' => $scheduleBNumber,
                        'scheduleB_unit' => $scheduleBUnit,
                    );
                }
                $pi++;
            }
        }
        $this->sku = implode(",", $this->sku);
        $totalQty = 0;
        foreach ($this->imOrder->getAllVisibleItems() AS $item) {
            $itemData = $item->getData();
            $totalQty += $itemData['qty_ordered'];
        }
        $this->upsAccounts = array("Shipper");
        $upsAcctModel = Mage::getModel('upslabel/account')->getCollection();
        foreach ($upsAcctModel AS $u1) {
            $this->upsAccounts[$u1->getId()] = $u1->getCompanyname();
        }
        if (count($shipmentAllItems) != count($this->imOrder->getAllVisibleItems()) && count($shipmentAllItems) != count($this->imOrder->getAllItems())) {
            if (Mage::getStoreConfig('upslabel/ratepayment/cod_shipping_cost') == 0) {
                $this->shipmentTotalPrice = $totalPrice;
            } else {
                $this->shipmentTotalPrice = $totalPrice + $this->imOrder->getShippingAmount() / $priceKoef;
            }
        } else {
            if (Mage::getStoreConfig('upslabel/ratepayment/cod_shipping_cost') == 0) {
                $this->shipmentTotalPrice = ($this->imOrder->getGrandTotal() - $this->imOrder->getShippingAmount()) / $priceKoef;
            } else {
                $this->shipmentTotalPrice = $this->imOrder->getGrandTotal() / $priceKoef;
            }
        }
        $this->defConfParams['upsaccount'] = Mage::getStoreConfig('upslabel/ratepayment/third_party');

        $ship_method = $this->imOrder->getShippingMethod();
        $ship_method = explode("_", $ship_method);
        $shippingInternational = $this->shippingAddress->getCountryId() == Mage::getStoreConfig('upslabel/address_' . Mage::getStoreConfig('upslabel/shipping/defaultshipfrom') . '/countrycode') ? 0 : 1;
        $this->shipByUps = preg_replace("/^ups_.{1,4}$/", 'ups', $ship_method);
        if ($type == 'shipment' || $type == 'invert') {
            if ($ship_method[0] == 'ups') {
                $this->shipByUpsCode = $this->configMethod->getUpsMethodNumber($ship_method[1]);
                $this->shipByUpsMethodName = $this->configMethod->getUpsMethodName($this->shipByUpsCode);
                $this->defConfParams['serviceCode'] = $this->shipByUpsCode;
            } else if ($ship_method[0] == 'upsap' && Mage::helper('core')->isModuleOutputEnabled("Infomodus_Upsap")) {
                $this->shipByUps = 'ups';
                $upsAP = Mage::getModel('upsap/method')->load($ship_method[1]);
                if ($upsAP) {
                    $this->shipByUpsCode = $upsAP->getUpsmethodId();
                    $this->defConfParams['serviceCode'] = strlen($this->shipByUpsCode) == 2 ? $this->shipByUpsCode : "0" . $this->shipByUpsCode;
                    $this->shipByUpsMethodName = $this->configMethod->getUpsMethodName($this->shipByUpsCode);
                }
            } else if ($ship_method[0] == 'upsadvance') {
                $this->shipByUps = 'ups';
                $this->shipByUpsCode = $ship_method[2];
                $this->defConfParams['serviceCode'] = strlen($this->shipByUpsCode) == 2 ? $this->shipByUpsCode : "0" . $this->shipByUpsCode;
                $this->shipByUpsMethodName = $this->configMethod->getUpsMethodName($this->shipByUpsCode);
            } else if ($ship_method[0] == 'caship' && Mage::helper('core')->isModuleOutputEnabled("Infomodus_Caship")) {
                $caShip = Mage::getModel('caship/method')->load($ship_method[1]);
                if($caShip && ($caShip->getCompanyType() == 'ups' || $caShip->getCompanyType() == 'upsinfomodus')) {
                    $this->shipByUps = 'ups';
                    $this->shipByUpsCode = $caShip->getUpsmethodId();
                    $this->shipByUpsMethodName = $this->configMethod->getUpsMethodName($this->shipByUpsCode);
                    $this->defConfParams['serviceCode'] = strlen($this->shipByUpsCode) == 2 ? $this->shipByUpsCode : "0" . $this->shipByUpsCode;
                }
            } else if (Mage::getStoreConfig('upslabel/shipping/shipping_method_native') == 1) {
                $modelConformity = Mage::getModel("upslabel/conformity")->getCollection()->addFieldToFilter('method_id', implode('_', $ship_method))->addFieldToFilter('store_id', 
                        1)
                    ->getSelect()->where('CONCAT(",", country_ids, ",") LIKE "%,' . $this->shippingAddress->getCountryId() . ',%"')->query()->fetch();
                if ($modelConformity && count($modelConformity) > 0) {
                    $this->defConfParams['serviceCode'] = $modelConformity["upsmethod_id"];
                }
            }
            if (!isset($this->defConfParams['serviceCode'])) {
                $this->defConfParams['serviceCode'] = $shippingInternational == 0 ?
                    Mage::getStoreConfig('upslabel/shipping/defaultshipmentmethod') :
                    Mage::getStoreConfig('upslabel/shipping/defaultshipmentmethodworld');
            }
        } else {
            $this->defConfParams['serviceCode'] = Mage::getStoreConfig('upslabel/return/default_return_method');
        }

        if ($this->totalWeight < 0.1) {
            $this->totalWeight = (float)str_replace(',', '.', Mage::getStoreConfig('upslabel/weightdimension/defweigth'));
        }



        $this->defConfParams['shipper_no'] = Mage::getStoreConfig('upslabel/shipping/defaultshipper');
        $this->defConfParams['shipfrom_no'] = Mage::getStoreConfig('upslabel/shipping/defaultshipfrom');
        $this->defConfParams['testing'] = Mage::getStoreConfig('upslabel/testmode/testing');
        $this->defConfParams['addtrack'] = Mage::getStoreConfig('upslabel/shipping/addtrack');
        if (Mage::getStoreConfig('upslabel/shipping/shipmentdescription') != 4) {
            $this->defConfParams['shipmentdescription'] = Mage::getStoreConfig('upslabel/shipping/shipmentdescription') == 1 ? ($this->shippingAddress->getFirstname() . ' ' . $this->shippingAddress->getLastname() . ', ' . $this->imOrder->getIncrementId()) : (Mage::getStoreConfig('upslabel/shipping/shipmentdescription') == 2 ? $this->shippingAddress->getFirstname() . ' ' . $this->shippingAddress->getLastname() : (Mage::getStoreConfig('upslabel/shipping/shipmentdescription') !== '' ? Mage::helper('upslabel')->__('Order Id') . ': ' . $this->imOrder->getIncrementId() : ''));
        } else {
            $this->defConfParams['shipmentdescription'] = $this->macropaste(Mage::getStoreConfig('upslabel/shipping/shipmentdescription_custom'));
        }
        $this->defConfParams['currencycode'] = Mage::getStoreConfig('upslabel/ratepayment/currencycode');
        $this->defConfParams['shipmentcharge'] = Mage::getStoreConfig('upslabel/ratepayment/shipmentcharge');
        $this->defConfParams['cod'] = Mage::getStoreConfig('upslabel/ratepayment/cod') == 1 ? 1 : (($this->paymentmethod == 'cashondelivery' || $this->paymentmethod == 'phoenix_cashondelivery') ? 1 : 0);
        $this->defConfParams['codmonetaryvalue'] = $this->shipmentTotalPrice;
        $this->defConfParams['codfundscode'] = Mage::getStoreConfig('upslabel/ratepayment/codfundscode');
        $this->defConfParams['invoicelinetotalyesno'] = Mage::getStoreConfig('upslabel/ratepayment/invoicelinetotal');
        $this->defConfParams['invoicelinetotal'] = $this->shipmentTotalPrice;
        $this->defConfParams['carbon_neutral'] = Mage::getStoreConfig('upslabel/ratepayment/carbon_neutral');
        $this->defConfParams['default_return'] = (Mage::getStoreConfig('upslabel/return/default_return') == 0 || Mage::getStoreConfig('upslabel/return/default_return_amount') > $this->shipmentTotalPrice) ? 0 : 1;
        $this->defConfParams['default_return_servicecode'] = Mage::getStoreConfig('upslabel/return/default_return_method');
        $this->defConfParams['qvn'] = Mage::getStoreConfig('upslabel/quantum/qvn');
        $this->defConfParams['qvn_code'] = explode(",", Mage::getStoreConfig('upslabel/quantum/qvn_code'));
        $this->defConfParams['qvn_email_shipper'] = Mage::getStoreConfig('upslabel/quantum/qvn_email_shipper');
        $this->defConfParams['qvn_lang'] = Mage::getStoreConfig('upslabel/quantum/qvn_lang');
        $this->defConfParams['adult'] = Mage::getStoreConfig('upslabel/quantum/adult');
        $this->defConfParams['weightunits'] = Mage::getStoreConfig('upslabel/weightdimension/weightunits');
        $this->defConfParams['includedimensions'] = Mage::getStoreConfig('upslabel/weightdimension/includedimensions');
        $this->defConfParams['unitofmeasurement'] = Mage::getStoreConfig('upslabel/weightdimension/unitofmeasurement');
        $this->defConfParams['residentialaddress'] = strlen($this->shippingAddress->getCompany()) > 0 ? '' : '<ResidentialAddress />';
        $this->defConfParams['shiptocompanyname'] = strlen($this->shippingAddress->getCompany()) > 0 ? $this->shippingAddress->getCompany() : $this->shippingAddress->getFirstname() . ' ' . $this->shippingAddress->getLastname();
        $this->defConfParams['shiptoattentionname'] = $this->shippingAddress->getFirstname() . ' ' . $this->shippingAddress->getLastname();
        $this->defConfParams['shiptophonenumber'] = Infomodus_Upslabel_Helper_Help::escapePhone($this->shippingAddress->getTelephone());
        $this->defConfParams['direct_delivery_only'] = Mage::getStoreConfig('upslabel/shipping/direct_delivery_only');

        $addressLine1 = $this->shippingAddress->getStreet();
        if (is_array($addressLine1) && isset($addressLine1[0]) && isset($addressLine1[1]) && strlen($addressLine1[0] . $addressLine1[1]) <= 35) {
            $this->defConfParams['shiptoaddressline1'] = $addressLine1[0] . $addressLine1[1];
        } else {
            $this->defConfParams['shiptoaddressline1'] = is_array($addressLine1) && array_key_exists(0, $addressLine1) ? $addressLine1[0] : $addressLine1;
            $this->defConfParams['shiptoaddressline2'] = (is_array($addressLine1) && isset($addressLine1[1])) ? $addressLine1[1] : '';
        }
        $this->defConfParams['shiptocity'] = $this->shippingAddress->getCity();
        $this->defConfParams['shiptostateprovincecode'] = $this->shippingAddress->getRegionCode() ? $this->shippingAddress->getRegionCode() : $this->shippingAddress->getRegion();
        $this->defConfParams['shiptopostalcode'] = $this->shippingAddress->getPostcode();
        $this->defConfParams['shiptocountrycode'] = $this->shippingAddress->getCountryId();
        $this->defConfParams['qvn_email_shipto'] = $this->shippingAddress->getEmail();
        $this->defConfParams['saturday_delivery'] = Mage::getStoreConfig('upslabel/shipping/saturday_delivery') == 0 ? "" : '<SaturdayDelivery />';

        $this->defConfParams['movement_reference_number_enabled'] = Mage::getStoreConfig('upslabel/shipping/movement_min_price') <= $this->shipmentTotalPrice ? 1 : 0;
        $this->defConfParams['movement_reference_number'] = Mage::getStoreConfig('upslabel/shipping/movement_reference_number');

        $this->defConfParams['international_invoice'] = Mage::getStoreConfig('upslabel/paperless/enable');
        $this->defConfParams['international_comments'] = Mage::getStoreConfig('upslabel/paperless/international_comments');
        $this->defConfParams['international_invoicenumber'] = $this->imOrder->getIncrementId();
        $this->defConfParams['international_invoicedate'] = date("Ymd", time());
        $this->defConfParams['international_reasonforexport'] = Mage::getStoreConfig('upslabel/paperless/reasonforexport');
        $this->defConfParams['international_purchaseordernumber'] = $this->imOrder->getIncrementId();
        $this->defConfParams['international_termsofshipment'] = Mage::getStoreConfig('upslabel/paperless/international_termsofshipment');
        $this->defConfParams['declaration_statement'] = Mage::getStoreConfig('upslabel/paperless/declaration_statement');

        $this->defConfParams['international_soldtotype'] = Mage::getStoreConfig('upslabel/paperless/soldto');


        $this->defConfParams['accesspoint'] = 0;
        if (Mage::getStoreConfig('carriers/upsap/active') == 1 && Mage::helper('core')->isModuleOutputEnabled("Infomodus_Upsap")) {
            if (count($modelAccessPoint) > 0) {
                $modelAccessPoint = json_decode($modelAccessPoint['address'], true);
                $this->defConfParams['accesspoint'] = 1;
                $this->defConfParams['accesspoint_type'] = Mage::getStoreConfig('carriers/upsap/type');
                $this->defConfParams['accesspoint_name'] = $modelAccessPoint['name'];
                $this->defConfParams['accesspoint_atname'] = $modelAccessPoint['name'];
                $this->defConfParams['accesspoint_appuid'] = $modelAccessPoint['appuId'];
                $this->defConfParams['accesspoint_street'] = $modelAccessPoint['addLine1'];
                if (isset($modelAccessPoint['addLine2'])) {
                    $this->defConfParams['accesspoint_street1'] = $modelAccessPoint['addLine2'];
                    if (isset($modelAccessPoint['addLine3'])) {
                        $this->defConfParams['accesspoint_street2'] = $modelAccessPoint['addLine3'];
                    }
                }
                $this->defConfParams['accesspoint_city'] = $modelAccessPoint['city'];
                $this->defConfParams['accesspoint_provincecode'] = $modelAccessPoint['state'];
                $this->defConfParams['accesspoint_postal'] = $modelAccessPoint['postal'];
                $this->defConfParams['accesspoint_country'] = $modelAccessPoint['country'];

                if(
                    $this->defConfParams['accesspoint_type'] !== '01'
                    || strpos(
                        Mage::getStoreConfig('general/country/eu_countries'),
                        $this->defConfParams['shiptocountrycode']
                    ) === false
                || strpos(
                        Mage::getStoreConfig('general/country/eu_countries'),
                        Mage::getStoreConfig('upslabel/address_' . $this->defConfParams['shipfrom_no'] . '/countrycode')
                    ) === false
                ){
                    $this->defConfParams['cod'] = 0;
                }
            }
        }

        $attributeCodeWidth = Mage::getStoreConfig('upslabel/weightdimension/attribute_code_width') ?
            Mage::getStoreConfig('upslabel/weightdimension/attribute_code_width') : 'width';
        $attributeCodeHeight = Mage::getStoreConfig('upslabel/weightdimension/attribute_code_height') ?
            Mage::getStoreConfig('upslabel/weightdimension/attribute_code_height') : 'height';
        $attributeCodeLength = Mage::getStoreConfig('upslabel/weightdimension/attribute_code_length') ?
            Mage::getStoreConfig('upslabel/weightdimension/attribute_code_length') : 'length';

        /* Multi package */
        $dimensionSets = Mage::getModel("upslabel/config_defaultdimensionsset")->toOptionArray();

        if (
            ($type == 'shipment' || $type == 'invert')
            && Mage::getStoreConfig('upslabel/packaging/frontend_multipackes_enable') == 1
        ) {
            $i = 0;
            $defParArr_1 = array();
            foreach ($shipmentAllItems AS $item) {
                if (!$item->isDeleted() && !$item->getParentItemId()) {
                    $itemData = $item->getData();
                    if (!isset($itemData['qty'])) {
                        $itemData['qty'] = $itemData['qty_ordered'];
                    }
                    if (!isset($itemData['weight'])) {
                        foreach ($this->imOrder->getAllVisibleItems() AS $w) {
                            if ($w->getProductId() == $itemData["product_id"]) {
                                $itemData['weight'] = $w->getWeight();
                            }
                        }
                    }
                    $myproduct = Mage::getModel('catalog/product')->load($itemData['product_id']);
                    $myproduct = $myproduct->getData();
                    for ($ik = 0; $ik < $itemData['qty']; $ik++) {
                        $is_attribute = 0;
                        if (Mage::getStoreConfig('upslabel/packaging/packages_by_attribute_enable') == 1) {
                            if (isset($myproduct[Mage::getStoreConfig('upslabel/packaging/packages_by_attribute_code')])) {
                                $attribute = explode(";", trim($myproduct[Mage::getStoreConfig('upslabel/packaging/packages_by_attribute_code')], ";"));
                                if (count($attribute) > 1) {
                                    $rvaPrice = $itemData['price'] / $priceKoef;
                                    foreach ($attribute AS $v) {
                                        $itemData['weight'] = $v;
                                        $itemData['price'] = round($rvaPrice / count($attribute), 2);
                                        $defParArr_1[$i] = $this->setDefParams($itemData , $type);
                                        $i++;
                                    }
                                    $is_attribute = 1;
                                }
                            }
                        }
                        if ($is_attribute !== 1) {
                            $countProductInBox = 0;
                            if (count($dimensionSets) > 0) {
                                $packer = new Infomodus_Upslabel_Model_Packer_Packer();
                                $myproduct = Mage::getModel('catalog/product')->load($itemData['product_id']);
                                $myproduct = $myproduct->getData();

                                if (
                                    isset($myproduct[$attributeCodeWidth]) && $myproduct[$attributeCodeWidth] != ""
                                    && isset($myproduct[$attributeCodeHeight]) && $myproduct[$attributeCodeHeight] != ""
                                    && isset($myproduct[$attributeCodeLength]) && $myproduct[$attributeCodeLength] != ""
                                ) {
                                    $countProductInBox++;
                                } else {
                                    $countProductInBox = 0;
                                    Mage::getSingleton('adminhtml/session')->addError("Product " . $myproduct['name'] . " does not have width or height or length");
                                }
                                $packer->addItem(new Infomodus_Upslabel_Model_Packer_TestItem($itemData['price'], $myproduct[$attributeCodeWidth], $myproduct[$attributeCodeLength], $myproduct[$attributeCodeHeight], $myproduct['weight'], true));
                                if ($countProductInBox > 0) {
                                    foreach ($dimensionSets AS $v) {
                                        if ($v['value'] !== 0) {
                                            $packer->addBox(new Infomodus_Upslabel_Model_Packer_TestBox(
                                                $v['value'],
                                                Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/outer_width'),
                                                Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/outer_length'),
                                                Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/outer_height'),
                                                Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/emptyWeight'),
                                                Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/width'),
                                                Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/length'),
                                                Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/height'),
                                                Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/maxWeight')
                                            ));
                                        }
                                    }
                                    $packedBoxes = $packer->pack();
                                    if ($packer->isError == false && count($packedBoxes) > 0) {
                                        foreach ($packedBoxes as $packedBox) {
                                            $itemDataTwo = array();
                                            $boxType = $packedBox->getBox();
                                            $itemDataTwo['width'] = $boxType->getOuterWidth();
                                            $itemDataTwo['length'] = $boxType->getOuterLength();
                                            $itemDataTwo['height'] = $boxType->getOuterDepth();
                                            $itemDataTwo['weight'] = $packedBox->getWeight();
                                            $itemsInTheBox = $packedBox->getItems();
                                            $itemDataTwo['price'] = 0;
                                            foreach ($itemsInTheBox as $item) {
                                                $itemDataTwo['price'] += $item->getDescription();
                                            }
                                            $defParArr_1[$i] = $this->setDefParams($itemDataTwo , $type);
                                            $i++;
                                        }
                                    } else {
                                        $countProductInBox = 0;
                                    }
                                }
                            }

                            if ($countProductInBox == 0) {
                                $defParArr_1[$i] = $this->setDefParams(NULL , $type);
                                $i++;
                            }
                        }
                    }
                }
            }
            $this->defParams = $defParArr_1;
        } else {
            $this->defParams = array();
            $i = 0;
            $rvaShipmentTotalPrice = $this->shipmentTotalPrice;
            if (Mage::getStoreConfig('upslabel/packaging/packages_by_attribute_enable') == 1 && ($type == 'shipment' || $type == 'invert')) {
                foreach ($shipmentAllItems AS $item) {
                    if (!$item->isDeleted() && !$item->getParentItemId()) {
                        $itemData = $item->getData();
                        if (!isset($itemData['qty'])) {
                            $itemData['qty'] = $itemData['qty_ordered'];
                        }
                        if (!isset($itemData['weight'])) {
                            foreach ($this->imOrder->getAllVisibleItems() AS $w) {
                                if ($w->getProductId() == $itemData["product_id"]) {
                                    $itemData['weight'] = $w->getWeight();
                                }
                            }
                        }
                        $itemData2 = $itemData;
                        $myproduct = Mage::getModel('catalog/product')->load($itemData['product_id']);
                        $myproduct = $myproduct->getData();
                        for ($ik = 0; $ik < $itemData['qty']; $ik++) {
                            if (isset($myproduct[Mage::getStoreConfig('upslabel/packaging/packages_by_attribute_code')])) {
                                $attribute = explode(";", trim($myproduct[Mage::getStoreConfig('upslabel/packaging/packages_by_attribute_code')], ";"));
                                if (count($attribute) > 1) {
                                    foreach ($attribute AS $v) {
                                        $this->totalWeight = $this->totalWeight - $itemData2['weight'];
                                        $itemData['price'] = round(($itemData2['price'] / $priceKoef) / count($attribute), 2);
                                        $itemData['weight'] = $v;
                                        $this->defParams[$i] = $this->setDefParams($itemData , $type);
                                        $i++;
                                    }
                                    $rvaShipmentTotalPrice = $rvaShipmentTotalPrice - $itemData2['price'];
                                }
                            }
                        }
                    }
                }
            }
            if ($this->totalWeight > 0) {
                $countProductInBox = 0;
                if ($type == 'shipment' || $type == 'invert') {
                    if (count($dimensionSets) > 0) {
                        $packer = new Infomodus_Upslabel_Model_Packer_Packer();
                        foreach ($shipmentAllItems AS $item) {
                            $itemData = $item->getData();
                            if (!isset($itemData['qty'])) {
                                $itemData['qty'] = $itemData['qty_ordered'];
                            }
                            $myproduct = Mage::getModel('catalog/product')->load($itemData['product_id']);
                            $myproduct = $myproduct->getData();

                            if (
                                isset($myproduct[$attributeCodeWidth]) && $myproduct[$attributeCodeWidth] != ""
                                && isset($myproduct[$attributeCodeHeight]) && $myproduct[$attributeCodeHeight] != ""
                                && isset($myproduct[$attributeCodeLength]) && $myproduct[$attributeCodeLength] != ""
                            ) {
                                $countProductInBox++;
                            } else {
                                $countProductInBox = 0;
                                Mage::getSingleton('adminhtml/session')->addError("Product " . $myproduct['name'] . " does not have width or height or length");
                                break;
                            }
                            for ($ik = 0; $ik < $itemData['qty']; $ik++) {
                                $packer->addItem(new Infomodus_Upslabel_Model_Packer_TestItem($itemData['price'] / $priceKoef, $myproduct[$attributeCodeWidth], $myproduct[$attributeCodeLength], $myproduct[$attributeCodeHeight], $myproduct['weight'], true));
                            }
                        }
                        if ($countProductInBox > 0) {
                            foreach ($dimensionSets AS $v) {
                                if ($v['value'] !== 0) {
                                    $packer->addBox(new Infomodus_Upslabel_Model_Packer_TestBox(
                                        $v['value'],
                                        Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/outer_width'),
                                        Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/outer_length'),
                                        Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/outer_height'),
                                        Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/emptyWeight'),
                                        Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/width'),
                                        Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/length'),
                                        Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/height'),
                                        Mage::getStoreConfig('upslabel/dimansion_' . $v['value'] . '/maxWeight')
                                    ));
                                }
                            }
                            $packedBoxes = $packer->pack();
                            if ($packer->isError == false && count($packedBoxes) > 0) {
                                foreach ($packedBoxes as $packedBox) {
                                    $itemData = array();
                                    $boxType = $packedBox->getBox();
                                    $itemData['width'] = $boxType->getOuterWidth();
                                    $itemData['length'] = $boxType->getOuterLength();
                                    $itemData['height'] = $boxType->getOuterDepth();
                                    $itemData['weight'] = $packedBox->getWeight();
                                    $itemsInTheBox = $packedBox->getItems();
                                    $itemData['price'] = 0;
                                    foreach ($itemsInTheBox as $item) {
                                        $itemData['price'] += $item->getDescription();
                                    }
                                    $this->defParams[$i] = $this->setDefParams($itemData , $type);
                                    $i++;
                                }
                            } else {
                                $countProductInBox = 0;
                            }
                        }
                    }
                }

                if ($countProductInBox == 0) {
                    $this->defParams[$i] = $this->setDefParams(NULL , $type);
                }
            }
        }
        /* END Multi package */
    }

    public
    function setDefParams($itemData = null , $type = "shipment")
    {
        $defParArr_1['packagingtypecode'] = Mage::getStoreConfig('upslabel/packaging/packagingtypecode');
        $defParArr_1['packagingdescription'] = Mage::getStoreConfig('upslabel/packaging/packagingdescription');
        $defParArr_1['packagingreferencenumbercode'] = Mage::getStoreConfig('upslabel/packaging/packagingreferencenumbercode');
        $defParArr_1['packagingreferencebarcode'] = Mage::getStoreConfig('upslabel/packaging/packagingreferencebarcode');
        $defParArr_1['packagingreferencenumbervalue'] = $this->macropaste(Mage::getStoreConfig('upslabel/packaging/packagingreferencenumbervalue'));
        $defParArr_1['packagingreferencenumbercode2'] = Mage::getStoreConfig('upslabel/packaging/packagingreferencenumbercode2');
        $defParArr_1['packagingreferencebarcode2'] = Mage::getStoreConfig('upslabel/packaging/packagingreferencebarcode2');
        $defParArr_1['packagingreferencenumbervalue2'] = $this->macropaste(Mage::getStoreConfig('upslabel/packaging/packagingreferencenumbervalue2'));
        $defParArr_1['weight'] = $itemData !== null ? $itemData['weight'] : $this->totalWeight;
        $defParArr_1['width'] = $itemData !== null && isset($itemData['width']) ? $itemData['width'] : '';
        $defParArr_1['height'] = $itemData !== null && isset($itemData['height']) ? $itemData['height'] : '';
        $defParArr_1['length'] = $itemData !== null && isset($itemData['length']) ? $itemData['length'] : '';
        $defParArr_1['packweight'] = round((float)str_replace(',', '.', Mage::getStoreConfig('upslabel/weightdimension/packweight')), 2) > 0 ? round((float)str_replace(',', '.', Mage::getStoreConfig('upslabel/weightdimension/packweight')), 2) : '0';
        $defParArr_1['additionalhandling'] = Mage::getStoreConfig('upslabel/ratepayment/additionalhandling') == 1 ? '<AdditionalHandling />' : '';
        $defParArr_1['cod'] = Mage::getStoreConfig('upslabel/ratepayment/cod') == 1 ? 1 : (($this->paymentmethod == 'cashondelivery' || $this->paymentmethod == 'phoenix_cashondelivery') ? 1 : 0);
        $defParArr_1['codfundscode'] = Mage::getStoreConfig('upslabel/ratepayment/codfundscode');
        $defParArr_1['codmonetaryvalue'] = $itemData !== null && isset($itemData['price']) ? $itemData['price'] : $this->shipmentTotalPrice;
        $defParArr_1['insuredmonetaryvalue'] = Mage::getStoreConfig('upslabel/ratepayment/insured_automaticaly') == 1 ? ($itemData !== null && isset($itemData['price']) ? $itemData['price'] : $this->shipmentTotalPrice) : 0;
        if(
        $this->defConfParams['accesspoint'] == 1 &&
        ($this->defConfParams['accesspoint_type'] !== '01'
            || strpos(
                Mage::getStoreConfig('general/country/eu_countries'),
                $this->defConfParams['shiptocountrycode']
            ) === false
            || strpos(
                Mage::getStoreConfig('general/country/eu_countries'),
                Mage::getStoreConfig('upslabel/address_' . $this->defConfParams['shipfrom_no'] . '/countrycode')
            ) === false)
        ){
            $defParArr_1['cod'] = 0;
        }
        return ($defParArr_1);
    }

    public function macropaste($value)
    {
        return str_replace(
            array("#order_id#", "#customer_name#", "#sku#"),
            array($this->imOrder->getIncrementId(), $this->shippingAddress->getFirstname() . ' ' . $this->shippingAddress->getLastname(), $this->sku),
            $value
        );
    }

    public
    function deletelabelAction()
    {
        $order_id = $this->getRequest()->getParam('order_id');
        $shipment_id = $this->getRequest()->getParam('shipment_id');
        $type = $this->getRequest()->getParam('type');
        $upslabel = Mage::getModel('upslabel/labelprice')->getCollection()->addFieldToFilter('order_id', $order_id)->addFieldToFilter('shipment_id', $shipment_id)->addFieldToFilter('type', $type);
        if (count($upslabel) > 0) {
            foreach ($upslabel AS $c) {
                $c->delete();
            }
        }
        $this->loadLayout();
        $this->renderLayout();
    }

    public
    function autoprintAction()
    {
        $order_id = $this->getRequest()->getParam('order_id');
        $shipment_id = $this->getRequest()->getParam('shipment_id', NULL);
        $label_id = $this->getRequest()->getParam('label_id', NULL);
        $shipident_id = $this->getRequest()->getParam('shipident_id', NULL);
        $type = $this->getRequest()->getParam('type');
        $path = Mage::getBaseDir('media') . DS . 'upslabel' . DS . 'label' . DS;

        $storeId = NULL;
        

        $upslabel = Mage::getModel('upslabel/upslabel');
        if ($label_id === NULL) {
            $colls2 = $upslabel->getCollection()->addFieldToFilter('order_id', $order_id);
            if ($shipment_id !== NULL) {
                $colls2->addFieldToFilter('shipment_id', $shipment_id);
            }
            $colls2->addFieldToFilter('type', $type)->addFieldToFilter('status', 0);
            foreach ($colls2 AS $coll) {
                if (file_exists($path . ($coll->getLabelname()))) {
                    if ($data = file_get_contents($path . ($coll->getLabelname()))) {
                        Mage::helper('upslabel/help')->sendPrint($data);
                        $coll->setRvaPrinted(1)->save();
                    }
                }
            }
            Mage::register('printResponse', Mage::helper('upslabel')->__('Label was sent to print'));
        } else if ($shipident_id !== NULL) {
            $colls2 = $upslabel->getCollection()->addFieldToFilter('shipmentidentificationnumber', $shipident_id);
            $colls2->addFieldToFilter('status', 0);
            foreach ($colls2 AS $coll) {
                if (file_exists($path . ($coll->getLabelname()))) {
                    if ($data = file_get_contents($path . ($coll->getLabelname()))) {
                        Mage::helper('upslabel/help')->sendPrint($data);
                        $coll->setRvaPrinted(1)->save();
                    }
                }
            }
            Mage::register('printResponse', Mage::helper('upslabel')->__('Label was sent to print'));
        } else if ($label_id !== NULL) {
            $label = $upslabel->load($label_id);
            if (file_exists($path . ($label->getLabelname()))) {
                if ($data = file_get_contents($path . ($label->getLabelname()))) {
                    //echo $path . ($label->getLabelname()); exit;
                    /*return $this->_prepareDownloadResponse('ups-labels'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').
                        '.zpl', $data, 'application/zpl');*/
                    /*header("Content-Disposition: inline; filename=ups_shipping_labels.zpl;");
                    header("Content-Type: application/zpl;");
                    echo $data;
                    exit;*/
                    Mage::helper('upslabel/help')->sendPrint($data);
                    $label->setRvaPrinted(1)->save();
                }
            }
            Mage::register('printResponse', Mage::helper('upslabel')->__('Label was sent to print'));
        }
        $this->loadLayout();
        $this->renderLayout();
    }

    public
    function downloadnotgifAction()
    {
        $order_id = $this->getRequest()->getParam('order_id');
        $shipment_id = $this->getRequest()->getParam('shipment_id');
        $label_id = $this->getRequest()->getParam('label_id');
        $type = $this->getRequest()->getParam('type');
        $path = Mage::getBaseDir('media') . DS . 'upslabel' . DS . 'label' . DS;

        $upslabel = Mage::getModel('upslabel/upslabel');
        if (!isset($label_id) || empty($label_id) || $label_id <= 0) {
            $applicationType = 'zip';
            $colls2 = $upslabel->getCollection()->addFieldToFilter('order_id', $order_id)->addFieldToFilter('shipment_id', $shipment_id)->addFieldToFilter('type', $type)->addFieldToFilter('status', 0);
            if (extension_loaded('zip')) {
                $zip = new ZipArchive();
                $zip_name = sys_get_temp_dir() . DS . 'order' . $order_id . 'shipment' . $shipment_id . '.zip';
                if ($zip->open($zip_name, ZipArchive::CREATE) !== TRUE) {
                }
                foreach ($colls2 AS $coll) {
                    if (file_exists($path . ($coll->getLabelname())) && $coll->getTypePrint() != 'GIF') {
                        $zip->addFile($path . $coll->getLabelname(), $coll->getLabelname());
                    }
                }
                $zip->close();
                if (file_exists($zip_name)) {
                    $pdfData = file_get_contents($zip_name);
                    unlink($zip_name);
                    return $this->_prepareDownloadResponse('labels_order' . $order_id . '_shipment' . $shipment_id . '.zip', $pdfData, 'application/' . $applicationType);
                }
            } else {
                $phar = new Phar(sys_get_temp_dir() . DS . 'order' . $order_id . 'shipment' . $shipment_id . '.phar');
                $phar = $phar->convertToExecutable(Phar::ZIP);

                foreach ($colls2 AS $coll) {
                    if (file_exists($path . ($coll->getLabelname())) && $coll->getTypePrint() != 'GIF') {
                        if ($data = file_get_contents($path . ($coll->getLabelname()))) {
                            $phar[$coll->getLabelname()] = $data;
                        }
                    }
                }
                if (Phar::canCompress(Phar::GZ)) {
                    $phar->compress(Phar::GZ, '.gz');
                    $applicationType = 'x-gzip';
                }
                if (file_exists($phar::running(false))) {
                    $pdfData = file_get_contents($phar::running(false));
                    @unlink($phar::running(false));
                    return $this->_prepareDownloadResponse('labels_order' . $order_id . '_shipment' . $shipment_id . '.zip', $pdfData, 'application/' . $applicationType);
                }
            }

        }
        return true;
    }

    public
    function printAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function trackstatusAction()
    {
        $ids = $this->getRequest()->getParam('upslabel');
        if (count($ids) > 0) {
            $modelTrack = Mage::getModel('upslabel/ups');
            $AccessLicenseNumber = Mage::getStoreConfig('upslabel/credentials/accesslicensenumber');
            $UserId = Mage::getStoreConfig('upslabel/credentials/userid');
            $Password = Mage::getStoreConfig('upslabel/credentials/password');
            $shipperNumber = Mage::getStoreConfig('upslabel/credentials/shippernumber');
            $modelTrack->setCredentials($AccessLicenseNumber, $UserId, $Password, $shipperNumber);
            foreach ($ids AS $id) {
                $item = Mage::getModel('upslabel/upslabel')->load($id);
                if ($item->getStatus() == 0) {
                    $result = $modelTrack->getTrackStatus($item->getTrackingnumber());
                    if (isset($result['error'])) {
                        $item->setTrackStatus($result['error']);
                        $item->setTrackStatusCode("-1");
                        $item->save();
                    } else {
                        $item->setTrackStatus($result['status']);
                        $item->setTrackStatusCode("1");
                        $item->save();
                    }
                }
            }
        }
        $this->_redirect('adminhtml/upslabel_lists/index');
        return true;
    }

    protected function implodeServiceCodeFromXmlArray($xmlArray){
        $serviceCodes = array();
        $arrNew = array();
        if(!is_array($xmlArray) || !isset($xmlArray[0])){$arrNew[0] = $xmlArray;} else {
            $arrNew = $xmlArray;
        }
        foreach ($arrNew AS $code){
            $serviceCodes[] = $code['Service']['Code'];
        }
        return $serviceCodes;
    }
}