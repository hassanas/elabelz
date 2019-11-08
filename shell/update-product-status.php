<?php 

/*
fetch simple products 
All simle products who's qty = 0 should be out of stock status  
is_in_stock=0(out of stock), =1 (in stock)
Case 1:
is_in_stock : yes
qty = 0
status:enable
Set the Status of these all products out of stock qty
*/
error_reporting( E_ALL );
require_once ('app/Mage.php'); 
Mage::app();

$collection = Mage::getResourceModel('catalog/product_collection')
    ->addAttributeToFilter('type_id', array('eq' => 'simple'))
    ->addAttributeToFilter('is_in_stock', array('eq' => 1))
    ->addAttributeToFilter('qty', array('eq' => 0));
	->addAttributeToSelect('*'); //or just the attributes you need
$collection->getSelect()->joinLeft(array('link_table' => 'catalog_product_super_link'),
    'link_table.product_id = e.entity_id',
    array('product_id')
);
$collection->getSelect()->where('link_table.product_id IS NULL');

foreach ($collection as $product) {
     //do something with $product
	echo  '--<br>'.$product->getId();
}
?>
