<?php
class Apptha_CustomMenu_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{
    
    public function indexAction()
    {   
        $this->loadLayout();
        $this->_setActiveMenu('system/partial_index_list');
        $this->_addContent($this->getLayout()->createBlock('custommenu/adminhtml_menu'));
        $this->renderLayout();
    }
    
    public function buildMenuAction()
    {
        try {
            Mage::helper('custommenu')->buildMenu();
            $this->_getSession()->addSuccess(Mage::helper('adminhtml')->__("Menu Build Successfully."));
            $this->_redirect('*/*');
        } catch (Exception $ex) {
            $this->_getSession()->addError($ex->getMessage());
            $this->_redirect('*/*/');
        }
    }
}
