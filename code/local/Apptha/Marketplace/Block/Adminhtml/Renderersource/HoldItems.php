<?php

/**
 * Progos
 * 
 *
 * 
*/

class Apptha_Marketplace_Block_Adminhtml_Renderersource_HoldItems extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {

        $collections = Mage::getModel('marketplace/commission')->getCollection()
                        ->addFieldToFilter('product_id',$row->getData($this->getColumn()->getProductid()))
                        ->addFieldToFilter('order_status','pending')
                        ->addFieldToFilter('item_order_status',array('neq'=>'canceled'))
                        ->addFieldToFilter('is_buyer_confirmation','No');
        $total = 0;
        foreach($collections as $collection){
            $total = $total+(integer)$collection->getProductQty();
        }

        return '<span style="color: red;">'.$total.'</span>';

    }

}

