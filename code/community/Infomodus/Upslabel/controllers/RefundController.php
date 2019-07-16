<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 10.01.12
 * Time: 13:30
 * To change this template use File | Settings | File Templates.
 */
require_once 'Adminhtml/Upslabel/UpslabelController.php';
class Infomodus_Upslabel_RefundController extends Mage_Core_Controller_Front_Action
{
    public $shippingAddress;
    public $imOrder;

    public function preDispatch()
    {
        parent::preDispatch();

        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    protected function _isAllowed()
    {
        return true;
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function printAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('root')->setTemplate('upslabel/sales/order/refund/refund.phtml');
        $this->renderLayout();
    }

    public function customerrefundAction()
    {
        $order_id = $this->getRequest()->getParam('id');
        $this->imOrder = Mage::getModel('sales/order')->load($order_id);
        $storeId = NULL;
        
        if (Mage::getStoreConfig('upslabel/return/frontend_customer_return') == 1) {
            if ($this->getRequest()->isPost()) {
                $type = 'refund';


                $AccessLicenseNumber = Mage::getStoreConfig('upslabel/credentials/accesslicensenumber');
                $UserId = Mage::getStoreConfig('upslabel/credentials/userid');
                $Password = Mage::getStoreConfig('upslabel/credentials/password');
                $shipperNumber = Mage::getStoreConfig('upslabel/credentials/shippernumber');
                $order = Mage::getModel('sales/order')->load($order_id);
                $shipTo = $order->getShippingAddress();
                $this->shippingAddress = $shipTo;

                $path = Mage::getBaseDir('media') . DS . 'upslabel' . DS . 'label' . DS;

                $lbl = Mage::getModel('upslabel/ups');

                $lbl->setCredentials($AccessLicenseNumber, $UserId, $Password, $shipperNumber);

                $controller = new Infomodus_Upslabel_Adminhtml_Upslabel_UpslabelController();

                $controller->intermediatehandy($order_id, $type);
                $lbl = $controller->setParams($lbl, $controller->defConfParams, $controller->defParams );

                $errors = 0;
                if (strlen($lbl->shiptoAddressLine1) > 35) {
                    Mage::getSingleton('core/session')->addError($this->__('Address line 1 should not exceed 35 characters'));
                    Mage::log($this->__('Address line 1 should not exceed 35 characters'), NULL, 'upslabel_error.log');
                    $errors++;
                }
                if (strlen($lbl->shiptoAddressLine2) > 35) {
                    Mage::getSingleton('core/session')->addError($this->__('Address line 2 should not exceed 35 characters'));
                    Mage::log($this->__('Address line 2 should not exceed 35 characters'), NULL, 'upslabel_error.log');
                    $errors++;
                }
                if (strlen($lbl->shiptoPhoneNumber) > 15) {
                    Mage::getSingleton('core/session')->addError($this->__('Phone number should not exceed 15 characters'));
                    Mage::log($this->__('Phone number should not exceed 15 characters'), NULL, 'upslabel_error.log');
                    $errors++;
                }
                if (strlen($lbl->shiptoCompanyName) > 35) {
                    Mage::getSingleton('core/session')->addError($this->__('Company Name should not exceed 35 characters'));
                    Mage::log($this->__('"Ship to" Company Name should not exceed 35 characters'), NULL, 'upslabel_error.log');
                    $errors++;
                }
                if (strlen($lbl->shiptoAttentionName) > 35) {
                    Mage::getSingleton('core/session')->addError($this->__('Attention Name should not exceed 35 characters'));
                    Mage::log($this->__('"Ship to" Attention Name should not exceed 35 characters'), NULL, 'upslabel_error.log');
                    $errors++;
                }
                if (strlen($lbl->shiptoCity) > 30) {
                    Mage::getSingleton('core/session')->addError($this->__('City should not exceed 30 characters'));
                    Mage::log($this->__('"Ship to" City should not exceed 30 characters'), NULL, 'upslabel_error.log');
                    $errors++;
                }
                if (strlen($lbl->shiptoStateProvinceCode) > 5) {
                    Mage::getSingleton('core/session')->addError($this->__('State Province should not exceed 5 characters'));
                    Mage::log($this->__('"Ship to" State Province Code should not exceed 5 characters'), NULL, 'upslabel_error.log');
                    $errors++;
                }
                if (strlen($lbl->shiptoPostalCode) > 10) {
                    Mage::getSingleton('core/session')->addError($this->__('Postal Code should not exceed 10 characters'));
                    Mage::log($this->__('"Ship to" Postal Code should not exceed 10 characters'), NULL, 'upslabel_error.log');
                    $errors++;
                }
                /*if ($weight <= 0) {
                    Mage::getSingleton('core/session')->addError($this->__('The current weight must be greater than zero'));
                    Mage::log($this->__('The current weight must be greater than zero'), NULL, 'upslabel_error.log');
                    $errors++;
                }*/
                if ($errors > 0) {
                    $this->loadLayout();
                    $this->renderLayout();
                } else {
                    $lbl->codYesNo = 0;
                    $lbl->currencyCode = '';
                    $lbl->codMonetaryValue = '';
                    $upsl = $lbl->getShipFrom();
                    if (!array_key_exists('error', $upsl) || !$upsl['error']) {
                        foreach ($upsl['arrResponsXML'] AS $upsl_one) {
                            $upslabel = Mage::getModel('upslabel/upslabel');
                            $upslabel->setTitle('Order ' . $order_id . ' TN' . $upsl_one['trackingnumber']);
                            $upslabel->setOrderId($order_id);
                            $upslabel->setShipmentId(0);
                            $upslabel->setType($type);
                            /*$upslabel->setBase64Image();*/
                            $upslabel->setTrackingnumber($upsl_one['trackingnumber']);
                            $upslabel->setShipmentidentificationnumber($upsl['shipidnumber']);
                            $upslabel->setShipmentdigest($upsl['digest']);
                            if (!isset($upsl_one['labelname'])) {
                                $upslabel->setLabelname('label' . $upsl_one['trackingnumber'] . '.' . strtolower($upsl_one['type_print']));
                            } else {
                                $upslabel->setLabelname($upsl_one['labelname']);
                            }
                            $upslabel->setTypePrint($upsl_one['type_print']);
                            $upslabel->setCreatedTime(date("Y-m-d H:i:s"));
                            $upslabel->setUpdateTime(date("Y-m-d H:i:s"));
                            $upslabel->save();

                            $upslabel = Mage::getModel('upslabel/labelprice');
                            $upslabel->setOrderId($order_id);
                            $upslabel->setShipmentId(0);
                            $upslabel->setPrice($upsl['price']['price'] . " " . $upsl['price']['currency']);
                            $upslabel->save();
                        }
                        if (!isset($upsl_one['labelname'])) {
                            include($path . $upsl_one['trackingnumber'] . '.html');
                        } else {
                            $this->_redirectUrl($upsl_one['labelname']);
                        }
                    } else {
                        Mage::register('error', preg_replace('/\<textarea\>.*?\<\/textarea\>/is', '', $upsl['error']));
                        Mage::log(preg_replace('/\<textarea\>.*?\<\/textarea\>/is', '', $upsl['error']), NULL, 'upslabel_error.log');
                        $this->loadLayout();
                        $this->renderLayout();
                    }
                }
                /*}
                else {
                    Mage::getSingleton('core/session')->addError($this->__('For one order, you can create only one return'));
                    $this->_redirectUrl($_SERVER['HTTP_REFERER']);
                }*/
            } else {
                $this->loadLayout();
                $this->renderLayout();
            }
        }
    }

    public function customershowlabelAction()
    {
        $track_id = $this->getRequest()->getParam('id');
        $label = Mage::getModel('upslabel/upslabel')->getCollection()->addFieldToFilter('trackingnumber', $track_id)->addFieldToFilter('type', 'refund');
        $label = $label->getData();
        $label = $label[0];

        $order = Mage::getModel('sales/order')->load($label['order_id']);
        
        if (Mage::getStoreConfig('upslabel/return/frontend_customer_return') == 1) {
            if (!empty($label)) {
                $path = Mage::getBaseDir('media') . DS . 'upslabel' . DS . 'label' . DS;
                if ($label['type_print'] != 'link') {
                    include($path . $label['trackingnumber'] . '.html');
                } else {
                    $this->_redirectUrl($label['labelname']);
                }
            }
        }
    }

    public function macropaste($value)
    {
        return str_replace(
            array("#order_id#", "#customer_name#"),
            array($this->imOrder->getIncrementId(), $this->shippingAddress->getFirstname() . ' ' . $this->shippingAddress->getLastname()),
            $value
        );
    }
}
