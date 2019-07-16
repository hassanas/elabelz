<?php
/*
NOTICE OF LICENSE

This source file is subject to the SafeMageEULA that is bundled with this package in the file LICENSE.txt.

It is also available at this URL: http://www.safemage.com/LICENSE_EULA.txt

Copyright (c)  SafeMage (http://www.safemage.com/)
*/

include_once('Mage/Adminhtml/controllers/System/ConfigController.php');

class SafeMage_UrlOptimization_Adminhtml_Urloptimization_IndexController extends Mage_Adminhtml_System_ConfigController
{
    public function clearAction()
    {
        $limit = Mage::helper('safemage_urloptimization')->getClearLimit();
        $result = Mage::getModel('safemage_urloptimization/clear')->process($limit);

        $session = Mage::getSingleton('admin/session');
        $deletedCount = intval($session->getClearCoreUrlRewriteCount());
        $deletedCount += $result;

        if ($result > 0 && $result >= $limit) {
            $process = SafeMage_UrlOptimization_Helper_Data::CLEAR_PROCESS_WORKING;
            $session->setClearCoreUrlRewriteCount($deletedCount);
        } else {
            $process = SafeMage_UrlOptimization_Helper_Data::CLEAR_PROCESS_DONE;
            $session->setClearCoreUrlRewriteCount(0);
        }

        Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Total deleted %d records.', $deletedCount));
        if ($process == SafeMage_UrlOptimization_Helper_Data::CLEAR_PROCESS_DONE) {
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('All done.'));
        }

        $this->_redirect('*/system_config/edit', array('section' => 'safemage_urloptimization', 'process' => $process));
    }

    public function saveAction()
    {
        parent::saveAction();
        $this->_redirect('*/*/clear');
    }

    public function reindexAction()
    {
        try {
            Mage::getModel('catalog/indexer_url')->reindexAll();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Catalog URL Rewrites have been reindexed.'));

        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/system_config/edit', array('section' => 'safemage_urloptimization'));
    }

    public function restoreAction()
    {
        $result = Mage::getModel('safemage_urloptimization/restore')->process();
        Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Restored %d URL(s) in total.', $result));
        $this->_redirect('*/system_config/edit', array('section' => 'safemage_urloptimization'));
    }

    public function clearLogAction()
    {
        try {
            Mage::getModel('log/log')->clean();
            Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Log tables have been cleared.'));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('*/system_config/edit', array('section' => 'safemage_urloptimization'));
    }
}
