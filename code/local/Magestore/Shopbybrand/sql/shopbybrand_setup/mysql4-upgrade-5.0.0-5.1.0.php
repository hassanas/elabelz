<?php
$installer = $this;

$installer->startSetup();

/**
 * Find and delete empty records
 */
$collection = Mage::getModel('shopbybrand/brand')->getCollection()->addFieldToFilter('name', null);
if ($collection->count() > 0) {
    foreach($collection as $item) $item->delete();
}
   
$installer->endSetup();