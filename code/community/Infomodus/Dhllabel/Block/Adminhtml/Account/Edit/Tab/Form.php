<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Block_Adminhtml_Account_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('account_form', array('legend' => Mage::helper('dhllabel')->__('Account information')));

        $fieldset->addField('companyname', 'text', array(
            'name'      => 'companyname',
            'label' => Mage::helper('dhllabel')->__('Company Name'),
            'required' => true
        ));

        $fieldset->addField('attentionname', 'text', array(
            'name'      => 'attentionname',
            'label' => Mage::helper('dhllabel')->__('Attention Name'),
            'required' => true
        ));
        $fieldset->addField('address1', 'text', array(
            'name'      => 'address1',
            'label' => Mage::helper('dhllabel')->__('Address 1'),
            'required' => true
        ));

        $fieldset->addField('address2', 'text', array(
            'name'      => 'address2',
            'label' => Mage::helper('dhllabel')->__('Address 2')
        ));

        $fieldset->addField('address3', 'text', array(
            'name'      => 'address3',
            'label' => Mage::helper('dhllabel')->__('Address 3')
        ));

        $fieldset->addField('country', 'select', array(
            'name'      => 'country',
            'label'     => Mage::helper('dhllabel')->__('Country/Territory'),
            'title'     => Mage::helper('dhllabel')->__('Country/Territory'),
            'values'    => Mage::getModel('adminhtml/system_config_source_country')->toOptionArray(),
            'required' => true
        ));

        $fieldset->addField('postalcode', 'text', array(
            'name'      => 'postalcode',
            'label' => Mage::helper('dhllabel')->__('Postal Code'),
            'required' => true
        ));

        $fieldset->addField('city', 'text', array(
            'name'      => 'city',
            'label' => Mage::helper('dhllabel')->__('City or Town'),
            'required' => true
        ));

        $fieldset->addField('province', 'text', array(
            'name'      => 'province',
            'label' => Mage::helper('dhllabel')->__('State/Province/County'),
            'required' => true
        ));

        $fieldset->addField('telephone', 'text', array(
            'name'      => 'telephone',
            'label' => Mage::helper('dhllabel')->__('Telephone'),
            'required' => true
        ));

        $fieldset->addField('fax', 'text', array(
            'name'      => 'fax',
            'label' => Mage::helper('dhllabel')->__('Fax')
        ));

        $fieldset->addField('accountnumber', 'text', array(
            'name'      => 'accountnumber',
            'label' => Mage::helper('dhllabel')->__('DHL Acct #'),
            'required' => true
        ));

        if (Mage::getSingleton('adminhtml/session')->getAccountData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getAccountData());
            Mage::getSingleton('adminhtml/session')->setAccountData(null);
        } elseif (Mage::registry('account_data') && count(Mage::registry('account_data')->getData()) > 0) {
            $data = Mage::registry('account_data')->getData();
            $form->setValues($data);
        }
        return parent::_prepareForm();
    }
}