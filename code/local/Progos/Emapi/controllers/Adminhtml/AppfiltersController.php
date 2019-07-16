<?php

/**
 * @category       Progos
 * @package        Progos_Emapi
 * @copyright      Progos Tech (c) 2018
 * @Author         Hassan Ali Shahzad
 * @date           27-04-2018 18:23
 */
class Progos_Emapi_Adminhtml_AppfiltersController extends Mage_Adminhtml_Controller_Action
{

    /*
     * For patch SUPEE-6285 mandatory for custom modules
     *
     * */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('marketplace/emapi');
    }


    /**
     *
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_title($this->__("New App Filters Warm Up Cache"));
        $this->renderLayout();
    }

    /**
     *  This function will re-write json files for categories filters for Mobile App
     *
     */
    public function warmupAction()
    {

        if ($this->getRequest()->isPost()) {
            try {
                $categories = $this->getRequest()->getPost('categories');
                $logs = $this->getRequest()->getPost('logs');

                if(empty($categories)){
                    Mage::getModel('emapi/filters')->runAppFilters($logs);
                }
                else{
                    $targetedCat = array_map('intval', explode(',',$categories));
                    Mage::getModel('emapi/filters')->runAppFilters($logs,$targetedCat);
                }
                Mage::getSingleton('adminhtml/session')->addSuccess($this->_getHelper()->__('App Filters Cache Updated.'));

            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($this->_getHelper()->__('Invalid Warn Up Attempt'));
            }

        }
        $this->_redirect('*/*/index');
    }
}