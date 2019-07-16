<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Block_Adminhtml_Conformity_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        /*multistore*/
        $store = $this->getRequest()->getParam('store', 0);
        /*multistore*/
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset(
            'conformity_form',
            array('legend' => Mage::helper('dhllabel')->__('Conformity information'))
        );

        $fieldset->addField(
            'method_id', 'select', array(
                'name' => 'method_id',
                'label' => Mage::helper('dhllabel')->__('Shipping Method in Checkout'),
                'title' => Mage::helper('dhllabel')->__('Shipping Method in Checkout'),
                'required' => true,
                'values' => Mage::getModel('dhllabel/config_dhlmethod')->getShippingMethods(),
            )
        );

        $fieldset->addField(
            'dhlmethod_id', 'select', array(
                'name' => 'dhlmethod_id',
                'label' => Mage::helper('dhllabel')->__('DHL Shipping Service for labels'),
                'title' => Mage::helper('dhllabel')->__('DHL Shipping Service for labels'),
                'required' => true,
                'values' => Mage::getModel('dhllabel/config_dhlmethod')->toOptionArray(),
            )
        );

        $fieldset->addField(
            'country_ids', 'multiselect', array(
                'name' => 'country_ids',
                'label' => Mage::helper('dhllabel')->__('Allowed Countries'),
                'title' => Mage::helper('dhllabel')->__('Allowed Countries'),
                'required' => true,
                'values' => Mage::getModel('adminhtml/system_config_source_country')->toOptionArray(),
            )
        );

        /*multistore*/
        $fieldset->addField(
            'store_id', 'select', array(
                'name' => 'store_id',
                'label' => Mage::helper('dhllabel')->__('Apply to Store'),
                'value' => $store,
                'options' => Mage::helper('dhllabel/help')->getStores(),
            )
        );
        /*multistore*/

        if (Mage::getSingleton('adminhtml/session')->getAccountData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getAccountData());
            Mage::getSingleton('adminhtml/session')->setAccountData(null);
        } elseif (Mage::registry('conformity_data')
            && count(Mage::registry('conformity_data')->getData()) > 0) {
            $data = Mage::registry('conformity_data')->getData();
            $form->setValues($data);
        }

        return parent::_prepareForm();
    }
}