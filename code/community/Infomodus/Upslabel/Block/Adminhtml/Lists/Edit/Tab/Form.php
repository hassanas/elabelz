<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Upslabel_Block_Adminhtml_Lists_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('lists_form', array('legend' => Mage::helper('upslabel')->__('Interim information')));
        /*$orders = Mage::getModel('sales/order')->getCollection()->setOrder('created_at', 'DESC')->setPageSize(500)->setCurPage(1);
        $arrOrders = array();
        foreach ($orders as $order) {
            $arrOrders[$order->getIncrementId()] = $order->getIncrementId();
        }*/
        $fieldset->addField('direction', 'select', array(
            'name'      => 'direction',
            'label'     => Mage::helper('upslabel')->__('Direction'),
            'title'     => Mage::helper('upslabel')->__('Direction'),
            'values'    => array('shipment' => 'Store -> Customer', 'invert' => 'Customer -> Store', 'refund' => 'RMA (return)'),
            'required' => true
        ));

        $fieldset->addField('order_id', 'text', array(
            'name'      => 'order_id',
            'label'     => Mage::helper('upslabel')->__('Order #'),
            'title'     => Mage::helper('upslabel')->__('Order #'),
            /*'values'    => $arrOrders,*/
            'required' => true
        ));

        if (Mage::getSingleton('adminhtml/session')->getAccountData()) {
            $form->setValues(Mage::getSingleton('adminhtml/session')->getAccountData());
            Mage::getSingleton('adminhtml/session')->setAccountData(null);
        } elseif (Mage::registry('upslabel_data') && count(Mage::registry('upslabel_data')->getData()) > 0) {
            $data = Mage::registry('upslabel_data')->getData();
            $form->setValues($data);
        }
        return parent::_prepareForm();
    }
}