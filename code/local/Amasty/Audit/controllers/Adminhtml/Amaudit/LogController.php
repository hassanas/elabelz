<?php

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Audit
 */
class Amasty_Audit_Adminhtml_Amaudit_LogController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('system/amaudit');
        $this->_title($this->__('Action Log'));

        $this->_addBreadcrumb($this->__('Admin Actions Log'), $this->__('Admin Action Log'));
        $block = $this->getLayout()->createBlock('amaudit/adminhtml_userlog');
        $this->_addContent($block);
        $this->renderLayout();
    }

    public function ajaxAction()
    {
        $idItem = Mage::app()->getRequest()->getParam('idItem');
        Mage::register('current_log', Mage::getModel('amaudit/log')->load($idItem));
        $block = $this->getLayout()->createBlock('amaudit/adminhtml_userlog_edit_tab_view_details');
        $this->getResponse()->setBody($block->toHtml());
    }

    public function editAction()
    {
        $this->loadLayout();
        $entityId = (int)$this->getRequest()->getParam('id');
        $logEntity = Mage::getModel('amaudit/log')->load($entityId);

        if ($entityId && !$logEntity->getId()) {
            $this->_getSession()->addError(Mage::helper('catalog')->__('This item no longer exists.'));
            $this->_redirect('*/*/');

            return;
        }

        if (!is_null(Mage::registry('current_log'))) {
            Mage::unregister('current_log');
        }
        Mage::register('current_log', $logEntity);

        $this->_title($logEntity->getCategoryName());


        $this->_setActiveMenu('system/amaudit');
        $this->renderLayout();
    }

    public function exportCsvAction()
    {
        $fileName = 'admin-actions-log.csv';
        $content = $this->getLayout()->createBlock('amaudit/adminhtml_userlog_grid_export')
            ->getCsv()
        ;

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName = 'admin-actions-log.xml';
        $content = $this->getLayout()->createBlock('amaudit/adminhtml_userlog_grid_export')
            ->getXml()
        ;

        $this->_sendUploadResponse($fileName, $content);
    }

    public function clearAction()
    {
        $tableLog = Mage::getModel('amaudit/log');

        $tableLog->clearLog(false);

        $tableLog->addClearToLog('Action Log');

        $this->_redirect('adminhtml/amaudit_log/index');
    }

    protected function _sendUploadResponse($fileName, $content)
    {
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function restoreAction()
    {
        $id = $this->getRequest()->getParam('id');
        $log = Mage::getModel('amaudit/log')->load($id);
        $logDetailsCollection = Mage::getModel('amaudit/log_details')->getCollection();
        $logDetailsCollection->addFieldToFilter('log_id', array('in' => $id));

        $elementId = $log->getElementId();
        $elementLoaded = false;

        if ($log->getCategory() == 'admin/system_config') {
            foreach ($logDetailsCollection as $logDetail) {
                Mage::getConfig()->saveConfig($logDetail->getName(), $logDetail->getOldValue(), 'default', $log->getStoreId());
            }
        } else {
            foreach ($logDetailsCollection as $logDetail) {
                $elementKey = $logDetail->getName();
                $oldValue = $logDetail->getOldValue();
                if ($oldValue == 'is_array') {
                    continue;
                }
                $modelName = $logDetail->getModel();
                if (!$elementLoaded) {
                    $model = Mage::getModel($modelName);
                    if ($log->getStoreId() !== 0) {
                        $model->setStoreId($log->getStoreId());
                    }

                    $element = $model->load($elementId);

                    $elementLoaded = true;
                }
                if (isset($element) && $element instanceof Mage_Catalog_Model_Product) {
                    if ($elementKey == 'website_ids' || $elementKey == 'up_sell_link_data') {
                        $oldValue = explode(',', $oldValue);
                    }
                    if ($elementKey == 'up_sell_link_data') {
                        $oldValue = $this->_prepareUpSellLinkData($oldValue);
                    }
                }
                $element->setData($elementKey, $oldValue);
            }
            $element->save();
        }

        $backUrl = $this->getUrl('adminhtml/amaudit_log');
        $this->getResponse()->setRedirect($backUrl);
    }

    public function customerAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function productAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function orderAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    protected function _prepareUpSellLinkData($oldValue)
    {
        $oldValue = array_flip($oldValue);

        foreach ($oldValue as $key => $value) {
            $oldValue[$key] = array('position' => '');
        }

        return $oldValue;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/amauditmenu/log');
    }
}
