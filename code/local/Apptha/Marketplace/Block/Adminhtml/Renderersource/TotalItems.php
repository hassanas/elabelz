<?php

/**
 * Progos
 * 
 *
 * 
*/

class Apptha_Marketplace_Block_Adminhtml_Renderersource_TotalItems extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
         $magentoQty = (integer) $row->getData($this->getColumn()->getMagentoqty());
        $collections = Mage::getModel('marketplace/commission')->getCollection()
                        ->addFieldToFilter('product_id',$row->getData($this->getColumn()->getProductid()))
                        ->addFieldToFilter('order_status','pending')
                        ->addFieldToFilter('item_order_status',array('neq'=>'canceled'))
                        ->addFieldToFilter('is_buyer_confirmation','No');
        $collectionCount = 0;
        foreach($collections as $collection){
            $collectionCount = $collectionCount+(integer)$collection->getProductQty();
        }
       $total = $magentoQty + $collectionCount;
        return '<span style="color:green;">'.$total.'</span>';;
    }

}

