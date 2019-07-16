<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */

class Infomodus_Dhllabel_Block_Adminhtml_Lists extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_lists';
        $this->_blockGroup = 'dhllabel';
        $this->_headerText = Mage::helper('dhllabel')->__('DHL Shipping Labels');
        parent::__construct();
        $this->_removeButton('add');
    }
}