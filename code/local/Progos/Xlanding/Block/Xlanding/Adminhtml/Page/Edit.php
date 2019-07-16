<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Xlanding
 */
class Progos_Xlanding_Block_Xlanding_Adminhtml_Page_Edit extends Amasty_Xlanding_Block_Adminhtml_Page_Edit
{
    public function __construct()
    {
        if (Mage::getStoreConfig('amlanding/xlanding/merchandising')) {
            $this->_addButton('autosort', array(
                'label' => Mage::helper('xlanding')->__('AutoSort'),
                'onclick' => "setLocation('{$this->getUrl('admin_xlanding/adminhtml_xlanding/autosort', array('_current' => true))}')",
                'class' => 'save'
            ), 10);
        }

        parent::__construct();
    }


}