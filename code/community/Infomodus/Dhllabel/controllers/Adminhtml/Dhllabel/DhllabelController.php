<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */

class Infomodus_Dhllabel_Adminhtml_Dhllabel_DhllabelController extends Mage_Adminhtml_Controller_Action
{

    public $upsl = null;
    public $upsl2;
    public $labelDirectIds = array();
    public $defConfParams;
    public $defParams;
    public $shipByUpsCode;
    public $totalWeight;
    public $shippingAddress;
    public $shipmentTotalPrice;
    public $upsAccounts;
    public $shipByUps;
    public $dhlAccountsDuty;
    public $shippingAmount;
    public $shipByUpsMethodName;
    public $shipByGlobalMethodName;
    public $sku;
    public $notificationMessage;

    protected $configMethod;
    protected $configOptions;
    protected $imOrder;
    protected $imShipment;

    protected $_publicActions = array('intermediate');

    protected $pdfCurrentHeight = 0;
    protected $pdfCurrentPage = 0;
    protected $pdfA4Height = null;
    protected $pdfA4Width = null;

    public function __construct($opOne = null, $opTwo = null, $opThree = array())
    {
        if ($opOne != null) {
            return parent::__construct($opOne, $opTwo, $opThree);
        } else {
            return $this;
        }
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('dhllabel/items')
            ->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Items Manager'),
                Mage::helper('adminhtml')->__('Item Manager')
            );

