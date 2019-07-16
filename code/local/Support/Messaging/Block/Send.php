<?php
class Support_Messaging_Block_Send extends Mage_Core_Block_Template {
 
    public function isMessageToSellerEnabled($seller_id) {
        $collection = Mage::getModel ('marketplace/sellerprofile')->load($seller_id,'seller_id');
        return $collection->getShowProfile ();
    }

    public function loadByMultiple($for, $from){
        $collection = $this->getCollection()
                ->addFieldToFilter('for', $for)
                ->addFieldToFilter('from', $from);
        return $collection->getFirstItem();
    }
}