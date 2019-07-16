<?php
/**
 * Created by PhpStorm.
 * User: Vitalij
 * Date: 29.05.14
 * Time: 11:37
 */
require_once 'UpslabelController.php';
require_once 'PdflabelsController.php';

class Infomodus_Upslabel_Adminhtml_Upslabel_AutocreatelabelController extends Mage_Adminhtml_Controller_Action
{

    protected function _isAllowed()
    {
        return true;
    }

    public function indexAction()
    {
        try {
            $ptype = $this->getRequest()->getParam('type');
            $type = 'shipment';
            $order_ids = $this->getRequest()->getParam($ptype . '_ids', array());
            if (!is_array($order_ids)) {
                $order_ids = explode(",", $order_ids);
            }
            $countCreateLabel = 0;
            foreach ($order_ids AS $order_id) {
                $order = Mage::getModel('sales/order')->load($order_id);
                $storeId = NULL;
                
                $isShippingActiveMethods = Mage::getStoreConfig('upslabel/bulk_create_labels/bulk_shipping_methods');
                if ($isShippingActiveMethods == 'specify') {
                    $shippingActiveMethods = trim(Mage::getStoreConfig('upslabel/bulk_create_labels/apply_to'), " ,");
                    $shippingActiveMethods = strlen($shippingActiveMethods) > 0 ? explode(",", $shippingActiveMethods) : array();
                }
                $isOrderStatuses = Mage::getStoreConfig('upslabel/bulk_create_labels/bulk_order_status');
                if ($isOrderStatuses == 'specify') {
                    $orderStatuses = explode(",", trim(Mage::getStoreConfig('upslabel/bulk_create_labels/orderstatus'), " ,"));
                }
                if (
                    (
                        $isShippingActiveMethods == 'all'
                        || (
                            isset($shippingActiveMethods)
                            && !empty($shippingActiveMethods)
                            && in_array($order->getShippingMethod(), $shippingActiveMethods)
                        )
                    )
                    &&
                    (
                        $isOrderStatuses
                        ||
                        (
                            isset($orderStatuses)
                            && !empty($orderStatuses)
                            && in_array($order->getStatus(), $orderStatuses)
                        )
                    )
                ) {
                    $collections = Mage::getModel('upslabel/upslabel');
                    $colls = $collections->getCollection()->addFieldToFilter('order_id', $order_id)->addFieldToFilter('type', $type)->addFieldToFilter('status', 0);
                    if (count($colls) == 0) {
                        $controller = new Infomodus_Upslabel_Adminhtml_Upslabel_UpslabelController();
                        $controller->intermediatehandy($order_id, $type);
                        $AccessLicenseNumber = Mage::getStoreConfig('upslabel/credentials/accesslicensenumber');
                        $UserId = Mage::getStoreConfig('upslabel/credentials/userid');
                        $Password = Mage::getStoreConfig('upslabel/credentials/password');
                        $shipperNumber = Mage::getStoreConfig('upslabel/credentials/shippernumber');

                        $lbl = Mage::getModel('upslabel/ups');
                        $lbl->setCredentials($AccessLicenseNumber, $UserId, $Password, $shipperNumber);
                        $lbl = $controller->setParams($lbl, $controller->defConfParams, $controller->defParams );
                        $upsl = $lbl->getShip();
                        if ($controller->defConfParams['default_return'] == 1) {
                            $lbl->serviceCode = array_key_exists('default_return_servicecode', $controller->defConfParams) ? $controller->defConfParams['default_return_servicecode'] : '';
                            $upsl2 = $lbl->getShipFrom();
                        }
                        Mage::register('isCreateLabelNow' . $order_id, 2);
                        if (!isset($upsl2)) {
                            $upsl2 = NULL;
                        }
                        $controller->saveDB($upsl, $upsl2, $controller->defConfParams, $order_id, null, $type);
                        $countCreateLabel++;
                    }
                }

            }

            $this->_redirectReferer();
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return true;
    }
} 