        return $this;
    }

    protected function _isAllowed()
    {
        if (Mage::getSingleton('admin/session')) {
            return Mage::getSingleton('admin/session')->isAllowed('sales/dhllabel');
        } else {
            return true;
        }
    }

    public function indexAction()
    {
        $this->_initAction()
            ->renderLayout();
    }

    public function showlabelAction()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $type = $this->getRequest()->getParam('type');
        $shipmentId = $this->getRequest()->getParam('shipment_id', null);
        $params = $this->getRequest()->getParams();
        $this->imOrder = Mage::getModel('sales/order')->load($orderId);
        $storeId = null;
        /*multistore*/
        $storeId = $this->imOrder->getStoreId();
        /*multistore*/
        $this->loadLayout();
        $lbl = Mage::getModel('dhllabel/dhl');

        if ($shipmentId !== null) {
            $collections = Mage::getModel('dhllabel/dhllabel');
            $collection = $collections->getCollection()->addFieldToFilter('shipment_id', $shipmentId)
                ->addFieldToFilter('type', $type)->addFieldToFilter('status', 0);
            $firstItem = $collection->getFirstItem();
            if ($type == 'shipment') {
                $backLink = $this->getUrl('adminhtml/sales_order_shipment/view/shipment_id/' . $shipmentId);
            } else {
                $backLink = $this->getUrl('adminhtml/sales_order_creditmemo/view/creditmemo_id/' . $shipmentId);
            }
        } else {
            $backLink = $this->getUrl('adminhtml/sales_order/view/order_id/' . $orderId);
        }

        if ($shipmentId === null || $firstItem->getShipmentId() != $shipmentId) {
            $arrPackagesOld = $this->getRequest()->getParam('package');
            $arrPackages = array();
            $upsl = null;
            $upslTwo = null;
            foreach ($arrPackagesOld as $k => $v) {
                $i = 0;
                foreach ($v as $d => $f) {
                    $arrPackages[$i][$k] = $f;
                    $i++;
                }
            }

            unset($v, $k, $i, $d, $f);


            $lbl = $this->setParams(
                $lbl, $params, $arrPackages /*multistore*/, $storeId /*multistore*/, $this->imOrder
            );
            $lbl->codOrderId = $orderId;

            if ($type == 'shipment' || $type == 'invert') {
                $upsl = $lbl->getShip(/*multistore*/
                    $storeId /*multistore*/);

                if (isset($params['default_return']) && $params['default_return'] == 1) {
                    $upslTwo = $lbl->getShipFrom(/*multistore*/
                        $storeId /*multistore*/);
                }
            } elseif ($type == 'refund') {
                $upsl = $lbl->getShipFrom(/*multistore*/
                    $storeId/*multistore*/);
            }

            $this->saveDB($upsl, $upslTwo, $params, $orderId, $shipmentId, $type, $lbl);
            if ($this->upsl !== null) {
                if ($this->upsl->getStatus() == 0) {
                    Mage::register('upsl', $this->upsl->getData());
                    if (isset($params['default_return']) && $params['default_return'] == 1) {
                        if ($this->upsl2->getStatus() == 0) {
                            Mage::register('upsl2', $this->upsl2->getData());
                            Mage::register('error2', '');
                        } else {
                            Mage::register('error2', $this->upsl2->getStatustext());
                        }
                    }

                    Mage::register('order_id', $orderId);
                    Mage::register('shipment_id', $shipmentId);
                    Mage::register('backLink', $backLink);
                    Mage::register('type', $type);
                    Mage::register('storeId', $storeId);
                    Mage::register('error', '');
                } else {
                    Mage::register('error', $this->upsl->getStatustext());
                }
            } else {
                Mage::getSingleton('adminhtml/session')->addError('Settings are invalid');
            }
        } else {
            Mage::register('order_id', $orderId);
            Mage::register('shipment_id', $shipmentId);
            $collections = Mage::getModel('dhllabel/dhllabel');
            $collection = $collections->getCollection()->addFieldToFilter('order_id', $orderId)
                ->addFieldToFilter('type', $type);
            if ($shipmentId !== null) {
                $collection->addFieldToFilter('shipment_id', $shipmentId);
            }

            $upslData = $collection->getData();
            Mage::register('upsl', $upslData[0]);
            Mage::register('upsl2', isset($upslData[1]) ? $upslData[1] : null);
            Mage::register('backLink', $backLink);
            Mage::register('type', $type);
            Mage::register('storeId', $storeId);
            Mage::register('error', '');
            if (isset($upslData[1]['status']) && $upslData[1]['status'] == 1) {
                Mage::register('error2', $upslData[1]['statustext']);
            }
        }

        $this->renderLayout();
    }

    public function saveDB($upsl, $upslTwo = null, $params, $orderId, $shipmentId, $type, $lbl = null)
    {
        Mage::helper('dhllabel/help')->createMediaFolders();
        $path = Mage::getBaseDir('media') . '/dhllabel/label/';
        $this->imOrder = Mage::getModel('sales/order')->load($orderId);
        $storeId = null;
        /*multistore*/
        $storeId = $this->imOrder->getStoreId();
        /*multistore*/
        $dhllabel = Mage::getModel('dhllabel/dhllabel');
        $collsTwo = $dhllabel->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('type', $type)
            ->addFieldToFilter('status', 1);

        if (!empty($collsTwo)) {
            foreach ($collsTwo as $c) {
                $c->delete();
            }
        }

        $responseData = $upsl;
        $note = isset($responseData['Note']) ? (array)$responseData['Note'] : array();

        if (array_key_exists('ActionNote', $note) && $note['ActionNote'] === 'Success') {
            $trackingnumber = $responseData['AirwayBillNumber'];
            if ($shipmentId == 0 && $type == "shipment") {
                if ($this->imOrder->canShip()) {
                    $shipmentIdTemp = Mage::getModel('sales/order_shipment_api_v2')
                        ->create($this->imOrder->getIncrementId(), array(), null, false, false);
                    $shipmentId = Mage::getModel('sales/order_shipment')
                        ->load($shipmentIdTemp, 'increment_id')->getId();
                } else {
                    $shipment = $this->imOrder->getShipmentsCollection()->getFirstItem();
                    $shipmentId = $shipment->getId();
                }
            }

            $labelImage = (array)$responseData['LabelImage'];
            $outputType = strtolower($labelImage['OutputFormat']);
            $outputPDF = base64_decode($labelImage['OutputImage']);
            $copyLabels = Mage::getStoreConfig('dhllabel/additional_settings/multiple_pdf_label', $storeId);
            if ($copyLabels > 1) {
                $pdf = new Zend_Pdf();
                $pdfTwo = Zend_Pdf::parse($outputPDF);
                for ($iPdf = 0; $iPdf < $copyLabels; $iPdf++) {
                    foreach ($pdfTwo->pages as $k => $page) {
                        $templateTwo = clone $pdfTwo->pages[$k];
                        $pageTwo = new Zend_Pdf_Page($templateTwo);
                        $pdf->pages[] = $pageTwo;
                    }
                }

                $outputPDF = $pdf->render();
            }

            if (file_put_contents($path . 'label_' . $trackingnumber . '.' . $outputType, $outputPDF)) {
                $dhllabel = Mage::getModel('dhllabel/dhllabel');
                $dhllabel->setTitle('Order ' . $orderId . ' TN' . $trackingnumber);
                $dhllabel->setOrderId($orderId);
                $dhllabel->setShipmentId($shipmentId);
                $dhllabel->setType($type);
                $dhllabel->setType2($type);
                $dhllabel->setTrackingnumber($trackingnumber);
                $dhllabel->setLabelname('label_' . $trackingnumber . '.' . $outputType);
                $dhllabel->setTypePrint($outputType);
                $dhllabel->setStatustext(Mage::helper('adminhtml')->__($note['ActionNote']));
                $dhllabel->setStatus(0);
                $dhllabel->setCreatedTime(Date("Y-m-d H:i:s"));
                $dhllabel->setUpdateTime(Date("Y-m-d H:i:s"));
                if ($dhllabel->save()) {
                    if ($type == "shipment"
                        && isset($params['invoice_declared_value'])
                        && Mage::getStoreConfig('dhllabel/paperless/create_pdf', $storeId) == 1
                    ) {
                        $path = Mage::getBaseDir('media') . '/dhllabel/label/';
                        file_put_contents(
                            $path . 'invoice_' . $trackingnumber . '.pdf',
                            $this->createInvoicePdf($params, $trackingnumber, $lbl, $storeId)
                        );
                    }
                }

                $this->upsl = $dhllabel;
                $this->labelDirectIds[] = $dhllabel->getId();
            } else {
                Mage::getSingleton('adminhtml/session')->addError('Could not save file');
            }

            $shippingMethods = json_decode($params['shipping_methods'], true);
            $dhllabel = Mage::getModel('dhllabel/labelprice');
            $dhllabel->setOrderId($orderId);
            $dhllabel->setShipmentId($shipmentId);
            if (isset($shippingMethods['price'][$params['serviceGlobalCode']])) {
                $dhllabel->setPrice(
                    round($shippingMethods['price'][$params['serviceGlobalCode']], 2) .
                    ((isset($shippingMethods['remote'][$params['serviceGlobalCode']])
                        && $shippingMethods['remote'][$params['serviceGlobalCode']] == 1) ? " (remote)" : "")
                );
            } else {
                $dhllabel->setPrice("");
            }

            if ($dhllabel->save() && Mage::getStoreConfig('dhllabel/printing/automatic_printing', $storeId) == 1) {
                Mage::helper('dhllabel/help')->sendPrint($outputPDF, $storeId);
            }

            if (isset($params['addtrack']) && $params['addtrack'] == 1 && $type == 'shipment') {
                $trTitle = 'DHL';
                $shipment = Mage::getModel('dhllabel/shipment')->load($shipmentId);
                $track = Mage::getModel('sales/order_shipment_track')
                    ->setNumber(trim($trackingnumber))
                    ->setCarrierCode('dhlint')
                    ->setTitle($trTitle);
                $shipment->addTrack($track);
                if (Mage::getStoreConfig('dhllabel/shipping/track_send', $storeId) == 1) {
                    $shipment->sendEmail(true, '');
                    $shipment->setEmailSent(true);
                }

                $shipment->save();
            }

            if (isset($params['default_return']) && $params['default_return'] == 1
                && isset($upslTwo) && !empty($upslTwo)) {
                $responseData = $upslTwo;
                if (isset($responseData['Note'])) {
                    $note = isset($responseData['Note']) ? (array)$responseData['Note'] : array();
                    if (isset($responseData['AirwayBillNumber'])) {
                        $trackingnumber = $responseData['AirwayBillNumber'];
                        if (array_key_exists('ActionNote', $note) && $note['ActionNote'] === 'Success') {
                            $labelImage = (array)$responseData['LabelImage'];
                            $outputPDF = base64_decode($labelImage['OutputImage']);
                            $copyLabels = Mage::getStoreConfig(
                                'dhllabel/additional_settings/multiple_pdf_label',
                                $storeId
                            );
                            if ($copyLabels > 1) {
                                $pdf = new Zend_Pdf();
                                $pdfTwo = Zend_Pdf::parse($outputPDF);
                                for ($iPdf = 0; $iPdf < $copyLabels; $iPdf++) {
                                    foreach ($pdfTwo->pages as $k => $page) {
                                        $templateTwo = clone $pdfTwo->pages[$k];
                                        $pageTwo = new Zend_Pdf_Page($templateTwo);
                                        $pdf->pages[] = $pageTwo;
                                    }
                                }

                                $outputPDF = $pdf->render();
                            }

                            if (file_put_contents($path . 'label_' . $trackingnumber . '.pdf', $outputPDF)) {
                                $dhllabel = Mage::getModel('dhllabel/dhllabel');
                                $dhllabel->setTitle('Order ' . $orderId . ' TN' . $trackingnumber);
                                $dhllabel->setOrderId($orderId);
                                $dhllabel->setShipmentId($shipmentId);
                                $dhllabel->setType($type);
                                $dhllabel->setType2('refund');
                                $dhllabel->setTrackingnumber($trackingnumber);
                                $dhllabel->setLabelname('label_' . $trackingnumber . '.pdf');
                                $dhllabel->setTypePrint('pdf');
                                $dhllabel->setStatustext(Mage::helper('adminhtml')->__($note['ActionNote']));
                                $dhllabel->setStatus(0);
                                $dhllabel->setCreatedTime(Date("Y-m-d H:i:s"));
                                $dhllabel->setUpdateTime(Date("Y-m-d H:i:s"));
                                if ($dhllabel->save()
                                    && Mage::getStoreConfig('dhllabel/printing/automatic_printing', $storeId) == 1
                                ) {
                                    Mage::helper('dhllabel/help')->sendPrint($outputPDF, $storeId);
                                }

                                $this->upsl2 = $dhllabel;
                                $this->labelDirectIds[] = $dhllabel->getId();

                                if (isset($params['addtrack']) && $params['addtrack'] == 1 && $type == 'shipment') {
                                    $trTitle = 'DHL (return)';
                                    $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
                                    $track = Mage::getModel('sales/order_shipment_track')
                                        ->setNumber(trim($trackingnumber))
                                        ->setCarrierCode('dhlint')
                                        ->setTitle($trTitle);
                                    $shipment->addTrack($track);
                                    $shipment->save();
                                }
                            }
                        }
                    }
                } else {
                    if (isset($responseData['Response'])) {
                        $error = (array)$responseData['Response'];
                        $error = (array)$error['Status'];
                        $error = $error['Condition'];
                        $errordescArr = '';
                        $error = (array)$error;
                        if (!isset($error['ConditionData'])) {
                            foreach ($error as $err) {
                                $errordesc = (array)$err;
                                if (isset($errordesc['ConditionData'])) {
                                    $errordescArr .= $errordesc['ConditionData'] . '; ';
                                } elseif (isset($errordesc['ConditionCode'])) {
                                    $errordescArr .= $errordesc['ConditionCode'] . '; ';
                                }
                            }
                        } else {
                            $errordescArr .= $error['ConditionData'] . '; ';
                        }
                    } else {
                        $errordescArr = 'Unknown error';
                    }

                    $dhllabel = Mage::getModel('dhllabel/dhllabel');
                    $dhllabel->setTitle('Order ' . $orderId);
                    $dhllabel->setOrderId($orderId);
                    $dhllabel->setShipmentId($shipmentId);
                    $dhllabel->setType($type);
                    $dhllabel->setType2('refund');
                    $dhllabel->setStatustext($errordescArr);
                    $dhllabel->setStatus(1);
                    $dhllabel->setCreatedTime(Date("Y-m-d H:i:s"));
                    $dhllabel->setUpdateTime(Date("Y-m-d H:i:s"));
                    $dhllabel->save();
                    $this->upsl2 = $dhllabel;
                }
            }

            if (Mage::getStoreConfig('dhllabel/additional_settings/orderstatuses', $storeId) != '') {
                $this->imOrder
                    ->setStatus(Mage::getStoreConfig('dhllabel/additional_settings/orderstatuses', $storeId));
                $history = $this->imOrder->addStatusHistoryComment('DHL label created', false);
                $history->setIsCustomerNotified(false);
                $this->imOrder->save();
            }
        } else {
            $error = (array)$responseData['Response'];
            $error = (array)$error['Status'];
            $error = $error['Condition'];
            $errordescArr = '';
            $error = (array)$error;
            if (!isset($error['ConditionData'])) {
                foreach ($error as $err) {
                    $errordesc = (array)$err;
                    if (isset($errordesc['ConditionData'])) {
                        $errordescArr .= $errordesc['ConditionData'] . '; ';
                    } elseif (isset($errordesc['ConditionCode'])) {
                        $errordescArr .= $errordesc['ConditionCode'] . '; ';
                    }
                }
            } else {
                $errordescArr .= $error['ConditionData'] . '; ';
            }

            $dhllabel = Mage::getModel('dhllabel/dhllabel');
            $dhllabel->setTitle('Order ' . $orderId);
            $dhllabel->setOrderId($orderId);
            $dhllabel->setShipmentId($shipmentId);
            $dhllabel->setType($type);
            $dhllabel->setType2($type);
            $dhllabel->setStatustext($errordescArr);
            $dhllabel->setStatus(1);
            $dhllabel->setCreatedTime(Date("Y-m-d H:i:s"));
            $dhllabel->setUpdateTime(Date("Y-m-d H:i:s"));
            $dhllabel->save();
            $this->upsl = $dhllabel;
        }

    }

    public function setParams($lbl, $params, $packages /*multistore*/, $storeId = null /*multistore*/, $order)
    {
        $configOptions = new Infomodus_Dhllabel_Model_Config_Options;
        $lbl->packages = $packages;
        $lbl->shipmentDescription = Infomodus_Dhllabel_Helper_Help::escapeXML($params['shipmentdescription']);

        $lbl->shipperId = Infomodus_Dhllabel_Helper_Help::escapeXML(
            Mage::getStoreConfig('dhllabel/address_' . $params['shipper_no'] . '/shipperid', $storeId)
        );
        $lbl->shipperName = Infomodus_Dhllabel_Helper_Help::escapeXML(
            Mage::getStoreConfig('dhllabel/address_' . $params['shipper_no'] . '/companyname', $storeId)
        );
        $lbl->shipperAttentionName = Infomodus_Dhllabel_Helper_Help::escapeXML(
            Mage::getStoreConfig('dhllabel/address_' . $params['shipper_no'] . '/attentionname', $storeId)
        );
        $lbl->shipperPhoneNumber = Infomodus_Dhllabel_Helper_Help::escapeXML(
            Mage::getStoreConfig('dhllabel/address_' . $params['shipper_no'] . '/phonenumber', $storeId)
        );
        $lbl->shipperAddressLine1 = Infomodus_Dhllabel_Helper_Help::escapeXML(
            Mage::getStoreConfig('dhllabel/address_' . $params['shipper_no'] . '/addressline1', $storeId)
        );
        $lbl->shipperAddressLine2 = Infomodus_Dhllabel_Helper_Help::escapeXML(
            Mage::getStoreConfig('dhllabel/address_' . $params['shipper_no'] . '/addressline2', $storeId)
        );
        $lbl->shipperAddressLine3 = Infomodus_Dhllabel_Helper_Help::escapeXML(
            Mage::getStoreConfig('dhllabel/address_' . $params['shipper_no'] . '/addressline3', $storeId)
        );
        $lbl->shipperCity = Infomodus_Dhllabel_Helper_Help::escapeXML(
            Mage::getStoreConfig('dhllabel/address_' . $params['shipper_no'] . '/city', $storeId)
        );
        $lbl->shipperStateProvinceCode = Infomodus_Dhllabel_Helper_Help::escapeXML(
            Mage::getStoreConfig('dhllabel/address_' . $params['shipper_no'] . '/stateprovincecode', $storeId)
        );
        $lbl->shipperPostalCode = Infomodus_Dhllabel_Helper_Help::escapeXML(
            Mage::getStoreConfig('dhllabel/address_' . $params['shipper_no'] . '/postalcode', $storeId)
        );
        $lbl->shipperCountryCode = Infomodus_Dhllabel_Helper_Help::escapeXML(
            Mage::getStoreConfig('dhllabel/address_' . $params['shipper_no'] . '/countrycode', $storeId)
        );
        $lbl->shipperStateProvinceName = Mage::getModel('directory/region')
            ->loadByCode($lbl->shipperStateProvinceCode, $lbl->shipperCountryCode)->getName();
        $lbl->shipperCountryName = Mage::getStoreConfig(
            'dhllabel/address_' . $params['shipper_no'] . '/countrycode', $storeId
        ) ? Infomodus_Dhllabel_Helper_Help::escapeXML(
            Mage::getModel('directory/country')
                ->loadByCode(
                    Mage::getStoreConfig(
                        'dhllabel/address_' . $params['shipper_no'] . '/countrycode',
                        $storeId
                    )
                )->getName()
        ) : "";

        $lbl->shiptoCompanyName = Infomodus_Dhllabel_Helper_Help::escapeXML($params['shiptocompanyname']);
        $lbl->shiptoAttentionName = Infomodus_Dhllabel_Helper_Help::escapeXML($params['shiptoattentionname']);
        $lbl->shiptoPhoneNumber = Infomodus_Dhllabel_Helper_Help::escapeXML($params['shiptophonenumber']);
        $lbl->shiptoAddressLine1 = trim(Infomodus_Dhllabel_Helper_Help::escapeXML($params['shiptoaddressline1']));
        $lbl->shiptoAddressLine2 = trim(Infomodus_Dhllabel_Helper_Help::escapeXML($params['shiptoaddressline2']));
        $lbl->shiptoAddressLine3 = trim(Infomodus_Dhllabel_Helper_Help::escapeXML($params['shiptoaddressline3']));
        $lbl->shiptoCity = Infomodus_Dhllabel_Helper_Help::escapeXML($params['shiptocity']);
        $lbl->shiptoStateProvinceName = Infomodus_Dhllabel_Helper_Help::escapeXML($params['shiptostateprovincecode']);
        $lbl->shiptoStateProvinceCode = Infomodus_Dhllabel_Helper_Help::escapeXML(
            $configOptions->getProvinceCode($params['shiptostateprovincecode'])
        );
        $lbl->shiptoCountryCode = Infomodus_Dhllabel_Helper_Help::escapeXML($params['shiptocountrycode']);
        $lbl->shiptoCountryName = Infomodus_Dhllabel_Helper_Help::escapeXML(
            Mage::getModel('directory/country')->loadByCode($params['shiptocountrycode'])->getName()
        );
        $lbl->shiptoPostalCode = Infomodus_Dhllabel_Helper_Help::escapeXML($params['shiptopostalcode']);
        $declaredValue = explode('.', (string)round($params['declared_value'], 2));
        if (count($declaredValue) > 1 && strlen($declaredValue[1]) == 1) {
            $declaredValue = round($params['declared_value'], 2) . '0';
        } else {
            $declaredValue = round($params['declared_value'], 2);
        }

        $lbl->declaredValue = $declaredValue;

        $globalCode = array_key_exists('serviceGlobalCode', $params) ? $params['serviceGlobalCode'] : '';
        $localCode = null;
        if (isset($params['shipping_methods'])) {
            $codes = json_decode($params['shipping_methods'], true);
            if (isset($codes['global']) && is_array($codes['global']) && !empty($codes['global'])) {
                foreach ($codes['global'] as $key => $shCode) {
                    if ($shCode == $globalCode) {
                        $localCode = $codes['local'][$key];
                    }
                }
            } else {
                Mage::getSingleton('adminhtml/session')
                    ->addSuccess(
                        Mage::helper('dhllabel')
                            ->__('Sorry, no Recommended shipping methods are available for this destination')
                    );
            }
        }

        $lbl->serviceGlobalCode = $globalCode;
        $lbl->serviceCode = $localCode;
        if (empty($lbl->serviceCode)) {
            $lbl->serviceCode = $lbl->serviceGlobalCode;
        }

        $globalCode = array_key_exists('default_return_servicecode', $params) ?
            $params['default_return_servicecode'] : '';
        if ($globalCode && Mage::app()->getRequest()->getParam('type') == 'shipment') {
            $localCode = null;
            if (isset($params['return_methods'])) {
                $codes = json_decode($params['return_methods'], true);
                if (isset($codes['global']) && is_array($codes['global']) && !empty($codes['global'])) {
                    foreach ($codes['global'] as $key => $shCode) {
                        if ($shCode == $globalCode) {
                            $localCode = $codes['local'][$key];
                        }
                    }
                } else {
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('dhllabel')
                            ->__(
                                'Return label: Sorry, no Recommended shipping methods 
                                are available for this destination'
                            )
                    );
                }
            }

            $lbl->serviceGlobalCodeReturn = $globalCode;
            $lbl->serviceCodeReturn = $localCode;
        } else {
            $lbl->serviceGlobalCodeReturn = $lbl->serviceGlobalCode;
            $lbl->serviceCodeReturn = $lbl->serviceCode;
        }

        if (empty($lbl->serviceCodeReturn)) {
            $lbl->serviceCodeReturn = $lbl->serviceGlobalCodeReturn;
        }

        $lbl->weightUnits = array_key_exists('weightunits', $params) ? $params['weightunits'] : '';

        $lbl->includeDimensions = array_key_exists('includedimensions', $params) ? $params['includedimensions'] : 0;
        $lbl->unitOfMeasurement = array_key_exists('unitofmeasurement', $params) ? $params['unitofmeasurement'] : '';

        $lbl->codYesNo = array_key_exists('cod', $params) ? $params['cod'] : '';
        $lbl->currencyCode = array_key_exists('currencycode', $params) ? $params['currencycode'] : '';
        $lbl->codMonetaryValue = array_key_exists('codmonetaryvalue', $params) ? $params['codmonetaryvalue'] : '';
        $lbl->doorto = array_key_exists('doorto', $params) ? $params['doorto'] : '';
        $lbl->ReferenceId = array_key_exists('reference_id', $params) ? $params['reference_id'] : '';
        if ($params['upsaccount'] != "S" && $params['upsaccount'] != "R") {
            $lbl->upsAccount = 1;
            $lbl->accountData = Mage::getModel('dhllabel/account')->load($params['upsaccount'])->getAccountnumber();
        } else {
            $lbl->upsAccount = 0;
            $lbl->accountData = $params['upsaccount'];
        }

        if (isset($params['duty_payment_type'])) {
            $lbl->dutyPaymentType = $params['duty_payment_type'];
            $lbl->accountDataDuty = Mage::getModel('dhllabel/account')
                ->load($params['duty_payment_type'])
                ->getAccountnumber();
        }

        $lbl->testing = $params['testing'];

        $lbl->qvn_email_shipto = $params['qvn_email_shipto'];
        $lbl->qvn_email_shipper = Mage::getStoreConfig('dhllabel/quantum/qvn_email_shipper', $storeId);
        if (array_key_exists('qvn', $params) && $params['qvn'] > 0) {
            $lbl->qvn = 1;
            $lbl->qvn_email_message = isset($params['qvn_message_shipto']) ? Infomodus_Dhllabel_Helper_Help::escapeXML($params['qvn_message_shipto']) : "";
        }

        $lbl->packageType = isset($params['packagingtypecode']) ? Infomodus_Dhllabel_Helper_Help::escapeXML($params['packagingtypecode']) : "";

        $lbl->print_type = Mage::getStoreConfig('dhllabel/printing/printer', $storeId);
        $lbl->print_type_format = Mage::getStoreConfig('dhllabel/printing/printer_format', $storeId);
        if (strpos($lbl->print_type, "PDF") !== false) {
            $lbl->print_type_format = "PDF";
        }

        $lbl->requestArchiveDoc = "N";
        if (Mage::getStoreConfig('dhllabel/printing/archive', $storeId) == 1) {
            $lbl->requestArchiveDoc = "Y";
        }

        $lbl->invoiceProducts = $params['invoice_product'];
        $lbl->orderIncrementId = $order->getIncrementId();
        $lbl->invoicePdf = $this->createInvoicePdf($params, '', $lbl, $storeId);
        return $lbl;
    }

    public function intermediateAction()
    {

        $this->loadLayout();
        $orderId = $this->getRequest()->getParam('order_id');
        $type = $this->getRequest()->getParam('type');
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        $this->intermediatehandy(
            $orderId,
            $type,
            $shipmentId,
            $this->getRequest()->getParam('shipperaddress', null)
        );

        Mage::register('servicecodes', json_decode($this->defConfParams['shipping_methods'], true));
        Mage::register('servicecodesreturn', json_decode($this->defConfParams['return_methods'], true));
        Mage::register('order', $this->imOrder);
        Mage::register('shippingAmount', $this->shippingAmount['shipping_amount']);
        Mage::register('shipment', $this->imShipment);
        Mage::register('type', $type);
        Mage::register('dhlAccounts', $this->upsAccounts);
        Mage::register('dhlAccountsDuty', $this->dhlAccountsDuty);
        Mage::register('shipmentTotalPrice', $this->shipmentTotalPrice);
        Mage::register('shipmentTotalWeight', $this->totalWeight);
        Mage::register('shipByUps', $this->shipByUps);
        Mage::register('shipByUpsCode', $this->shipByUpsCode);
        Mage::register('shipTo', $this->shippingAddress);
        Mage::register('shipByUpsMethodName', $this->shipByUpsMethodName);
        Mage::register('shipByGlobalMethodName', $this->shipByGlobalMethodName);
        Mage::register('shipByUpsMethods', $this->configMethod->getUpsMethods());
        Mage::register('shipByGlobalMethods', $this->configMethod->getUpsMethods());
        Mage::register('unitofmeasurement', $this->configOptions->getUnitOfMeasurement());
        $paymentCode = "";
        if (is_object($this->imOrder->getPayment())) {
            $paymentCode = $this->imOrder->getPayment()->getMethodInstance()->getCode();
        }
        Mage::register('paymentmethod', $paymentCode);
        Mage::register('sku', $this->sku);
        Mage::register('notificationMessage', $this->notificationMessage);
        Mage::register('defParams', $this->defParams);
        Mage::register('defConfParams', $this->defConfParams);

        $this->renderLayout();
    }

    public function intermediatehandy($orderId, $type, $shipmentId = null, $shipperAddress = null)
    {
        $this->configOptions = new Infomodus_Dhllabel_Model_Config_Options;
        $this->configMethod = new Infomodus_Dhllabel_Model_Config_Dhlmethod;
        $this->defConfParams = array();

        $this->imOrder = Mage::getModel('sales/order')->load($orderId);
        $storeId = null;
        /*multistore*/
        $storeId = $this->imOrder->getStoreId();
        /*multistore*/
        $this->shippingAddress = $this->imOrder->getShippingAddress();

        $totalPrice = 0;
        $this->totalWeight = 0;
        $totalShipmentQty = 0;
        $this->sku = array();
        if ($shipmentId !== null) {
            if ($type == 'shipment') {
                $this->imShipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
                if ($this->imShipment) {
                    $this->shippingAmount = $this->imShipment->getShippingAddress()->getOrder()->getData();
                }
            } else {
                $this->imShipment = Mage::getModel('sales/order_creditmemo')->load($shipmentId);
                if ($this->imShipment) {
                    $this->shippingAmount = $this->imShipment->getShippingAddress()->getOrder()->getData();
                }
            }

            $shipmentAllItems = $this->imShipment->getAllItems();
        } else {
            $this->shippingAmount = $this->imOrder->getData();
            $shipmentAllItems = $this->imOrder->getAllVisibleItems();
        }

        $totalQty = 0;
        $shipmentDescription = array();
        $itemDeclaredTotalPrice = 0;
        foreach ($shipmentAllItems as $item) {
            if ($item->getOrderItemId()) {
                $item = $this->imOrder->getItemById($item->getOrderItemId());
            }

            if (!$item->isDeleted() && !$item->getParentItemId()) {
                $itemData = $item->getData();
                $this->sku[] = $itemData['sku'];
                if (!isset($itemData['qty'])) {
                    $itemData['qty'] = $itemData['qty_ordered'];
                }

                $originProductObject = Mage::getModel('catalog/product')->load($itemData['product_id']);
                $originProduct = $originProductObject->getData();

                $itemPrice = $item->getPrice() - $item->getDiscountAmount()/$itemData['qty'];

                $totalPrice += $itemPrice * $itemData['qty'];
                $this->totalWeight += $itemData['weight'] * $itemData['qty'];
                $totalShipmentQty += $itemData['qty'];
                $totalQty += $itemData['qty'];
                if (Mage::getStoreConfig('dhllabel/shipping/shipmentdescription', $storeId) == 4) {
                    $shipmentDescription[] = $itemData['name'];
                }

                $itemName = isset($originProduct['declared_name'])
                && strlen(trim($originProduct['declared_name'])) > 0
                    ? $originProduct['declared_name'] : $itemData['name'];
                $itemDeclaredTotalPrice += $itemPrice * $itemData['qty'];

                $commoditycode = (
                    strlen(Mage::getStoreConfig('dhllabel/paperless/commodity_attribute', $storeId)) > 0
                    && isset(
                        $originProduct[Mage::getStoreConfig(
                            'dhllabel/paperless/commodity_attribute',
                            $storeId
                        )]
                    )
                ) ? $originProduct[Mage::getStoreConfig('dhllabel/paperless/commodity_attribute', $storeId)] : '';

                $commoditytype = (
                    strlen(Mage::getStoreConfig('dhllabel/paperless/commodityType', $storeId)) > 0
                    && isset(
                        $originProduct[Mage::getStoreConfig(
                            'dhllabel/paperless/commodityType',
                            $storeId
                        )]
                    )
                ) ? $originProductObject->getAttributeText(
                    Mage::getStoreConfig('dhllabel/paperless/commodityType', $storeId)
                ) : '';

                $dgAttributeContentId = (strlen(Mage::getStoreConfig('dhllabel/shipping/dg_attribute_content_id', $storeId)) > 0 && isset($originProduct[Mage::getStoreConfig('dhllabel/shipping/dg_attribute_content_id', $storeId)]))
                    ? $originProduct[Mage::getStoreConfig('dhllabel/shipping/dg_attribute_content_id', $storeId)] : '';
                $dgAttributeLabel = (strlen(Mage::getStoreConfig('dhllabel/shipping/dg_attribute_label', $storeId)) > 0 && isset($originProduct[Mage::getStoreConfig('dhllabel/shipping/dg_attribute_label', $storeId)]))
                    ? $originProduct[Mage::getStoreConfig('dhllabel/shipping/dg_attribute_label', $storeId)] : '';
                $dgAttributeUNCode = (strlen(Mage::getStoreConfig('dhllabel/shipping/dg_attribute_uncode', $storeId)) > 0 && isset($originProduct[Mage::getStoreConfig('dhllabel/shipping/dg_attribute_uncode', $storeId)]))
                    ? $originProduct[Mage::getStoreConfig('dhllabel/shipping/dg_attribute_uncode', $storeId)] : '';

                $this->defConfParams['invoice_product'][] = array(
                    'enable' => 1,
                    'qty' => $itemData['qty'],
                    'name' => $itemName,
                    'weight' => $itemData['weight'],
                    'price' => $itemPrice,
                    'sku' => $itemData['sku'],
                    'id' => $itemData['product_id'],
                    'commodity_code' => $commoditycode,
                    'commodity_type' => $commoditytype,
                    'dangerous_goods' => Mage::getStoreConfig('dhllabel/shipping/dangerous_goods', $storeId),
                    'dg_attribute_content_id' => $dgAttributeContentId,
                    'dg_attribute_label' => $dgAttributeLabel,
                    'dg_attribute_uncode' => $dgAttributeUNCode,
                );
            }
        }

        $this->sku = implode(",", $this->sku);
        $this->shipmentTotalPrice = isset($this->shippingAmount['grand_total'])
            ? $this->shippingAmount['grand_total'] : 0;


        $this->upsAccounts = Mage::getModel('dhllabel/config_methodofpayment')->toOptionArray();
        $this->dhlAccountsDuty = Mage::getModel('dhllabel/config_methodofpaymentduty')->toOptionArray();

        $shipMethod = $this->imOrder->getShippingMethod();
        $this->shipByUps = preg_replace("/^dhlint_.{1,4}$/", 'dhlint', $shipMethod);
        $this->shipByUpsCode = preg_replace("/^dhlint_(.{1,4})$/", '$1', $shipMethod);
        $this->shipByUpsMethodName = $this->configMethod->getUpsMethodName($this->shipByUpsCode);
        $this->shipByGlobalMethodName = $this->configMethod->getUpsMethodName($this->shipByUpsCode);

        $this->notificationMessage = Mage::getStoreConfig('dhllabel/quantum/qvn_message', $storeId);

        if ($this->totalWeight <= 0) {
            $this->totalWeight = (float)str_replace(
                ',',
                '.',
                Mage::getStoreConfig('dhllabel/weightdimension/defweigth', $storeId)
            );
            if($this->totalWeight == '' || $this->totalWeight <= 0){
                Mage::getSingleton('adminhtml/session')->addError("Some of the products are missing their weight information. Please fill the weight for all products or enter a default value from the \"Weight and Dimensions\" section of the UPS module configuration.");
            }
        }

        $attributeCodeWidth = Mage::getStoreConfig('dhllabel/weightdimension/attribute_code_width', $storeId) ?
            Mage::getStoreConfig('dhllabel/weightdimension/attribute_code_width', $storeId) : 'width';
        $attributeCodeHeight = Mage::getStoreConfig('dhllabel/weightdimension/attribute_code_height', $storeId) ?
            Mage::getStoreConfig('dhllabel/weightdimension/attribute_code_height', $storeId) : 'height';
        $attributeCodeLength = Mage::getStoreConfig('dhllabel/weightdimension/attribute_code_length', $storeId) ?
            Mage::getStoreConfig('dhllabel/weightdimension/attribute_code_length', $storeId) : 'length';

        $dimensionSets = Mage::getModel("dhllabel/config_defaultdimensionsset")
            ->toOptionArray(/*multistore*/
                $storeId /*multistore*/);

        if (Mage::getStoreConfig('dhllabel/packaging/frontend_multipackes_enable', $storeId) == 1) {
            $i = 0;
            $defParArrOne = array();
            foreach ($shipmentAllItems as $item) {
                if ($item->getOrderItemId()) {
                    $item = $this->imOrder->getItemById($item->getOrderItemId());
                }

                if (!$item->isDeleted() && !$item->getParentItemId()) {
                    $itemData = $item->getData();
                    if (!isset($itemData['qty'])) {
                        $itemData['qty'] = $itemData['qty_ordered'];
                    }

                    $myproduct = Mage::getModel('catalog/product')->load($itemData['product_id'])->getData();
                    if (!isset($myproduct['weight']) || $myproduct['weight'] == null) {
                        $defParArrOne = array();
                        Mage::getSingleton('adminhtml/session')->addError("Product " . $myproduct['name'] . " does not have weight set");
                        break;
                    }

                    $packageByAttributeCode = isset(
                        $myproduct[Mage::getStoreConfig('dhllabel/packaging/packages_by_attribute_code', $storeId)]
                    );
                    $attribute = array();
                    if ($packageByAttributeCode) {
                        $attribute = explode(";", trim($myproduct[Mage::getStoreConfig('dhllabel/packaging/packages_by_attribute_code', $storeId)], ";"));
                    }

                    $countAttribute = 0;
                    if (Mage::getStoreConfig('dhllabel/packaging/packages_by_attribute_enable', $storeId) == 1) {
                        $countAttribute = count($attribute);
                    }

                    for ($ik = 0; $ik < $itemData['qty']; $ik++) {
                        if ($countAttribute > 1) {
                            $rvaPrice = $itemData['price'];
                            foreach ($attribute as $v) {
                                $itemData['weight'] = $v;
                                $itemData['price'] = round($rvaPrice / $countAttribute, 2);
                                $defParArrOne[$i] = $this->setDefParams(
                                    $itemData /*multistore*/, $storeId /*multistore*/, $type
                                );
                                $i++;
                            }
                        } else {
                            $countProductInBox = 0;
                            if (!empty($dimensionSets)) {
                                $packer = new Infomodus_Dhllabel_Model_Packer_Packer();
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
                                    Mage::getSingleton('adminhtml/session')->addWarning("Product " . $myproduct['name'] . " does not have width or height or length");
                                }

                                $packer->addItem(
                                    new Infomodus_Dhllabel_Model_Packer_TestItem(
                                        $itemData['price'],
                                        $myproduct[$attributeCodeWidth],
                                        $myproduct[$attributeCodeLength],
                                        $myproduct[$attributeCodeHeight],
                                        $myproduct['weight'],
                                        true
                                    )
                                );
                                if ($countProductInBox > 0) {
                                    foreach ($dimensionSets as $v) {
                                        if ($v['value'] !== 0) {
                                            $packer->addBox(new Infomodus_Dhllabel_Model_Packer_TestBox(
                                                $v['value'],
                                                Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/outer_width', $storeId),
                                                Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/outer_length', $storeId),
                                                Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/outer_height', $storeId),
                                                Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/emptyWeight', $storeId),
                                                Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/width', $storeId),
                                                Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/length', $storeId),
                                                Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/height', $storeId),
                                                Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/maxWeight', $storeId)
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

                                            $defParArrOne[$i] = $this->setDefParams(
                                                $itemDataTwo /*multistore*/, $storeId /*multistore*/, $type
                                            );
                                            $i++;
                                        }
                                    } else {
                                        $countProductInBox = 0;
                                    }
                                }
                            }

                            if ($countProductInBox == 0) {
                                $defParArrOne[$i] = $this->setDefParams(
                                    null /*multistore*/, $storeId /*multistore*/, $type
                                );
                                $i++;
                            }
                        }
                    }
                }
            }
            if (count($defParArrOne) == 0) {
                $defParArrOne[0] = $this->setDefParams(
                    null /*multistore*/, $storeId /*multistore*/, $type
                );
            }
            $this->defParams = $defParArrOne;
        } else {
            $this->defParams = array();
            $i = 0;
            $rvaShipmentTotalPriceStart = $this->shipmentTotalPrice;
            $rvaShipmentTotalPrice = $rvaShipmentTotalPriceStart;
            if (Mage::getStoreConfig('dhllabel/packaging/packages_by_attribute_enable', $storeId) == 1) {
                foreach ($shipmentAllItems as $item) {
                    if ($item->getOrderItemId()) {
                        $item = $this->imOrder->getItemById($item->getOrderItemId());
                    }

                    if (!$item->isDeleted() && !$item->getParentItemId()) {
                        $itemData = $item->getData();
                        if (!isset($itemData['qty'])) {
                            $itemData['qty'] = $itemData['qty_ordered'];
                        }

                        $itemData2 = $itemData;
                        $myproduct = Mage::getModel('catalog/product')->load($itemData['product_id']);
                        $myproduct = $myproduct->getData();
                        for ($ik = 0; $ik < $itemData['qty']; $ik++) {
                            if (isset($myproduct[Mage::getStoreConfig('dhllabel/packaging/packages_by_attribute_code', $storeId)])) {
                                $attribute = explode(";", trim($myproduct[Mage::getStoreConfig('dhllabel/packaging/packages_by_attribute_code', $storeId)], ";"));
                                if (count($attribute) > 1) {
                                    foreach ($attribute as $v) {
                                        $this->totalWeight = $this->totalWeight - $itemData2['weight'];
                                        $itemData['price'] = round($itemData2['price'] / count($attribute), 2);
                                        $itemData['weight'] = $v;
                                        $this->defParams[$i] = $this->setDefParams(
                                            $itemData /*multistore*/, $storeId /*multistore*/, $type
                                        );
                                        $i++;
                                    }

                                    $rvaShipmentTotalPrice = $rvaShipmentTotalPrice - $itemData2['price'];
                                }
                            }
                        }
                    }
                }
                $this->shipmentTotalPrice = $rvaShipmentTotalPrice;
            }
            if ($this->totalWeight > 0) {
                $countProductInBox = 0;
                if (count($dimensionSets) > 0) {
                    $packer = new Infomodus_Dhllabel_Model_Packer_Packer();
                    foreach ($shipmentAllItems as $item) {
                        if ($item->getOrderItemId()) {
                            $item = $this->imOrder->getItemById($item->getOrderItemId());
                        }

                        if (!$item->isDeleted() && !$item->getParentItemId()) {
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
                                Mage::getSingleton('adminhtml/session')
                                    ->addWarning(
                                        "Product " . $myproduct['name'] . " does not have width or height or length"
                                    );
                                break;
                            }

                            if (!isset($myproduct['weight']) || $myproduct['weight'] == null) {
                                $countProductInBox = 0;
                                Mage::getSingleton('adminhtml/session')->addError("Product " . $myproduct['name'] . " does not have weight set");
                                break;
                            }

                            for ($ik = 0; $ik < $itemData['qty']; $ik++) {
                                $packer->addItem(
                                    new Infomodus_Dhllabel_Model_Packer_TestItem(
                                        $itemData['price'],
                                        $myproduct[$attributeCodeWidth],
                                        $myproduct[$attributeCodeLength],
                                        $myproduct[$attributeCodeHeight],
                                        $myproduct['weight'],
                                        true
                                    )
                                );
                            }
                        }
                    }

                    if ($countProductInBox > 0) {
                        foreach ($dimensionSets as $v) {
                            if ($v['value'] !== 0) {
                                $packer->addBox(new Infomodus_Dhllabel_Model_Packer_TestBox(
                                    $v['value'],
                                    Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/outer_width', $storeId),
                                    Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/outer_length', $storeId),
                                    Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/outer_height', $storeId),
                                    Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/emptyWeight', $storeId),
                                    Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/width', $storeId),
                                    Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/length', $storeId),
                                    Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/height', $storeId),
                                    Mage::getStoreConfig('dhllabel/dimansion_' . $v['value'] . '/maxWeight', $storeId)
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

                                $this->defParams[$i] = $this->setDefParams($itemData /*multistore*/, $storeId /*multistore*/, $type);
                                $i++;
                            }
                        } else {
                            $countProductInBox = 0;
                        }
                    }
                }

                if ($countProductInBox == 0) {
                    $this->defParams[$i] = $this->setDefParams(null /*multistore*/, $storeId /*multistore*/, $type);
                }
            }
            $this->shipmentTotalPrice = $rvaShipmentTotalPriceStart;
        }
        $shippingInternational = $this->shippingAddress->getCountryId() == Mage::getStoreConfig('dhllabel/address_' . Mage::getStoreConfig('dhllabel/shipping/defaultshipper', $storeId) . '/countrycode', $storeId) ? 0 : 1;

        $modelConformity = Mage::getModel("dhllabel/conformity")->getCollection()->addFieldToFilter('method_id', $this->shipByUps)->addFieldToFilter('store_id', /*multistore*/
            $storeId ? $storeId : /*multistore*/
                1)/*->addFieldToFilter('international', $shippingInternational)*/
        ->getSelect()->where('CONCAT(",", country_ids, ",") LIKE "%,' . $this->shippingAddress->getCountryId() . ',%"')->query()->fetch();
        $shipMethod = explode("_", $shipMethod);
        if ($shipMethod[0] == 'dhlint') {
            $this->defConfParams['serviceCode'] = $this->shipByUpsCode;
            $this->defConfParams['serviceGlobalCode'] = $this->shipByUpsCode;
        } elseif ($shipMethod[0] == 'caship' && Mage::helper('core')->isModuleOutputEnabled("Infomodus_Caship")) {
            $caShip = Mage::getModel('caship/method')->load($shipMethod[1]);
            if ($caShip && ($caShip->getCompanyType() == 'dhl' || $caShip->getCompanyType() == 'dhlinfomodus')) {
                $this->shipByUps = 'dhlint';
                $this->shipByUpsCode = $caShip->getDhlmethodId();
                $this->defConfParams['serviceCode'] = $this->shipByUpsCode;
                $this->defConfParams['serviceGlobalCode'] = $this->shipByUpsCode;
            }
        } elseif (Mage::getStoreConfig('dhllabel/shipping/shipping_method_native', $storeId) == 1 && $modelConformity && count($modelConformity) > 0) {
            $this->defConfParams['serviceCode'] = $modelConformity["dhlmethod_id"];
            $this->defConfParams['serviceGlobalCode'] = $modelConformity["dhlmethod_id"];
        }

        if (!isset($this->defConfParams['serviceGlobalCode'])) {
            if ($shippingInternational) {
                $this->defConfParams['serviceCode'] = Mage::getStoreConfig('dhllabel/shipping/defaultshipmentmethodworld', $storeId);
                $this->defConfParams['serviceGlobalCode'] = Mage::getStoreConfig('dhllabel/shipping/defaultshipmentmethodworld', $storeId);
            } else {
                $this->defConfParams['serviceCode'] = Mage::getStoreConfig('dhllabel/shipping/defaultshipmentmethod', $storeId);
                $this->defConfParams['serviceGlobalCode'] = Mage::getStoreConfig('dhllabel/shipping/defaultshipmentmethod', $storeId);
            }
        }

        $this->defConfParams['upsaccount'] = Mage::getStoreConfig('dhllabel/ratepayment/methodofpayment', $storeId);

        if ($type !== 'refund') {
            $this->defConfParams['duty_payment_type'] = Mage::getStoreConfig('dhllabel/ratepayment/duty_method', $storeId);
        }

        $this->defConfParams['shipper_no'] = Mage::getStoreConfig('dhllabel/shipping/defaultshipper', $storeId);
        if ($shipperAddress !== null) {
            $this->defConfParams['shipper_no'] = $shipperAddress;
        }

        $this->defConfParams['testing'] = Mage::getStoreConfig('dhllabel/testmode/testing', $storeId);
        $this->defConfParams['addtrack'] = Mage::getStoreConfig('dhllabel/shipping/addtrack', $storeId);


        if (Mage::getStoreConfig('dhllabel/shipping/shipmentdescription', $storeId) == 4) {
            $shipmentDescription = implode(', ', $shipmentDescription);
        } elseif (Mage::getStoreConfig('dhllabel/shipping/shipmentdescription', $storeId) == 5) {
            $shipmentDescription = str_replace(array("#order_id#", "#customer_name#", "#total_qty#"), array($this->imOrder->getIncrementId(), $this->shippingAddress->getFirstname() . ' ' . $this->shippingAddress->getLastname(), $totalQty), Mage::getStoreConfig('dhllabel/shipping/shipmentdescription_custom', $storeId));
        } else {
            $shipmentDescription = Mage::getStoreConfig('dhllabel/shipping/shipmentdescription', $storeId) == 1 ? (Mage::helper('adminhtml')->__('Customer') . ': ' . $this->shippingAddress->getFirstname() . ' ' . $this->shippingAddress->getLastname() . ' ' . Mage::helper('adminhtml')->__('Order Id') . ': ' . $this->imOrder->getIncrementId()) : (Mage::getStoreConfig('dhllabel/shipping/shipmentdescription', $storeId) == 2 ? Mage::helper('adminhtml')->__('Customer') . ': ' . $this->shippingAddress->getFirstname() . ' ' . $this->shippingAddress->getLastname() : (Mage::getStoreConfig('dhllabel/shipping/shipmentdescription', $storeId) !== '' ? Mage::helper('adminhtml')->__('Order Id') . ': ' . $this->imOrder->getIncrementId() : ''));
        }

        $this->defConfParams['shipmentdescription'] = $shipmentDescription;

        $this->defConfParams['currencycode'] = Mage::getStoreConfig('dhllabel/ratepayment/currencycode', $storeId);
        $this->defConfParams['cod'] = Mage::getStoreConfig('dhllabel/ratepayment/cod', $storeId);
        $this->defConfParams['codmonetaryvalue'] = Mage::getStoreConfig('dhllabel/ratepayment/cod_shipping_cost', $storeId) == 0 ? $this->shipmentTotalPrice - $this->shippingAmount['shipping_amount'] : $this->shipmentTotalPrice;
        $this->defConfParams['default_return'] = (Mage::getStoreConfig('dhllabel/return/default_return', $storeId) == 0 || Mage::getStoreConfig('dhllabel/return/default_return_amount', $storeId) > $this->shipmentTotalPrice) ? 0 : 1;
        $this->defConfParams['default_return_servicecode'] = Mage::getStoreConfig('dhllabel/return/default_return_method', $storeId);
        $this->defConfParams['qvn'] = Mage::getStoreConfig('dhllabel/quantum/qvn', $storeId);
        $this->defConfParams['qvn_code'] = explode(",", Mage::getStoreConfig('dhllabel/quantum/qvn_code', $storeId));
        $this->defConfParams['qvn_email_shipper'] = Mage::getStoreConfig('dhllabel/quantum/qvn_email_shipper', $storeId);
        $this->defConfParams['adult'] = Mage::getStoreConfig('dhllabel/quantum/adult', $storeId);
        $this->defConfParams['weightunits'] = Mage::getStoreConfig('dhllabel/weightdimension/weightunits', $storeId);
        $this->defConfParams['includedimensions'] = Mage::getStoreConfig('dhllabel/weightdimension/includedimensions', $storeId);
        $this->defConfParams['unitofmeasurement'] = Mage::getStoreConfig('dhllabel/weightdimension/unitofmeasurement', $storeId);
        $this->defConfParams['doorto'] = Mage::getStoreConfig('dhllabel/shipping/doorto', $storeId);

        $this->defConfParams['shiptocompanyname'] = strlen($this->shippingAddress->getCompany()) > 0 ? $this->shippingAddress->getCompany() : $this->shippingAddress->getFirstname() . ' ' . $this->shippingAddress->getLastname();
        $this->defConfParams['shiptoattentionname'] = $this->shippingAddress->getFirstname() . ' ' . $this->shippingAddress->getLastname();
        $this->defConfParams['shiptophonenumber'] = Infomodus_Dhllabel_Helper_Help::escapePhone($this->shippingAddress->getTelephone());
        $addressLine1 = $this->shippingAddress->getStreet();
        $this->defConfParams['shiptoaddressline1'] = is_array($addressLine1) && array_key_exists(0, $addressLine1) ? $addressLine1[0] : $addressLine1;
        $this->defConfParams['shiptoaddressline2'] = is_array($addressLine1) && array_key_exists(1, $addressLine1) ? $addressLine1[1] : "";
        $this->defConfParams['shiptoaddressline3'] = is_array($addressLine1) && array_key_exists(2, $addressLine1) ? $addressLine1[2] : "";
        $this->defConfParams['shiptocity'] = $this->shippingAddress->getCity();
        $this->defConfParams['shiptostateprovincecode'] = $this->shippingAddress->getRegion();
        $this->defConfParams['shiptocountrycode'] = $this->shippingAddress->getCountryId();
        if ($this->defConfParams['shiptocountrycode'] == 'JP') {
            $this->defConfParams['shiptopostalcode'] = str_replace("-", "", $this->shippingAddress->getPostcode());
            $this->defConfParams['shiptopostalcode'] = substr($this->defConfParams['shiptopostalcode'], 0, 3) . '-' . substr($this->defConfParams['shiptopostalcode'], 3);
        } else {
            $this->defConfParams['shiptopostalcode'] = $this->shippingAddress->getPostcode();
        }

        $this->defConfParams['qvn_email_shipto'] = $this->shippingAddress->getEmail();
        $this->defConfParams['reference_id'] = Mage::getStoreConfig('dhllabel/shipping/reference_id', $storeId) == 'order' ? $this->imOrder->getIncrementId() : '';
        $this->defConfParams['declared_value'] = round($this->shipmentTotalPrice - $this->shippingAmount['shipping_amount'], 2);
        if ($this->defConfParams['shiptocountrycode'] != Mage::getStoreConfig('dhllabel/address_' . $this->defConfParams['shipper_no'] . '/countrycode', $storeId) && $itemDeclaredTotalPrice > 0) {
            $this->defConfParams['declared_value'] = $itemDeclaredTotalPrice;
        }

        $this->defConfParams['packagingtypecode'] = Mage::getStoreConfig('dhllabel/packaging/packagingtypecode', $storeId);

        if ($this->defConfParams['shiptocountrycode'] != Mage::getStoreConfig('dhllabel/address_' . $this->defConfParams['shipper_no'] . '/countrycode', $storeId)) {
            $this->defConfParams['invoice_declared_value'] = $totalPrice;
        }

        Mage::helper('dhllabel/help')->createMediaFolders();
        $lbl = Mage::getModel('dhllabel/dhl');
        $lbl = $this->setParams($lbl, $this->defConfParams, $this->defParams/*multistore*/, $storeId/*multistore*/, $this->imOrder);
        $codes = array('global' => array(), 'local' => array(), 'price' => array(), 'remote' => array());
        $prices = $lbl->getShipPrice(false, $storeId);
        $error_codes = array();
        if ($prices !== false && count($prices) > 0) {
            foreach ($prices as $k => $price) {
                if (!in_array($price->getProductGlobalCode(), $codes['global'])) {
                    $codes['global'][] = $price->getProductGlobalCode();
                    $codes['local'][] = $price->getProductLocalCode();
                    $codes['price'][$price->getProductGlobalCode()] = $price->getTotalAmount();
                    $codes['remote'][$price->getProductGlobalCode()] = $price->getQtdShpExChrg();
                }
            }
        } else if ($prices !== false) {
            $error_codes[] = $lbl->rateErrors;
        }

        $prices = $lbl->getShipPrice(true, $storeId);
        if ($prices !== false && count($prices) > 0) {
            foreach ($prices as $k => $price) {
                if (!in_array($price->getProductGlobalCode(), $codes['global'])) {
                    $codes['global'][] = $price->getProductGlobalCode();
                    $codes['local'][] = $price->getProductLocalCode();
                    $codes['price'][$price->getProductGlobalCode()] = $price->getTotalAmount();
                    $codes['remote'][$price->getProductGlobalCode()] = $price->getQtdShpExChrg();
                }
            }
        } else if ($prices !== false) {
            $error_codes[] = $lbl->rateErrors;
        }

        if ($this->defConfParams['shiptocountrycode'] != "IN"
            && Mage::getStoreConfig('dhllabel/address_' . $this->defConfParams['shipper_no'] . '/countrycode', $storeId) == 'IN'
            && (empty($codes['global'])
                || !in_array("P", $codes['global']))
        ) {
            $codes['global'][] = "P";
            $codes['local'][] = "P";
        }

        $this->defConfParams['shipping_methods'] = json_encode($codes);
        $this->defConfParams['error_shipping_methods'] = json_encode($error_codes);

        $codes_return = array('global' => array(), 'local' => array(), 'price' => array(), 'remote' => array());
        $prices = $lbl->getReturnPrice(false, $storeId);
        $error_codes = array();
        if ($prices !== false && count($prices) > 0) {
            foreach ($prices as $k => $price) {
                if (!in_array($price->getProductGlobalCode(), $codes_return['global'])) {
                    $codes_return['global'][] = $price->getProductGlobalCode();
                    $codes_return['local'][] = $price->getProductLocalCode();
                    $codes_return['price'][$price->getProductGlobalCode()] = $price->getTotalAmount();
                    $codes_return['remote'][$price->getProductGlobalCode()] = $price->getQtdShpExChrg();
                }
            }
        } else if ($prices !== false) {
            $error_codes[] = $lbl->rateErrors;
        }

        $prices = $lbl->getReturnPrice(true, $storeId);
        if ($prices !== false && count($prices) > 0) {
            foreach ($prices as $k => $price) {
                if (!in_array($price->getProductGlobalCode(), $codes_return['global'])) {
                    $codes_return['global'][] = $price->getProductGlobalCode();
                    $codes_return['local'][] = $price->getProductLocalCode();
                    $codes_return['price'][$price->getProductGlobalCode()] = $price->getTotalAmount();
                    $codes_return['remote'][$price->getProductGlobalCode()] = $price->getQtdShpExChrg();
                }
            }
        } else if ($prices !== false) {
            $error_codes[] = $lbl->rateErrors;
        }

        if ($this->defConfParams['shiptocountrycode'] != "IN"
            && Mage::getStoreConfig('dhllabel/address_' . $this->defConfParams['shipper_no'] . '/countrycode', $storeId) == 'IN'
            && (empty($codes_return['global'])
                || !in_array("P", $codes_return['global']))
        ) {
            $codes_return['global'][] = "P";
            $codes_return['local'][] = "P";
        }

        $this->defConfParams['return_methods'] = json_encode($codes_return);
        $this->defConfParams['error_return_methods'] = json_encode($error_codes);

    }


    public
    function setDefParams($itemData = null /*multistore*/, $storeId /*multistore*/, $type = "shipment")
    {
        $defParArr_1['weight'] = $itemData !== null ? $itemData['weight'] : $this->totalWeight;
        $defParArr_1['packweight'] = round((float)str_replace(',', '.', Mage::getStoreConfig('dhllabel/weightdimension/packweight', $storeId)), 3) > 0 ? round((float)str_replace(',', '.', Mage::getStoreConfig('dhllabel/weightdimension/packweight', $storeId)), 3) : '0';
        $defParArr_1['currencycode'] = Mage::getStoreConfig('dhllabel/ratepayment/currencycode', $storeId);
        $defParArr_1['width'] = $itemData !== null && isset($itemData['width']) ? $itemData['width'] : '';
        $defParArr_1['height'] = $itemData !== null && isset($itemData['height']) ? $itemData['height'] : '';
        $defParArr_1['length'] = $itemData !== null && isset($itemData['length']) ? $itemData['length'] : '';
        $defParArr_1['depth'] = $itemData !== null && isset($itemData['length']) ? $itemData['length'] : '';
        return ($defParArr_1);
    }

    public
    function deletelabelAction()
    {
        $order_id = $this->getRequest()->getParam('order_id');
        $shipment_id = $this->getRequest()->getParam('shipment_id');
        $dhllabel = Mage::getModel('dhllabel/labelprice')->getCollection()->addFieldToFilter('order_id', $order_id)->addFieldToFilter('shipment_id', $shipment_id);
        if (count($dhllabel) > 0) {
            foreach ($dhllabel as $c) {
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
        $shipment_id = $this->getRequest()->getParam('shipment_id', null);
        $label_id = $this->getRequest()->getParam('label_id', null);
        $type = $this->getRequest()->getParam('type');
        $path = Mage::getBaseDir('media') . DS . 'dhllabel' . DS . 'label' . DS;

        $storeId = null;
        /*multistore*/
        $this->imOrder = Mage::getModel('sales/order')->load($order_id);
        $storeId = $this->imOrder->getStoreId();
        /*multistore*/

        $upslabel = Mage::getModel('dhllabel/dhllabel');
        if ($label_id === null) {
            $colls2 = $upslabel->getCollection()->addFieldToFilter('order_id', $order_id);
            if ($shipment_id !== null) {
                $colls2->addFieldToFilter('shipment_id', $shipment_id);
            }

            $colls2->addFieldToFilter('type', $type)->addFieldToFilter('status', 0);
            foreach ($colls2 as $coll) {
                if (file_exists($path . ($coll->getLabelname()))) {
                    if ($data = file_get_contents($path . ($coll->getLabelname()))) {
                        Mage::helper('dhllabel/help')->sendPrint($data, $storeId);
                        /*$coll->setRvaPrinted(1)->save();*/
                    }
                }
            }

            return Mage::helper('dhllabel')->__('Label was sent to print');
        } elseif ($label_id !== null) {
            $label = $upslabel->load($label_id);
            if (file_exists($path . ($label->getLabelname()))) {
                if ($data = file_get_contents($path . ($label->getLabelname()))) {
                    Mage::helper('dhllabel/help')->sendPrint($data, $storeId);
                    /*$label->setRvaPrinted(1)->save();*/
                }
            }

            return Mage::helper('dhllabel')->__('Label was sent to print');
        }
    }

    public
    function downloadnotgifAction()
    {
        $order_id = $this->getRequest()->getParam('order_id');
        $shipment_id = $this->getRequest()->getParam('shipment_id');
        $label_id = $this->getRequest()->getParam('label_id');
        $type = $this->getRequest()->getParam('type');
        $path = Mage::getBaseDir('media') . DS . 'dhllabel' . DS . 'label' . DS;

        $upslabel = Mage::getModel('dhllabel/dhllabel');
        if (!isset($label_id) || empty($label_id) || $label_id <= 0) {
            $colls2 = $upslabel->getCollection()->addFieldToFilter('order_id', $order_id)->addFieldToFilter('shipment_id', $shipment_id)->addFieldToFilter('type', $type)->addFieldToFilter('status', 0);
            if (extension_loaded('zip')) {
                $zip = new ZipArchive();
                $zip_name = sys_get_temp_dir() . DS . 'order' . $order_id . 'shipment' . $shipment_id . '.zip';
                if ($zip->open($zip_name, ZipArchive::CREATE) !== true) {
                    foreach ($colls2 as $coll) {
                        if (file_exists($path . ($coll->getLabelname())) && $coll->getTypePrint() != 'GIF') {
                            $zip->addFile($path . $coll->getLabelname(), $coll->getLabelname());
                        }
                    }

                    $zip->close();
                }

                if (file_exists($zip_name)) {
                    header('Content-type: application/zip');
                    header('Content-Disposition: attachment; filename="labels_order' . $order_id . '_shipment' . $shipment_id . '.zip"');
                    readfile($zip_name);
                    unlink($zip_name);
                }
            } else {
                $phar = new Phar(sys_get_temp_dir() . DS . 'order' . $order_id . 'shipment' . $shipment_id . '.phar');
                $phar = $phar->convertToExecutable(Phar::ZIP);
                $applicationType = 'zip';

                foreach ($colls2 as $coll) {
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
                    unlink($phar::running(false));
                    header("Content-Disposition: inline; filename=labels_order' . $order_id . '_shipment' . $shipment_id . '.zip");
                    header("Content-type: application/" . $applicationType);
                    return $pdfData;
                }
            }

        }
        return true;
    }

    protected function createInvoicePdf($params, $trackingnumber, $lbl, $storeId = null)
    {
        $a4HighPadding = 24;
        $a4WidthPaddingLeft = 11;
        $a4WidthPaddingRight = 41;

        $fontSize = 10;
        $lineHeight = 12;

        $this->pdf = new Zend_Pdf();
        $this->pdf->pages[] = $this->getPdfCurrentPage();
        $a4High = $this->pdfCurrentPage->getHeight();
        $a4Width = $this->pdfCurrentPage->getWidth();
        $this->setPdfA4Height($a4High);
        $centerText = $a4Width / 2.39;
        $font = Zend_Pdf_Font::fontWithPath(Mage::getBaseDir('app') . '/code/community/Infomodus/Dhllabel/etc/HelveticaWorld-Regular.ttf');
        $fontBold = Zend_Pdf_Font::fontWithPath(Mage::getBaseDir('app') . '/code/community/Infomodus/Dhllabel/etc/HelveticaWorld-Bold.ttf');
        $this->pdfCurrentPage->setLineWidth(2);

        $this->pdfCalcTop($a4HighPadding);
        $this->pdfCurrentPage->drawLine($a4WidthPaddingLeft, $this->pdfCurrentHeight, $a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight);

        $this->pdfCalcTop(2);
        $this->pdfCurrentPage->setFont($font, $fontSize);

        $this->pdfCalcTop(18);
        $this->pdfCurrentPage->drawText('Sender:', 205, $this->pdfCurrentHeight);

        $this->pdfCalcTop(18);
        $this->pdfCurrentPage->drawText($lbl->shipperName, $centerText, $this->pdfCurrentHeight, 'UTF-8');

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText($lbl->shipperAttentionName, $centerText, $this->pdfCurrentHeight, 'UTF-8');

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText($lbl->shipperAddressLine1, $centerText, $this->pdfCurrentHeight, 'UTF-8');

        if ($lbl->shipperAddressLine2 !== '') {
            $this->pdfCalcTop($lineHeight);
            $this->pdfCurrentPage->drawText($lbl->shipperAddressLine2, $centerText, $this->pdfCurrentHeight, 'UTF-8');
        }

        if ($lbl->shipperAddressLine3 !== '') {
            $this->pdfCalcTop($lineHeight);
            $this->pdfCurrentPage->drawText($lbl->shipperAddressLine3, $centerText, $this->pdfCurrentHeight, 'UTF-8');
        }

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText('Phone: ' . $lbl->shipperPhoneNumber, $centerText, $this->pdfCurrentHeight, 'UTF-8');

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText('Fax:', $centerText, $this->pdfCurrentHeight);

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText($lbl->shipperPostalCode . ' ' . $lbl->shipperCity, $centerText, $this->pdfCurrentHeight, 'UTF-8');

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText((strlen($lbl->shipperStateProvinceName) > 0 ? $lbl->shipperStateProvinceName . ', ' : '') . $lbl->shipperCountryName, $centerText, $this->pdfCurrentHeight, 'UTF-8');

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText('Sender\'s VAT No: ' . Mage::getStoreConfig('general/store_information/merchant_vat_number', $storeId), $centerText, $this->pdfCurrentHeight);


        $this->pdfCalcTop(10);
        $this->pdfCurrentPage->drawLine($a4WidthPaddingLeft, $this->pdfCurrentHeight, $a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight);

        $this->pdfCalcTop(15);

        if ($lbl->shiptoCountryCode !== "IN" && $lbl->shipperCountryCode === "IN") {
            $invoiceTitle = 'INVOICE';
        } else {
            $invoiceTitle = 'COMMERCIAL INVOICE';
        }

        $this->pdfCurrentPage->drawText('Date: ' . date('d.m.Y', time()), $a4WidthPaddingLeft + 3, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->setFont($fontBold, 14);

        $this->pdfCalcTop(1);
        $this->pdfCurrentPage->drawText($invoiceTitle, $centerText + 1, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->setFont($font, $fontSize);

        $this->pdfCalcTop($lineHeight + 8);
        $this->pdfCurrentPage->drawText('Invoice Number: B/' . $this->imOrder->getIncrementId(), $a4WidthPaddingLeft + 3, $this->pdfCurrentHeight);

        if ($lbl->shiptoCountryCode != 'IN' && $lbl->shipperCountryCode == 'IN') {
            if (trim(Mage::getStoreConfig('dhllabel/paperless/IECNo', $storeId)) != '') {
                $this->pdfCalcTop($lineHeight);
                $this->pdfCurrentPage->drawText('IEC Number: ' . Mage::getStoreConfig('dhllabel/paperless/IECNo', $storeId), $a4WidthPaddingLeft + 3, $this->pdfCurrentHeight);
            }

            if (trim(Mage::getStoreConfig('dhllabel/paperless/GSTIN', $storeId)) != '') {
                $this->pdfCalcTop($lineHeight);
                $this->pdfCurrentPage->drawText('GST No.' . Mage::getStoreConfig('dhllabel/paperless/GSTIN', $storeId), $a4WidthPaddingLeft + 3, $this->pdfCurrentHeight);
            }
        }

        $this->pdfCalcTop($lineHeight + 5);
        $this->pdfCurrentPage->drawText('Delivery to:', $a4WidthPaddingLeft + 3, $this->pdfCurrentHeight);

        $this->pdfCurrentPage->drawText($lbl->shiptoCompanyName, $a4WidthPaddingLeft + 95 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText('Billed To:', $a4WidthPaddingLeft + 371 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText($lbl->shiptoCompanyName, $a4WidthPaddingLeft + 442 / 1.332, $this->pdfCurrentHeight);


        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText($lbl->shiptoAttentionName, $a4WidthPaddingLeft + 95 / 1.332, $this->pdfCurrentHeight, 'UTF-8');
        $this->pdfCurrentPage->drawText('Contact:', $a4WidthPaddingLeft + 371 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText($lbl->shiptoAttentionName, $a4WidthPaddingLeft + 442 / 1.332, $this->pdfCurrentHeight, 'UTF-8');

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText($lbl->shiptoAddressLine1, $a4WidthPaddingLeft + 95 / 1.332, $this->pdfCurrentHeight, 'UTF-8');
        $this->pdfCurrentPage->drawText($lbl->shiptoAddressLine1, $a4WidthPaddingLeft + 442 / 1.332, $this->pdfCurrentHeight, 'UTF-8');

        if ($lbl->shiptoAddressLine2 !== '') {
            $this->pdfCalcTop($lineHeight);
            $this->pdfCurrentPage->drawText($lbl->shiptoAddressLine2, $a4WidthPaddingLeft + 95 / 1.332, $this->pdfCurrentHeight, 'UTF-8');
            $this->pdfCurrentPage->drawText($lbl->shiptoAddressLine2, $a4WidthPaddingLeft + 442 / 1.332, $this->pdfCurrentHeight, 'UTF-8');
        }

        if ($lbl->shiptoAddressLine3 !== '') {
            $this->pdfCalcTop($lineHeight);
            $this->pdfCurrentPage->drawText($lbl->shiptoAddressLine3, $a4WidthPaddingLeft + 95 / 1.332, $this->pdfCurrentHeight, 'UTF-8');
            $this->pdfCurrentPage->drawText($lbl->shiptoAddressLine3, $a4WidthPaddingLeft + 442 / 1.332, $this->pdfCurrentHeight, 'UTF-8');
        }

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText($lbl->shiptoCity, $a4WidthPaddingLeft + 95 / 1.332, $this->pdfCurrentHeight, 'UTF-8');
        $this->pdfCurrentPage->drawText($lbl->shiptoCity, $a4WidthPaddingLeft + 442 / 1.332, $this->pdfCurrentHeight, 'UTF-8');

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText($lbl->shiptoPostalCode . ' ' . $lbl->shiptoCity, $a4WidthPaddingLeft + 95 / 1.332, $this->pdfCurrentHeight, 'UTF-8');
        $this->pdfCurrentPage->drawText($lbl->shiptoPostalCode . ' ' . $lbl->shiptoCity, $a4WidthPaddingLeft + 442 / 1.332, $this->pdfCurrentHeight, 'UTF-8');

        /*$this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText(' ', $a4WidthPaddingLeft + 95 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText(' ', $a4WidthPaddingLeft + 442 / 1.332, $this->pdfCurrentHeight);*/

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText((strlen($lbl->shiptoStateProvinceName) > 0 ? $lbl->shiptoStateProvinceName . ', ' : '') . $lbl->shiptoCountryName, $a4WidthPaddingLeft + 95 / 1.332, $this->pdfCurrentHeight, 'UTF-8');
        $this->pdfCurrentPage->drawText((strlen($lbl->shiptoStateProvinceName) > 0 ? $lbl->shiptoStateProvinceName . ', ' : '') . $lbl->shiptoCountryName, $a4WidthPaddingLeft + 442 / 1.332, $this->pdfCurrentHeight, 'UTF-8');

        /*$this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText(' ', $a4WidthPaddingLeft + 95 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText(' ', $a4WidthPaddingLeft + 442 / 1.332, $this->pdfCurrentHeight);*/

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText('Phone: ' . $lbl->shiptoPhoneNumber, $a4WidthPaddingLeft + 95 / 1.332, $this->pdfCurrentHeight, 'UTF-8');
        $this->pdfCurrentPage->drawText('Phone: ' . $lbl->shiptoPhoneNumber, $a4WidthPaddingLeft + 442 / 1.332, $this->pdfCurrentHeight, 'UTF-8');

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText('Fax: ', $a4WidthPaddingLeft + 95 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText('Fax: ', $a4WidthPaddingLeft + 442 / 1.332, $this->pdfCurrentHeight);

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText('Receiver\'s VAT No: ', $a4WidthPaddingLeft + 95 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText('VAT No: ', $a4WidthPaddingLeft + 442 / 1.332, $this->pdfCurrentHeight);

        $heightRow = 25;
        $invoice_product = $params['invoice_product'];
        $countProduct = count($invoice_product);
        $ip = 1;
        $totalWeight = 0;
        $totalUnits = 0;
        $totalPrice = 0;
        $this->pdfCurrentPage->setFont($font, $fontSize - 2);
        $this->pdfCurrentPage->setLineWidth(1);

        $this->pdfCalcTop(2 * $lineHeight);
        $this->pdfCurrentPage->drawLine($a4WidthPaddingLeft, $this->pdfCurrentHeight, $a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight);

        $this->pdfCurrentPage->drawLine($a4WidthPaddingLeft, $this->pdfCurrentHeight, $a4WidthPaddingLeft, $this->pdfCurrentHeight - $lineHeight * 3);
        $this->pdfCurrentPage->drawLine(231 / 1.332, $this->pdfCurrentHeight, 231 / 1.332, $this->pdfCurrentHeight - $lineHeight * 3);
        $this->pdfCurrentPage->drawLine(281 / 1.332, $this->pdfCurrentHeight, 281 / 1.332, $this->pdfCurrentHeight - $lineHeight * 3);
        $this->pdfCurrentPage->drawLine(354 / 1.332, $this->pdfCurrentHeight, 354 / 1.332, $this->pdfCurrentHeight - $lineHeight * 3);
        $this->pdfCurrentPage->drawLine(456 / 1.332, $this->pdfCurrentHeight, 456 / 1.332, $this->pdfCurrentHeight - $lineHeight * 3);
        $this->pdfCurrentPage->drawLine(523 / 1.332, $this->pdfCurrentHeight, 523 / 1.332, $this->pdfCurrentHeight - $lineHeight * 3);
        $this->pdfCurrentPage->drawLine(644 / 1.332, $this->pdfCurrentHeight, 644 / 1.332, $this->pdfCurrentHeight - $lineHeight * 3);
        $this->pdfCurrentPage->drawLine($a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight, $a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight - $lineHeight * 3);
        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText('Full Description of Goods', 49 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText('Qty', 237 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText('Unit', 307 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText('Value', 301 / 1.332, $this->pdfCurrentHeight - $lineHeight);
        $this->pdfCurrentPage->drawText('Subtotal', 382 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText('Value', 389 / 1.332, $this->pdfCurrentHeight - $lineHeight);
        $this->pdfCurrentPage->drawText('Unit Net', 467 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText('Weight', 470 / 1.332, $this->pdfCurrentHeight - $lineHeight);
        $this->pdfCurrentPage->drawText('Country of', 554 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText('Manufacture/', 546 / 1.332, $this->pdfCurrentHeight - 10);
        $this->pdfCurrentPage->drawText('Origin', 567 / 1.332, $this->pdfCurrentHeight - 20);
        $this->pdfCurrentPage->drawText('Comm.', 673 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText('Code', 679 / 1.332, $this->pdfCurrentHeight - $lineHeight);


        $this->pdfCalcTop(2 * $lineHeight);

        $this->pdfCurrentPage->drawLine($a4WidthPaddingLeft, $this->pdfCurrentHeight, $a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight);

        /*$this->pdfCalcTop(-14);*/

        foreach ($invoice_product as $itemData) {
            if (isset($itemData['enable']) && $itemData['enable'] == 1) {
                $this->pdfCurrentPage->setFont($font, $fontSize - 2);
                $originProduct = Mage::getModel('catalog/product')->load($itemData['id'])->getData();
                $productName = explode(" ", $itemData['name']);
                $productNameLine = '';
                $i = 0;
                foreach ($productName as $k => $v) {
                    $this->pdfCurrentPage->setFont($font, $fontSize - 2);
                    if (strlen($productNameLine . ' ' . $v) < 35) {
                        $space = ' ';
                        if (strlen($productNameLine) == 0) {
                            $space = '';
                        }
                        $productNameLine .= $space . $v;
                        if (count($productName) > $k + 1) {
                            continue;
                        }
                    }
                    $this->pdfCalcTop($lineHeight);
                    $this->pdfCurrentPage->drawText($productNameLine, 17 / 1.332, $this->pdfCurrentHeight, 'UTF-8');
                    $this->pdfCalcTop(-1 * $lineHeight);
                    $this->pdfCurrentPage->drawLine($a4WidthPaddingLeft, $this->pdfCurrentHeight, $a4WidthPaddingLeft, $this->pdfCurrentHeight - ($lineHeight));
                    $this->pdfCurrentPage->drawLine(231 / 1.332, $this->pdfCurrentHeight, 231 / 1.332, $this->pdfCurrentHeight - ($lineHeight));
                    $this->pdfCurrentPage->drawLine(281 / 1.332, $this->pdfCurrentHeight, 281 / 1.332, $this->pdfCurrentHeight - ($lineHeight));
                    $this->pdfCurrentPage->drawLine(354 / 1.332, $this->pdfCurrentHeight, 354 / 1.332, $this->pdfCurrentHeight - ($lineHeight));
                    $this->pdfCurrentPage->drawLine(456 / 1.332, $this->pdfCurrentHeight, 456 / 1.332, $this->pdfCurrentHeight - ($lineHeight));
                    $this->pdfCurrentPage->drawLine(523 / 1.332, $this->pdfCurrentHeight, 523 / 1.332, $this->pdfCurrentHeight - ($lineHeight));
                    $this->pdfCurrentPage->drawLine(644 / 1.332, $this->pdfCurrentHeight, 644 / 1.332, $this->pdfCurrentHeight - ($lineHeight));
                    $this->pdfCurrentPage->drawLine($a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight, $a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight - ($lineHeight));
                    $this->pdfCalcTop($lineHeight);
                    $productNameLine = $v;
                    $i++;
                }
                $this->pdfCurrentPage->drawText(round($itemData['qty'], 0), 250 / 1.332, $this->pdfCurrentHeight);
                $this->pdfCurrentPage->drawText(round($itemData['price'], 2), 300 / 1.332, $this->pdfCurrentHeight);
                $this->pdfCurrentPage->drawText(round($itemData['qty'] * $itemData['price'], 2), 390 / 1.332, $this->pdfCurrentHeight);
                $this->pdfCurrentPage->drawText(round($itemData['weight'], 2), 480 / 1.332, $this->pdfCurrentHeight);
                $totalWeight += $itemData['weight'] * $itemData['qty'];
                $this->pdfCurrentPage->drawText(isset($originProduct['country_of_manufacture']) ? Mage::getModel('directory/country')->loadByCode($originProduct['country_of_manufacture'])->getName() : '', 528 / 1.332, $this->pdfCurrentHeight, 'UTF-8');
                $this->pdfCurrentPage->drawText($itemData['commodity_code'], 649 / 1.332, $this->pdfCurrentHeight, 'UTF-8');
                $totalUnits += $itemData['qty'];
                $totalPrice += $itemData['price'] * $itemData['qty'];

                $this->pdfCurrentPage->drawLine($a4WidthPaddingLeft, $this->pdfCurrentHeight, $a4WidthPaddingLeft, $this->pdfCurrentHeight - (5));
                $this->pdfCurrentPage->drawLine(231 / 1.332, $this->pdfCurrentHeight, 231 / 1.332, $this->pdfCurrentHeight - (5));
                $this->pdfCurrentPage->drawLine(281 / 1.332, $this->pdfCurrentHeight, 281 / 1.332, $this->pdfCurrentHeight - (5));
                $this->pdfCurrentPage->drawLine(354 / 1.332, $this->pdfCurrentHeight, 354 / 1.332, $this->pdfCurrentHeight - (5));
                $this->pdfCurrentPage->drawLine(456 / 1.332, $this->pdfCurrentHeight, 456 / 1.332, $this->pdfCurrentHeight - (5));
                $this->pdfCurrentPage->drawLine(523 / 1.332, $this->pdfCurrentHeight, 523 / 1.332, $this->pdfCurrentHeight - (5));
                $this->pdfCurrentPage->drawLine(644 / 1.332, $this->pdfCurrentHeight, 644 / 1.332, $this->pdfCurrentHeight - (5));
                $this->pdfCurrentPage->drawLine($a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight, $a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight - (5));

                $this->pdfCalcTop(5);
                $this->pdfCurrentPage->drawLine($a4WidthPaddingLeft, $this->pdfCurrentHeight, $a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight);
                $ip++;
            }
        }

        $this->pdfCalcTop(17);
        /* Horizontal */
        $this->pdfCurrentPage->setFont($fontBold, $fontSize);
        $this->pdfCurrentPage->drawText('Total Declared Value: ' . round($totalPrice, 2) . ' ' . $lbl->currencyCode, 234 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->setFont($font, $fontSize);
        $this->pdfCurrentPage->drawText('Total Net Weight: ' . round($totalWeight, 2) . ' ' . ($lbl->weightUnits == 'K' ? 'kg' : 'lb') . '(s)', 460 / 1.332, $this->pdfCurrentHeight);

        $this->pdfCalcTop(-17);
        $this->pdfCurrentPage->drawLine($a4WidthPaddingLeft, $this->pdfCurrentHeight, $a4WidthPaddingLeft, $this->pdfCurrentHeight - $heightRow);
        $this->pdfCurrentPage->drawLine(231 / 1.332, $this->pdfCurrentHeight, 231 / 1.332, $this->pdfCurrentHeight - $heightRow);
        $this->pdfCurrentPage->drawLine(456 / 1.332, $this->pdfCurrentHeight, 456 / 1.332, $this->pdfCurrentHeight - $heightRow);
        $this->pdfCurrentPage->drawLine($a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight, $a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight - $heightRow);

        $this->pdfCalcTop($heightRow + 17);

        $this->pdfCurrentPage->drawText('Total Unit(s): ' . $totalUnits, 234 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText('Total Gross Weight ' . round($totalWeight, 2) . ' ' . ($lbl->weightUnits == 'K' ? 'kg' : 'lb') . '(s)', 460 / 1.332, $this->pdfCurrentHeight);

        $this->pdfCalcTop(-17);
        $this->pdfCurrentPage->drawLine(231 / 1.332, $this->pdfCurrentHeight, 231 / 1.332, $this->pdfCurrentHeight - $heightRow);
        $this->pdfCurrentPage->drawLine(456 / 1.332, $this->pdfCurrentHeight, 456 / 1.332, $this->pdfCurrentHeight - $heightRow);
        $this->pdfCurrentPage->drawLine(231 / 1.332, $this->pdfCurrentHeight, $a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawLine($a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight, $a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight - $heightRow);
        $this->pdfCurrentPage->drawLine($a4WidthPaddingLeft, $this->pdfCurrentHeight, $a4WidthPaddingLeft, $this->pdfCurrentHeight - $heightRow);

        $this->pdfCalcTop($heightRow);
        $this->pdfCurrentPage->drawLine($a4WidthPaddingLeft, $this->pdfCurrentHeight, $a4Width - $a4WidthPaddingRight, $this->pdfCurrentHeight);
        /* END Horizontal */


        $this->pdfCalcTop(2 * $lineHeight);
        $this->pdfCurrentPage->drawText('Type of Export', 17 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText(': permanent', 173 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText('Currency Code', 381 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText(': ' . $lbl->currencyCode, 535 / 1.332, $this->pdfCurrentHeight);

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText('Reason for Export', 17 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText(': SELL', 173 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText('Terms of Trade', 381 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText(': DAP / ' . $lbl->shiptoCity, 535 / 1.332, $this->pdfCurrentHeight);

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText('Duty/Taxes Acct', 17 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText(': ' . $lbl->shippernumber, 173 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText('City Name of liability', 381 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText(':', 535 / 1.332, $this->pdfCurrentHeight);


        /*
        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText('Remark', 17 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText(': Miss', 173 / 1.332, $this->pdfCurrentHeight);*/

        $this->pdfCalcTop(54);
        $this->pdfCurrentPage->drawText('Signature: ' . $lbl->shipperAttentionName, 17 / 1.332, $this->pdfCurrentHeight);

        $this->pdfCalcTop(2 * $lineHeight);
        $this->pdfCurrentPage->drawText('Airwaybill Number: ' . $trackingnumber, 17 / 1.332, $this->pdfCurrentHeight);

        $this->pdfCalcTop(2 * $lineHeight);
        $this->pdfCurrentPage->drawText('Company Stamp: ', 470 / 1.332, $this->pdfCurrentHeight);
        $this->pdfCurrentPage->drawText($lbl->shipperName, 580 / 1.332, $this->pdfCurrentHeight);

        $this->pdfCalcTop($lineHeight);
        $this->pdfCurrentPage->drawText($lbl->shipperPostalCode . ' ' . $lbl->shipperStateProvinceName, 580 / 1.332, $this->pdfCurrentHeight);

        $this->pdfRenderedData = $this->pdf->render();
        return $this->pdf->render();
    }

    protected function setPdfA4Height($height)
    {
        $this->pdfA4Height = $height;
        $this->pdfCurrentHeight = $this->pdfA4Height;
    }

    protected function pdfCalcTop($lineHeight)
    {
        $this->pdfCurrentHeight -= $lineHeight;
        if ($this->pdfCurrentHeight - 30 < 0) {
            $this->pdfCurrentHeight = $this->pdfA4Height - 24;
            $this->pdf->pages[] = $this->getPdfCurrentPage();
        }
    }

    protected function getPdfCurrentPage()
    {
        $this->pdfCurrentPage = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_A4);
        $font = Zend_Pdf_Font::fontWithPath(Mage::getBaseDir('app') . '/code/community/Infomodus/Dhllabel/etc/HelveticaWorld-Regular.ttf');
        $this->pdfCurrentPage->setFont($font, 10);
        return $this->pdfCurrentPage;
    }
}