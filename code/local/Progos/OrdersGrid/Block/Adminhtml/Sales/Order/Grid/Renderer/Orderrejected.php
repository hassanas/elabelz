<?php

/**
 * Progos_OrdersGrid
 * Rewrite Extension
 *
 * @category   Progos
 * @package    Progos_OrdersGrid
 * @copyright  Copyright (c) 2017 Elabelz (https://www.elabelz.com)
 * @author     Humaira Batool (humaira.batool@progos.org)
 * @created_at 24/03/2017
 * @reason     For adding new column in ordersgrid (failed delivery, sms verify check) 
 */

class Progos_OrdersGrid_Block_Adminhtml_Sales_Order_Grid_Renderer_Orderrejected extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());  
        $orders= Mage::getModel ( 'sales/order' )->getCollection ()
        ->addFieldToSelect ( '*' )
        ->addFieldToFilter ('increment_id',$value)->getFirstItem();
        $value = $orders->getFailedDelivery();
        if($value == 1):
            $circle = 'failed'; $msg = "Failed";
        elseif($value == 2):
            $circle = 'success'; $msg = "Success";
        elseif($value == 3):
            $circle = 'warning'; $msg = "";
        else:
            $circle = ''; $msg = "";
        endif;

        $result = "<div class='circ_outer_status ".$circle."'>".$msg."</div>";
        
        return $result;
    }
}