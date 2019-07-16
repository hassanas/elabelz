<?php
/**
 * MageWorx
 * Admin Order Grid  extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersGrid
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersGrid_Adminhtml_Mageworx_OrdersgridController extends Mage_Adminhtml_Controller_Action
{
    public function columnsOrderAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->getMwHelper()->__('Manage Grid Columns'));
        $this->_addContent($this->getLayout()->createBlock('mageworx_ordersgrid/adminhtml_column_edit'))
            ->_addLeft($this->getLayout()->createBlock('mageworx_ordersgrid/adminhtml_column_edit_tabs'));
        $this->renderLayout();
    }

    public function resetColumnsAction()
    {
        $helper = $this->getMwHelper();

        Mage::getSingleton('mageworx_ordersgrid/grid')->initColumnOrder($helper::XML_GRID_TYPE_ORDER);
        Mage::getSingleton('mageworx_ordersgrid/grid')->initColumns($helper::XML_GRID_TYPE_ORDER);

        Mage::getSingleton('mageworx_ordersgrid/grid')->initColumnOrder($helper::XML_GRID_TYPE_CUSTOMER);
        Mage::getSingleton('mageworx_ordersgrid/grid')->initColumns($helper::XML_GRID_TYPE_CUSTOMER);

        $this->_getSession()->addSuccess($this->getMwHelper()->__('The settings were reset successfully'));
        $this->_redirectReferer();
    }

    public function saveColumnsOrderAction()
    {
        if (!$this->_validateFormKey()) {
            $this->_redirectReferer();
            return;
        }

        $helper = $this->getMwHelper();
        $post = $this->getRequest()->getPost();
        if (isset($post['form_key'])) {
            unset($post['form_key']);
        }

        $this->saveColumnSettings($post[$helper::XML_GRID_TYPE_ORDER], $helper::XML_GRID_TYPE_ORDER);
        $this->saveColumnSettings($post[$helper::XML_GRID_TYPE_CUSTOMER], $helper::XML_GRID_TYPE_CUSTOMER);

        $this->_getSession()->addSuccess($this->getMwHelper()->__('The settings were saved successfully'));

        if ($this->getRequest()->getParam('back')) {
            $this->_redirectReferer();
        } else {
            $this->_redirect('adminhtml/system_config/edit/section/mageworx_ordersmanagement');
            return;
        }
    }

    public function syncAction()
    {
        try {
            /** @var MageWorx_OrdersGrid_Model_Order_Grid */
            Mage::getModel('mageworx_ordersgrid/order_grid')->syncAllOrders();
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
     * Archive selected orders
     */
    public function massArchiveAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());
        $count = $this->getMwHelper()->addToOrderGroup($orderIds, 1);
        if ($count > 0) {
            Mage::getSingleton('adminhtml/session')->addSuccess($this->getMwHelper()->__('Selected orders were archived.'));
        }
        $this->_redirect('adminhtml/sales_order/');
    }

    /**
     * Delete selected orders
     */
    public function massDeleteAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());
        $count = $this->getMwHelper()->addToOrderGroup($orderIds, 2);
        if ($count > 0) {
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
     * Restore selected orders
     */
    public function massRestoreAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());
        $count = $this->getMwHelper()->addToOrderGroup($orderIds, 0);
        if ($count > 0) {
            Mage::getSingleton('adminhtml/session')->addSuccess($this->getMwHelper()->__('Selected orders were restored.'));
        }
        $this->_redirect('adminhtml/sales_order/');
    }

    /**
     * Create invoices for selected orders
     */
    public function massInvoiceAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());
        $count = $this->getMwHelper()->invoiceOrderMass($orderIds);
        if ($count > 0) {
            Mage::getSingleton('adminhtml/session')->addSuccess($this->getMwHelper()->__('Selected orders were invoiced.'));
        }
        $this->_redirect('adminhtml/sales_order/');
    }

    /**
     * Create shipments for selected orders
     */
    public function massShipAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());
        $count = $this->getMwHelper()->shipOrder($orderIds);
        if ($count > 0) {
            Mage::getSingleton('adminhtml/session')->addSuccess($this->getMwHelper()->__('Selected orders were shipped.'));
        }
        $this->_redirect('adminhtml/sales_order/');
    }

    /**
     * Create both invoice and shipment for selected orders
     */
    public function massInvoiceAndShipAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());
        $count = $this->getMwHelper()->invoiceOrderMass($orderIds);
        $count += $this->getMwHelper()->shipOrder($orderIds);
        if ($count > 0) {
            Mage::getSingleton('adminhtml/session')->addSuccess($this->getMwHelper()->__('Selected orders were invoiced and shipped.'));
        }
        $this->_redirect('adminhtml/sales_order/');
    }

    /**
     * Delete all invoices and shipments for selected orders
     */
    public function deleteInvoiceAndShipmentAction()
    {
        $orderId = intval($this->getRequest()->getParam('order_id'));
        if ($orderId > 0) {
            Mage::getResourceModel('mageworx_ordersgrid/order')->deleteInvoiceAndShipment($orderId);
        }
        $this->getResponse()->setBody('ok');
    }

    /**
     * Invoice and print selected orders
     * @return Mage_Core_Controller_Varien_Action
     */
    public function massInvoiceAndPrintAction()
    {
        $orderIds = $this->getRequest()->getPost('order_ids', array());
        $count = $this->getMwHelper()->invoiceOrderMass($orderIds);

        $flag = false;
        if (!empty($orderIds)) {
            foreach ($orderIds as $orderId) {
                $invoices = Mage::getResourceModel('sales/order_invoice_collection')
                    ->setOrderFilter($orderId)
                    ->load();
                if ($invoices->getSize() > 0) {
                    $flag = true;
                    if (!isset($pdf)){
                        $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
                    } else {
                        $pages = Mage::getModel('sales/order_pdf_invoice')->getPdf($invoices);
                        $pdf->pages = array_merge ($pdf->pages, $pages->pages);
                    }
                }
            }
            if ($flag) {
                return $this->_prepareDownloadResponse(
                    'invoice'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').'.pdf', $pdf->render(),
                    'application/pdf'
                );
            } else {
                $this->_getSession()->addError($this->__('There are no printable documents related to selected orders.'));
                $this->_redirect('adminhtml/sales_order/');
            }
        }

        if ($count > 0) {
            Mage::getSingleton('adminhtml/session')->addSuccess($this->getMwHelper()->__('Selected orders were invoiced and printed.'));
        }
        $this->_redirect('adminhtml/sales_order/');
    }

    public function saveCustomerSortOrderAction()
    {
        if (!$this->_validateFormKey() || !$this->getRequest()->isAjax()) {
            $this->_redirectReferer();
            return;
        }

        $post = $this->getRequest()->getPost();
        if (isset($post['form_key'])) {
            unset($post['form_key']);
        }

        $result = $this->getMwHelper()->saveSortOrderConfig('customer', $post);
        $this->getResponse()->setBody(intval($result));
    }

    public function saveSortOrderAction()
    {
        if (!$this->_validateFormKey() || !$this->getRequest()->isAjax()) {
            $this->_redirectReferer();
            return;
        }

        $post = $this->getRequest()->getPost();
        if (isset($post['form_key'])) {
            unset($post['form_key']);
        }

        $result = $this->getMwHelper()->saveSortOrderConfig('grid', $post);
        $this->getResponse()->setBody(intval($result));
    }

    protected function saveColumnSettings($data, $type)
    {
        $gridColumns = array();
        $sortOrder = array();
        foreach($data as $key => $values){
            $sortOrder[$key] = $values['sortOrder'];
            if (isset($values['enabled']) && $values['enabled'] == 'on') {
                $gridColumns[] = $key;
            }
        }

        $this->getMwHelper()->saveSortOrderConfig($type, $sortOrder);
        $this->getMwHelper()->saveColumnsConfig($type, implode(',',$gridColumns));
    }

    /**
     * @return MageWorx_OrdersGrid_Helper_Data
     */
    protected function getMwHelper()
    {
        return Mage::helper('mageworx_ordersgrid');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/mageworx_ordersgrid');
    }
}