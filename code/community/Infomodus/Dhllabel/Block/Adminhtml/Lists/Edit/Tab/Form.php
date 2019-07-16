<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Block_Adminhtml_Lists_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('lists_form', array('legend' => Mage::helper('dhllabel')->__('Labels information')));

        if (Mage::getSingleton('adminhtml/session')->getAccountData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getAccountData());
            Mage::getSingleton('adminhtml/session')->setAccountData(null);
        } elseif (Mage::registry('dhllabel_data') && count(Mage::registry('dhllabel_data')->getData()) > 0) {
            $data = Mage::registry('dhllabel_data')->getData();
            $form->setValues($data);
        }
        return parent::_prepareForm();
    }
}