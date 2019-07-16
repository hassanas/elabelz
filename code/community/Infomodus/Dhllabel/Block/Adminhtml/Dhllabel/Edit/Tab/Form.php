<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Dhllabel_Block_Adminhtml_Dhllabel_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('dhllabel_form', array('legend'=>Mage::helper('dhllabel')->__('Item information')));
     
      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('dhllabel')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
      ));
		
      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('dhllabel')->__('Withdraw'),
          'name'      => 'status',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('dhllabel')->__('No'),
              ),
              array(
                  'value'     => 2,
                  'label'     => Mage::helper('dhllabel')->__('Yes'),
              ),
          ),
      ));
     
      $fieldset->addField('order_id', 'text', array(
          'name'      => 'order_id',
          'label'     => Mage::helper('dhllabel')->__('Order Id'),
          'title'     => Mage::helper('dhllabel')->__('Order Id'),
          'required'  => true,
          'readonly' => true,
      ));
      $fieldset->addField('labelname', 'text', array(
          'name'      => 'labelname',
          'label'     => Mage::helper('dhllabel')->__('Label Name'),
          'title'     => Mage::helper('dhllabel')->__('Label Name'),
          'required'  => true,
          'readonly' => true,
      ));
      $fieldset->addField('shipmentidentificationnumber', 'text', array(
          'name'      => 'shipmentidentificationnumber',
          'label'     => Mage::helper('dhllabel')->__('Shipment Identification Number'),
          'title'     => Mage::helper('dhllabel')->__('Shipment Identification Number'),
          'required'  => true,
          'readonly' => true,
      ));
      $fieldset->addField('trackingnumber', 'text', array(
          'name'      => 'trackingnumber',
          'label'     => Mage::helper('dhllabel')->__('Tracking Number'),
          'title'     => Mage::helper('dhllabel')->__('Tracking Number'),
          'required'  => true,
          'readonly' => true,
      ));
      $fieldset->addField('shipmentdigest', 'text', array(
          'name'      => 'shipmentdigest',
          'label'     => Mage::helper('dhllabel')->__('Shipment Digest'),
          'title'     => Mage::helper('dhllabel')->__('Shipment Digest'),
          'required'  => true,
          'readonly' => true,
      ));
     
      if ( Mage::getSingleton('adminhtml/session')->getDhllabelData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getDhllabelData());
          Mage::getSingleton('adminhtml/session')->setDhllabelData(null);
      } elseif ( Mage::registry('dhllabel_data') ) {
          $form->setValues(Mage::registry('dhllabel_data')->getData());
      }
      return parent::_prepareForm();
  }
}