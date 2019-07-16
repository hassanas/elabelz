<?php

/**
 * Created by JetBrains PhpStorm.
 * User: Owner
 * Date: 10.01.12
 * Time: 13:30
 * To change this template use File | Settings | File Templates.
 */
require_once 'Adminhtml/Dhllabel/DhllabelController.php';

class Infomodus_Dhllabel_RefundController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        parent::preDispatch();

        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function printAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('root')->setTemplate('dhllabel/sales/order/refund/refund.phtml');
        $this->renderLayout();
    }

    public function customerrefundAction()
    {
        if ($this->getRequest()->isPost()) {
            $orderId = $this->getRequest()->getParam('id');
            $order = Mage::getModel('sales/order')->load($orderId);
            $storeId = NULL;
            /*multistore*/
            $storeId = $order->getStoreId();
            /*multistore*/
            if (Mage::getStoreConfig('dhllabel/return/frontend_customer_return', $storeId) == 1) {
                $path = Mage::getBaseUrl('media') . 'dhllabel' . DS . "label" . DS;
                $type = 'refund';
                $collections = Mage::getModel('dhllabel/dhllabel');
                $colls = $collections->getCollection()->addFieldToFilter('order_id', $orderId)
                    ->addFieldToFilter('type', $type);
                $coll = 0;
                foreach ($colls AS $v) {
                    $coll = $v['label_id'];
                    break;
                }

                /*$collection = Mage::getModel('dhllabel/dhllabel')->load($coll);
                if ($collection->getOrderId() != $orderId) {*/
                    $order = Mage::getModel('sales/order')->load($orderId);

                    $path = Mage::getBaseDir('media') . DS . 'dhllabel' . DS . 'label' . DS;

                    $controller = new Infomodus_Dhllabel_Adminhtml_Dhllabel_DhllabelController();
                    $controller->intermediatehandy($orderId, $type);

                    $lbl = Mage::getModel('dhllabel/dhl');

                    $lbl = $controller->setParams(
                        $lbl,
                        $controller->defConfParams,
                        array($controller->defParams)/*multistore*/, $storeId/*multistore*/,
                        $order
                    );

                    $weight = 0;
                    $paramWeight = $this->getRequest()->getParam('weight');
                    foreach ($this->getRequest()->getParam('cart') AS $k => $item) {
                        if (!empty($item) && isset($item['qty']) && $item['qty'] > 0) {
                            $weight += $paramWeight[$k] * $item['qty'];
                        }
                    }

                    $lbl->packages[0]['weight'] = ceil($weight);

                    $upsl = $lbl->getShipFrom();

                    $dhllabel = Mage::getModel('dhllabel/dhllabel');
                    $collsTwo = $dhllabel->getCollection()
                        ->addFieldToFilter('order_id', $orderId)
                        ->addFieldToFilter('shipment_id', 0)
                        ->addFieldToFilter('type', $type)
                        ->addFieldToFilter('status', 1);

                    if (!empty($collsTwo)) {
                        foreach ($collsTwo AS $c) {
                            $c->delete();
                        }
                    }

                    $responseData = $upsl;
                    $note = isset($responseData['Note']) ? (array)$responseData['Note'] : array();
                    $trackingnumber = isset($responseData['AirwayBillNumber']) ? $responseData['AirwayBillNumber'] : "";

                    if (isset($note['ActionNote']) && $note['ActionNote'] === 'Success') {
                        $LabelImage = (array)$responseData['LabelImage'];
                        if (file_put_contents($path . 'label_' . $trackingnumber . '.pdf', base64_decode($LabelImage['OutputImage']))) {
                            $dhllabel = Mage::getModel('dhllabel/dhllabel');
                            $dhllabel->setTitle('Order ' . $orderId . ' TN' . $trackingnumber);
                            $dhllabel->setOrderId($orderId);
                            $dhllabel->setShipmentId(0);
                            $dhllabel->setType($type);
                            $dhllabel->setTrackingnumber($trackingnumber);
                            $dhllabel->setLabelname('label_' . $trackingnumber . '.pdf');
                            $dhllabel->setStatustext(Mage::helper('adminhtml')->__($note['ActionNote']));
                            $dhllabel->setStatus(0);
                            $dhllabel->setCreatedTime(Date("Y-m-d H:i:s"));
                            $dhllabel->setUpdateTime(Date("Y-m-d H:i:s"));
                            $dhllabel->save();
                            return $this->_prepareDownloadResponse(
                                'dhl-label' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') . '.pdf',
                                file_get_contents($path . 'label_' . $trackingnumber . '.pdf'),
                                'application/pdf'
                            );
                        } else {
                            Mage::register('error', 'Error writing file');
                            $this->loadLayout();
                            $this->renderLayout();
                        }
                    } else {
                        $error = (array)$responseData['Response'];
                        $error = (array)$error['Status'];
                        $error = $error['Condition'];
                        $errordescArr = '';
                        $error = (array)$error;
                        if (!isset($error['ConditionData'])) {
                            foreach ($error AS $err) {
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

                        Mage::register('error', $errordescArr);
                        $dhllabel = Mage::getModel('dhllabel/dhllabel');
                        $dhllabel->setTitle('Order ' . $orderId);
                        $dhllabel->setOrderId($orderId);
                        $dhllabel->setShipmentId(0);
                        $dhllabel->setType($type);
                        $dhllabel->setStatustext($errordescArr);
                        $dhllabel->setStatus(1);
                        $dhllabel->setCreatedTime(Date("Y-m-d H:i:s"));
                        $dhllabel->setUpdateTime(Date("Y-m-d H:i:s"));
                        $dhllabel->save();
                        $this->loadLayout();
                        $this->renderLayout();
                    }

                /*} else {
                    return $this->_prepareDownloadResponse(
                        'dhl-label' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') .
                        '.pdf', file_get_contents($path . 'label_' . $collection->getTrackingnumber() . '.pdf'),
                        'application/pdf'
                    );
                }*/
            }
        } else {
            $this->loadLayout();
            $this->renderLayout();
        }
    }

    public function customershowlabelAction()
    {
        $trackId = $this->getRequest()->getParam('id');
        $label = Mage::getModel('dhllabel/dhllabel')->getCollection()
            ->addFieldToFilter('trackingnumber', $trackId)
            ->addFieldToFilter('type', 'refund');
        if (count($label) > 0) {
            $label = $label->getFirstItem()->getData();

            $order = Mage::getModel('sales/order')->load($label['order_id']);
            /*multistore*/
            $storeId = $order->getStoreId();
            /*multistore*/
            if (Mage::getStoreConfig('dhllabel/return/frontend_customer_return', $storeId) == 1) {
                if (count($label) > 0) {
                    $path = Mage::getBaseDir('media') . '/dhllabel/label/';
                    return $this->_prepareDownloadResponse('dhl-label' . Mage::getSingleton('core/date')->date('Y-m-d_H-i-s') .
                        '.pdf', file_get_contents($path . $label['labelname']), 'application/pdf');
                }
            }
        }
    }
}
