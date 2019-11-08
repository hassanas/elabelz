<?php

    error_reporting( E_ALL );
   require_once ('app/Mage.php'); 
      Mage::app();
       
$collections = Mage::getModel('customer/customer')
        ->getCollection()
        ->addAttributeToSelect('*')
        ->addFieldToFilter('group_id', 4);
foreach($collections as $collection){
        
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
   $passphrase = "elabelz123";
   $salt = "SC";
   $customer_id= $collection->getId();
   $password = md5($salt . $passphrase) . ":SC";
 
   $write->query("update customer_entity_varchar set value='$password' where entity_id=$customer_id and attribute_id in (select attribute_id from eav_attribute where attribute_code='password_hash' and entity_type_id=1)");
  echo  '<br>'.$collection->getId();
}  ?>