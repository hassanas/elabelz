<?php
/**
 * Created by PhpStorm.
 * User: Vitalij
 * Date: 29.05.14
 * Time: 11:37
 */
require_once 'DhllabelController.php';
require_once 'PdflabelsController.php';

class Infomodus_Dhllabel_Adminhtml_Dhllabel_AutocreatelabelController extends Mage_Adminhtml_Controller_Action
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
            /* $this->loadLayout();*/
            $orderIds = $this->getRequest()->getParam($ptype . '_ids', array());
            if (!is_array($orderIds)) {
                $orderIds = explode(",", $orderIds);
            }
            $countCreateLabel = 0;
            foreach ($orderIds AS $orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);
                $storeId = null;
                /*multistore*/
                $storeId = $order->getStoreId();
                /*multistore*/


                $isShippingActiveMethods = Mage::getStoreConfig('dhllabel/bulk_create_labels/bulk_shipping_methods', $storeId);
                if ($isShippingActiveMethods == 'specify') {
                    $shippingActiveMethods = trim(Mage::getStoreConfig('dhllabel/bulk_create_labels/apply_to', $storeId), " ,");
                    $shippingActiveMethods = strlen($shippingActiveMethods) > 0 ? explode(",", $shippingActiveMethods) : array();
                }

                $isOrderStatuses = Mage::getStoreConfig('dhllabel/bulk_create_labels/bulk_order_status', $storeId);
                if ($isOrderStatuses == 'specify') {
                    $orderStatuses = explode(",", trim(Mage::getStoreConfig('dhllabel/bulk_create_labels/orderstatus', $storeId), " ,"));
                }

                if ((
                        $isShippingActiveMethods == 'all'
                        || (
                            !empty($shippingActiveMethods)
                            && in_array($order->getShippingMethod(), $shippingActiveMethods)
                        )
                    )
                    &&
                    (
                        $isOrderStatuses == 'all'
                        ||
                        (
                            !empty($orderStatuses)
                            && in_array($order->getStatus(), $orderStatuses)
                        )
                    )
                ) {
                    $collections = Mage::getModel('dhllabel/dhllabel');
                    $colls = $collections->getCollection()->addFieldToFilter('order_id', $orderId)
                        ->addFieldToFilter('type', $type)->addFieldToFilter('status', 0);

                    if (count($colls) == 0) {
                        $controller = new Infomodus_Dhllabel_Adminhtml_Dhllabel_DhllabelController();
                        $controller->intermediatehandy($orderId, $type);

                        $lbl = Mage::getModel('dhllabel/dhl');

                        $lbl = $controller->setParams($lbl, $controller->defConfParams, $controller->defParams, $storeId, $order);
                        $lbl->codOrderId = $orderId;

                        $upsl = $lbl->getShip($storeId);
                        $upsl2 = null;
                        if ($controller->defConfParams['default_return'] == 1) {
                            $lbl->serviceCode = array_key_exists('default_return_servicecode', $controller->defConfParams) ? $controller->defConfParams['default_return_servicecode'] : '';
                            $upsl2 = $lbl->getShipFrom( /*multistore*/
                                $storeId /*multistore*/);
                        }

                        if (!Mage::registry('isCreateLabelNow')) {
                            Mage::register('isCreateLabelNow', 2);
                        }

                        $controller->saveDB($upsl, $upsl2, $controller->defConfParams, $orderId, 0, $type, $lbl);
                        $countCreateLabel++;
                        if(is_array($controller->labelDirectIds) && count($controller->labelDirectIds) > 0){
                            foreach ($controller->labelDirectIds as $labelModel){
                                $labelIds[] = $labelModel;
                            }
                        }
                    }
                }
            }



            if ($countCreateLabel == 0) {
                $this->_getSession()->addError($this->__('Not created any labels'));
            } else {
                if(Mage::getStoreConfig('dhllabel/bulk_create_labels/print_immediately', $storeId) == 1){
                    $controllerPdf = new Infomodus_Dhllabel_Adminhtml_Dhllabel_PdflabelsController();
                    $pdfData = $controllerPdf->createPdf($labelIds, 'shipment', null, true);
                    if($pdfData !== false) {
                        return $this->_prepareDownloadResponse('dhl-labels' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') .
                            '.pdf', $pdfData, 'application/pdf');
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