<?php
/**
 * Progos_OrdersEdit
 *
 * @category    Progos
 * @package     Progos_OrdersEdit
 * @author      Sergejs Plisko <sergejs.plisko@redboxdigital.com>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */
?>
<?php

require_once (Mage::getModuleDir('controllers','MageWorx_OrdersGrid').DS.'Adminhtml'.DS.'Mageworx'.DS.'OrdersgridController.php');

class Progos_OrdersGrid_Adminhtml_Mageworx_OrdersgridController extends MageWorx_OrdersGrid_Adminhtml_Mageworx_OrdersgridController
{

    /**
     * This function will sync order based on given date range in MageWorx->Order Management->Admin Order Grid->Select (Past) Date:
     *
     */
    public function syncAction()
    {
        $fromDate = Mage::getStoreConfig('mageworx_ordersmanagement/ordersgrid/fromdatetosync');
        $toDate = Mage::getStoreConfig('mageworx_ordersmanagement/ordersgrid/todatetosync');
        try {
            $ordersNeedToSync = Mage::helper('progos_ordersgrid')->syncOrdersToExtendedGrid($fromDate,$toDate);
        }
        catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirectReferer();
        }
        $this->_getSession()->addSuccess($this->getMwHelper()->__('The synchronization was done successfully'));
        Mage::getModel('core/config')->saveConfig(MageWorx_OrdersGrid_Helper_Data::XML_LAST_ORDERS_SYNC, time());
        $this->_redirectReferer();
    }

    /**
     * Delete selected orders
     */
    public function massDeleteAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());

        /** Begin: Delete order from marketplace table commission */
        /** @var Progos_OrdersEdit_Model_Edit $model */
        $model = Mage::getSingleton('progos_ordersedit/edit');

        foreach ($orderIds as $id) {
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load($id);

            $model->cancelAllOrderItem($order);

            if ($order->getStatus() != 'pending' && $order->getStatus() != 'canceled') {
                Mage::getSingleton('adminhtml/session')->addError($this->__('Order Quantity cannot be updated'));
            }
        }
        /** End: Delete order from marketplace table commission */

        $count = $this->getMwHelper()->addToOrderGroup($orderIds, 2);
        if ($count > 0) {

            /** Begin: Delete order from marketplace table commission */
            $this->massDeleteMarketplace($orderIds);
            /** End: Delete order from marketplace table commission */

            Mage::getSingleton('adminhtml/session')->addSuccess($this->getMwHelper()->__('Selected orders were deleted.'));
        }
        $this->_redirect('adminhtml/sales_order/');
    }

    /**
     * Delete completely selected orders
     */
    public function massDeleteCompletelyAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());
        if (!$orderIds) {
            $orderId = $this->getRequest()->getParam('order_id', false);
            if ($orderId) {
                $orderIds = array($orderId);
            }
        }
        if ($orderIds) {
            $count = $this->getMwHelper()->deleteOrderCompletely($orderIds);

            /** Begin: Delete order from marketplace table commission */
            $this->massDeleteMarketplace($orderIds);
            /** Begin: Delete order from marketplace table commission */

            if ($count == 1) {
                Mage::getSingleton('adminhtml/session')->addSuccess($this->getMwHelper()->__('Order has been completely deleted.'));
            }
            if ($count > 1) {
                Mage::getSingleton('adminhtml/session')->addSuccess($this->getMwHelper()->__('Selected orders were completely deleted.'));
            }
        }
        $this->_redirect('adminhtml/sales_order/');
    }

    /**
     * Delete order from marketplace table commission
     *
     * @param $orderIds
     */
    public function massDeleteMarketplace($orderIds)
    {
        foreach ($orderIds as $id) {
            try {
                $collection = Mage::getModel('marketplace/commission')
                    ->getCollection()
                    ->addAttributeToSelect(array('order_id'))
                    ->addFieldToFilter('order_id', $id);
                foreach ($collection as $coll) {
                    /** @var Apptha_Marketplace_Model_Commission $coll */
                    $coll->delete();
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    /**
     * Get MageWorx OrdersGrid helper
     *
     * @return MageWorx_OrdersGrid_Helper_Data
     */
    protected function getMwHelper()
    {
        return Mage::helper('mageworx_ordersgrid');
    }
}