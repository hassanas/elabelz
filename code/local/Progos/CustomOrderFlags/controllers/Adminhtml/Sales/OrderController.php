<?php
/**
 * Progos_CustomOrderFlags
 *
 * @category    Progos
 * @package     Progos_CustomOrderFlags
 * @author      Touqeer Jalal <touqeer.jalal@progos.org>
 * @Modified    Saroop Chand <saroop.chand@progos.org> 22-02-2018
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */
include_once Mage::getModuleDir('controllers', 'Mage_Adminhtml') . DS . 'Sales' . DS . 'OrderController.php';
class Progos_CustomOrderFlags_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController 
{

	protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order');
    }
    public function setOosAction() {
        $data = $this->getRequest()->getParams();
		$result = array();
        if (isset($data['order_id']) && isset($data['oos'])) {
            try {
                $order = Mage::getModel('sales/order')->load($data['order_id']);
                $order->setOosStatus($data['oos']);
                $order->save();
                //System/Configuration/Mageworx/Order Management/Custom Order Flag
                $isremoveDependencyOnOrderTable = Mage::getStoreConfig('mageworx_ordersmanagement/customorderflag/enabled');
                if( $isremoveDependencyOnOrderTable ) {
                    $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load($data['order_id']);
                    $mageworx->setOosStatus($data['oos']);
                    $mageworx->save();
                }
                $result['msg'] = $this->__('OOS Status has been updated.');
            } catch (Exception $e) {
                 $result['msg'] = $this->__($e->getMessage());
            }
        }
        else
			$result['msg'] = $this->__("Order not found.");
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function setPfCourierAction() {
        $data = $this->getRequest()->getParams();
		$result = array();
        if (isset($data['order_id']) && isset($data['pfc'])) {
            try {
                $order = Mage::getModel('sales/order')->load($data['order_id']);
                $order->setPrefferedCourier($data['pfc']);
                $order->save();
                //System/Configuration/Mageworx/Order Management/Custom Order Flag
                $isremoveDependencyOnOrderTable = Mage::getStoreConfig('mageworx_ordersmanagement/customorderflag/enabled');
                if( $isremoveDependencyOnOrderTable ) {
                    $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load($data['order_id']);
                    $mageworx->setPrefferedCourier($data['pfc']);
                    $mageworx->save();
                }
                $result['msg'] = $this->__('Preffered Courier has been updated.');
            } catch (Exception $e) {
                $result['msg'] = $this->__($e->getMessage());
            }
        }
        else
			$result['msg'] = $this->__("Order not found.");
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function setCstFlagAction() {
        $data = $this->getRequest()->getParams();
		$result = array();
        if (isset($data['order_id']) && isset($data['csf'])) {
            try {
                $order = Mage::getModel('sales/order')->load($data['order_id']);
                $order->setCustomerFlag($data['csf']);
                $order->save();
                //System/Configuration/Mageworx/Order Management/Custom Order Flag
                $isremoveDependencyOnOrderTable = Mage::getStoreConfig('mageworx_ordersmanagement/customorderflag/enabled');
                if( $isremoveDependencyOnOrderTable ) {
                    $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load($data['order_id']);
                    $mageworx->setCustomerFlag($data['csf']);
                    $mageworx->save();
                }
                $result['msg'] = $this->__('Customer Flag has been updated.');
            } catch (Exception $e) {
				$result['msg'] = $this->__($e->getMessage());
            }
        }
		else
			$result['msg'] = $this->__("Order not found.");
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function setUpsstatusFlagAction() {
        $data = $this->getRequest()->getParams();
        $result = array();
        if (isset($data['order_id']) && isset($data['upsstatus'])) {
            try {
                $status = 0;
                $state = false;
                if( strtolower($data['upsstatus_label']) == strtolower("DELIVERED") ) {
                    $status = 1;
                    $state = true;
                }
                $order = Mage::getModel('sales/order')->load($data['order_id']);
                $order->setUpsstatus($data['upsstatus']);
                if( $state )
                    $order->setUpsstatusFlag($status);
                $order->save();

                $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load($data['order_id']);
                $mageworx->setUpsstatus($data['upsstatus']);
                if( $state )
                    $mageworx->setUpsstatusFlag($status);
                $mageworx->save();

                $upsstatuscollection = Mage::getModel('upslabel/upslabel')->getCollection()
                                ->addFieldToFilter('order_id',  array('eq'=>$data['order_id']));

                foreach( $upsstatuscollection as $uspstatus ){
                    $uspstatus->setUpsstatus( $data['upsstatus'] );
                    if( $state )
                        $uspstatus->setUpsstatusFlag($status);
                    $uspstatus->save();
                }

                $result['msg'] = $this->__('Ups Status has been updated.');
            } catch (Exception $e) {
                $result['msg'] = $this->__($e->getMessage());
            }
        }
        else
            $result['msg'] = $this->__("Order not found.");
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function setDhlstatusFlagAction() {
        $data = $this->getRequest()->getParams();
        $result = array();
        if (isset($data['order_id']) && isset($data['dhlstatus'])) {
            try {
                $status = 0;
                $state = false;
                if( strtolower($data['dhlstatus_label']) == strtolower("DELIVERED") ) {
                    $status = 1;
                    $state = true;
                }
                $order = Mage::getModel('sales/order')->load($data['order_id']);
                $order->setDhlstatus($data['dhlstatus']);
                if( $state )
                    $order->setDhlstatusFlag($status);
                $order->save();

                $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load($data['order_id']);
                $mageworx->setDhlstatus($data['dhlstatus']);
                if( $state )
                    $mageworx->setDhlstatusFlag($status);
                $mageworx->save();

                $dhlstatuscollection = Mage::getModel('dhllabel/dhllabel')->getCollection()
                    ->addFieldToFilter('order_id',  array('eq'=>$data['order_id']));

                foreach( $dhlstatuscollection as $uspstatus ){
                    $uspstatus->setDhlstatus( $data['dhlstatus'] );
                    if( $state )
                        $uspstatus->setDhlstatusFlag($status);
                    $uspstatus->save();
                }

                $result['msg'] = $this->__('Dhl Status has been updated.');
            } catch (Exception $e) {
                $result['msg'] = $this->__($e->getMessage());
            }
        }
        else
            $result['msg'] = $this->__("Order not found.");
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function setAramexstatusFlagAction() {
        $data = $this->getRequest()->getParams();
        $result = array();
        if (isset($data['order_id']) && isset($data['aramexstatus'])) {
            try {
                $status = 0;
                $state = false;
                if( strtolower($data['aramexstatus_label']) == strtolower("DELIVERED") ) {
                    $status = 1;
                    $state = true;
                }
                $order = Mage::getModel('sales/order')->load($data['order_id']);
                $order->setAramexstatus($data['aramexstatus']);
                if( $state )
                    $order->setAramexstatusFlag($status);
                $order->save();

                $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load($data['order_id']);
                $mageworx->setAramexstatus($data['aramexstatus']);
                if( $state )
                    $mageworx->setAramexstatusFlag($status);
                $mageworx->save();

                $aramexstatuscollection = Mage::getModel('customorderflags/aramexlabel')->getCollection()
                    ->addFieldToFilter('order_id',  array('eq'=>$data['order_id']));

                foreach( $aramexstatuscollection as $uspstatus ){
                    $uspstatus->setAramexstatus( $data['aramexstatus'] );
                    if( $state )
                        $uspstatus->setAramexstatusFlag($status);
                    $uspstatus->save();
                }

                $result['msg'] = $this->__('Aramex Status has been updated.');
            } catch (Exception $e) {
                $result['msg'] = $this->__($e->getMessage());
            }
        }
        else
            $result['msg'] = $this->__("Order not found.");
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function syncCustomerDataAction(){
        $collection = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('customer_flag' , array('neq'=>NULL));
        foreach( $collection as $order ){
            $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load($order->getEntityId());
            if(  !empty( $mageworx->getData() ) ){
                $mageworx->setCustomerFlag($order->getCustomerFlag());
                $mageworx->save();
            }
        }
        echo 'Customer Flag Synced Successfully.';
    }

    public function syncOosDataAction(){
        $collection = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('oos_status' , array('neq'=>NULL));
        foreach( $collection as $order ){
            $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load( $order->getEntityId() );
            if(  !empty( $mageworx->getData() ) ){
                $mageworx->setOosStatus($order->getOosStatus());
                $mageworx->save();
            }
        }
        echo 'Oos Flag Synced Successfully.';
    }

    public function syncPrefferedDataAction(){
        $collection = Mage::getModel('sales/order')
            ->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('preffered_courier' , array('neq'=>NULL));
        foreach( $collection as $order ){
            $mageworx = Mage::getModel('mageworx_ordersgrid/order_grid')->load($order->getEntityId());
            if(  !empty( $mageworx->getData() ) ){
                $mageworx->setPrefferedCourier($order->getPrefferedCourier());
                $mageworx->save();
            }
        }
        echo 'Preffered Courier Flag Synced Successfully.';
    }

    public function syncAramexDataAction(){
        try {
            $collection = Mage::getModel('sales/order_shipment_track')//carrier_code like '%aramex%'
            ->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('carrier_code', array('like' => "%aramex%"));
            foreach ($collection as $shipment) {
                $aramexCollection = Mage::getModel('customorderflags/aramexlabel')
                    ->getCollection()
                    ->addFieldToSelect('*')
                    ->addFieldToFilter('trackingnumber', array('eq' => $shipment->getTrackNumber()));

                if (empty($aramexCollection->getData())) {
                    $aramex = Mage::getModel('customorderflags/aramexlabel')->load();
                    $aramex->setTrackingnumber($shipment->getTrackNumber());
                    $aramex->setOrderId($shipment->getOrderId());
                    $aramex->setCreatedTime($shipment->getCreatedAt());
                    $aramex->setUpdateTime(date('Y-m-d H:i:s'));
                    $aramex->save();
                }
            }
        }catch (Exception $e){
            echo $e->getMessage();
        }
        echo "All Aramex Tracking are synced.";
    }
}