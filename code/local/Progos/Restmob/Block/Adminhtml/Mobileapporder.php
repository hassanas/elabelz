<?php

/**
 * This Module is created to complete the orders from App
 * working on Eid
 * @category     Progos
 * @package      Progos_Restmob
 * @copyright    Progos TechCopyright (c) 01-09-201
 * @author       Hassan Ali Shahzad
 *
 */
class Progos_Restmob_Block_Adminhtml_Mobileapporder extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * constructor
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_mobileapporder'; // This is responsible to create and call Grid thats why I change it adminhtml_apporders to adminhtml_mobileapporder, Asolan Grid walay folder ka name b controller per hona chahia tha jo responsible hy grid ka but jaldi ma asa kia gia hy will change in free time
        $this->_blockGroup = 'restmob';
        parent::__construct();
        $this->_removeButton('add');
        $this->_headerText = Mage::helper('restmob')->__('Mobile App Orders');

    }
}
