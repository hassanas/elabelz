<?php

class Apptha_Marketplace_Block_Adminhtml_Callcenter_Comments extends Mage_Core_Block_Template 
{
    
    public function getOrderNotes() {       
      
        $increment_id = Mage::app()->getRequest()->getParam('order_id');
        $product_id = Mage::app()->getRequest()->getParam('product_id');
        $orders = Mage::getModel('marketplace/notes')->getCollection();
        $orders->addFieldToSelect('*');
        $orders->addFieldToFilter('increment_id',$increment_id ); 
        $orders->addFieldToFilter('item_id',$product_id ); 
        /**
         * Set order for manage order
         */
        $orders->setOrder('id', 'desc');
        /**
         * Return orders
         */
        return $orders;        
    }

    protected function _prepareForm()
    {
      $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
            'method' => 'post',
            'enctype' => 'multipart/form-data'
         )
      );
 
      $form->setUseContainer(true);
      $this->setForm($form);
      return parent::_prepareForm();
  }

}

