<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Block_Adminhtml_Conformity_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('conformity_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('dhllabel')->__('DHL conformity information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label'     => Mage::helper('dhllabel')->__('Conformity Information'),
            'title'     => Mage::helper('dhllabel')->__('Conformity Information'),
            'content'   => $this->getLayout()->createBlock('dhllabel/adminhtml_conformity_edit_tab_form')->toHtml(),
        ));

        return parent::_beforeToHtml();
    }
}