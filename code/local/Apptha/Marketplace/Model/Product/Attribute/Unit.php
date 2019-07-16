<?php 
 class Apptha_Marketplace_Model_Product_Attribute_Unit extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
    {
      public function getAllOptions()
      {
        $model = Mage::getModel('customer/customer');
        $collection = $model->getCollection();
        $condition = new Zend_Db_Expr("market.seller_id = e.entity_id AND e.is_active = 1");
        $collection->getSelect()->join(array('market' => $collection->getTable('marketplace/sellerprofile')),
        $condition,
        array('store_title' => 'market.store_title'));
        $customerArr = array();
        $customerArr[] = array('value' => '0','label' => 'Select');
        foreach($collection as $customer)
        {
            $customerArr[] = array(
                           'value' => $customer->getId(),
                           'label' => $customer['store_title'],
                            );
        }
        if (!$this->_options) {
            $this->_options = $customerArr;
        }
        return $this->_options;
      }
    }
  