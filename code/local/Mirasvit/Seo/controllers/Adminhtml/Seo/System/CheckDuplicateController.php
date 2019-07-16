<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */



class Mirasvit_Seo_Adminhtml_Seo_System_CheckDuplicateController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('seo');
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system')
            ->_addBreadcrumb(Mage::helper('seo')->__('Check Duplicate'), Mage::helper('seo')->__('Check Duplicate'));

        return $this;
    }

    public function indexAction()
    {
        Mage::getSingleton('adminhtml/session')->addNotice(Mage::helper('seo/help')->getDuplicateInfo());
        $this->_title($this->__('Check Duplicate'));

        $this->_initAction();
        $this->_addContent($this->getLayout()->createBlock('seo/adminhtml_checkDuplicate_check'));

        $this->renderLayout();
    }
}