<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */

class Infomodus_Dhllabel_Block_Adminhtml_Conformity extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        /*multistore*/
        $store = $this->getRequest()->getParam('store', 0);
        /*multistore*/
        $this->_controller = 'adminhtml_conformity';
        $this->_blockGroup = 'dhllabel';
        $this->_headerText = Mage::helper('dhllabel')->__('Compliance of Methods');
        $this->_addButtonLabel = Mage::helper('dhllabel')->__('Add Conformity');

        $data = array(
            'label' =>  Mage::helper('dhllabel')->__('Add Conformity'),
            'class' => 'scalable add',
            'onclick'   => "setLocation('".
                $this->getUrl(
                    'adminhtml/dhllabel_conformity/new'/*multistore*/,
                    array('store' => $store)/*multistore*/
                )."')"
        );
        $this->addButton('conformity_add', $data, 0, 100,  'header', 'header');
        parent::__construct();
        $this->_removeButton('add');
    }
}