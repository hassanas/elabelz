<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Audit
 */
class Amasty_Audit_Adminhtml_Amaudit_LoginController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction() 
    {
        $this->loadLayout();
        $this->_setActiveMenu('system/amaudit');
        $this->_title($this->__('Login Attempts'));
               
        $this->_addBreadcrumb($this->__('Admin Actions Log'), $this->__('Login Attempts'));
        $block = $this->getLayout()->createBlock('amaudit/adminhtml_auditlog');
        $this->_addContent($block);
        $this->renderLayout();
    }
    
    public function clearlockAction() 
	{
	   try
        {
             $lockModel = Mage::getModel('amaudit/lock');
             $collection = $lockModel->getCollection();
             foreach($collection as $item){
                 $count = $item->getCount();
                 if($count >= Mage::getStoreConfig('amaudit/login/numberFailed')){
                     $user = Mage::getModel('amaudit/lock')->load($item->getEntityId());
                     if($user){
                        $user->setData('count', 0); 
                        $user->save();      
                     }    
                 }
                 
             }
        }
        catch (Exception $e) 
        {
            $session = Mage::getSingleton('adminhtml/session');
            $session->addException(Mage::helper('adminhtml')->__('Remove failed!'));
            Mage::logException($e);
        }
        $this->_redirect('adminhtml/system_config/edit', array('section' => 'amaudit'));
	}

    public function clearAction()
    {
        $table = Mage::getSingleton('core/resource')->getTableName('amaudit/data');

        Mage::getSingleton('core/resource')
            ->getConnection('core_write')
            ->truncate($table);

        Mage::getModel('amaudit/log')->addClearToLog('Login Attempts');

        $this->_redirect('adminhtml/amaudit_login/index');
    }

    public function exportCsvAction()
    {
        $fileName   = 'admin-login-attempts.csv';
        $content    = $this->getLayout()->createBlock('amaudit/adminhtml_auditlog_grid_export')
            ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'admin-login-attempts.xml';
        $content    = $this->getLayout()->createBlock('amaudit/adminhtml_auditlog_grid_export')
            ->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

    protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $this->_prepareDownloadResponse($fileName, $content);
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('system/amauditmenu/amaudit');
    }
}
