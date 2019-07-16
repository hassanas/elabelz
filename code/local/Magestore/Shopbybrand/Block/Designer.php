<?php
class Magestore_Shopbybrand_Block_Designer extends Mage_Core_Block_Template 
{
	public function getdesignerCollection(){
        $store = Mage::app()->getStore();
		$brandProductCollection = Mage::getResourceModel('shopbybrand/brand_collection');
				$brandProductCollection->addFieldToFilter('is_designer', 1)
                ->setStoreId($store->getId())
                ->setOrder('position_brand','ASC');
				
            return $brandProductCollection;
	}

}
?>