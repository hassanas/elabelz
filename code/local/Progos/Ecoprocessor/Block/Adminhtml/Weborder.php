<?php

/**
 * This Module is created to complete the orders from Web
 * @category     Progos
 * @package      Progos_Ecoprocessor
 * @copyright    Progos TechCopyright (c) 13-02-2018
 * @author       Saroop Chand
 *
 */
class Progos_Ecoprocessor_Block_Adminhtml_Weborder extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * constructor
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_weborder';
        $this->_blockGroup = 'ecoprocessor';
        parent::__construct();
        $this->_removeButton('add');
        $this->_headerText = Mage::helper('ecoprocessor')->__('Web Orders');

    }
}
