<?php

class Apptha_Marketplace_Block_Adminhtml_Renderersource_Call_Order_Order extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        
        $orderId = $row->getData($this->getColumn()->getIndex());
        $image = Mage::getDesign()->getSkinBaseUrl(array('_area'=>'adminhtml')).'images/';
        $orderData = $row->getData();
        $ordersItems = Mage::getModel ( 'marketplace/commission' )->getCollection ()
                ->addFieldToSelect ( '*' )
                ->addFieldToFilter ( 'order_id', array ('eq' => $orderId))
                ->setOrder ( 'created_at', 'DESC' );
        $status = array(
            "pending"                       => "Pending Confirmation",
            "holded"                        => "On Hold",
            "processing"                    => "Processing",
            "shipped_from_elabelz"          => "Shipped from Elabelz",
            "successful_delivery_partially" => "Successful Delivery Partially",
            "failed_delivery"               => "Failed Delivery",
            "successful_delivery"           => "Successful Delivery",
            "complete"                      => "Completed Non Refundable",
            "refunded"                      => "Refunded",
            "closed"                        => "Closed",
            "canceled"                      => "Canceled"
        );
        $finalStatus = $status[$orderData["status"]];
        $result =  "<ul><li>".$orderData['increment_id']." (<strong>{$finalStatus}</strong>)</li>";
        $url = Mage::helper('adminhtml')->getUrl('marketplaceadmin/adminhtml_callcenter/comments/', array('order_id'=>$value));
        /*
        foreach($ordersItems as $item) {

            $_product = Mage::getModel("catalog/product")->load($item->getProductId());
            try {
                $image =  Mage::helper('catalog/image')->init($_product, 'small_image')->resize(75, 75);
            } catch(Exception $e) {
                $image =  "";
            }

            $no_selection = $item->getIsBuyerConfirmation()=='No'?'selected="selected"':'';
            $yes_selection = $item->getIsBuyerConfirmation()=='Yes'?'selected="selected"':'';
            $reject_selection = $item->getIsBuyerConfirmation()=='Rejected'?'selected="selected"':'';


            $el = "item_status_change_".$item->getId();
            // $result .=  '<li class="right-arrow">'.$item['id'].'<select style="padding: 2px; width: 100px; margin-bottom: 3px; float: right"><option>Adnan</option></select></li>';
            $result .=  "<li class='pbox'><img src='{$image}' width='50' height='50' align='left' />
            <span style='display: block; padding-left: 55px'>{$_product->getName()}</span>
            <select style='padding: 2px; width: 100px; margin: 0 0 0 5px' id='{$el}' onchange='updateItemStatus(\"$el\", \"$el\");'>
                <option value='No' {$no_selection}>Pending</option>
                <option value='Yes' {$yes_selection}>Confirmed</option>
                <option value='Rejected' {$reject_selection}>Rejected</option>
            </select><div style='clear: both'></div></li>";
        }*/
        $result .= '</ul>';
        // $result .= "<div style='clear: both; height: 10px'></div><div onclick='openPopupForm(\"{$url}\");' class='pbox-call scalable back'>Log Attempt</div>";
        $order = Mage::getModel('sales/order')->load($orderId);
        $history = $order->getStatusHistoryCollection()->getFirstItem();
        if (strlen($history->getComment())) {
            $result .= "<div class='order-comment'>" . $history->getComment() . "</div>";
        }
        return $result;
    }

}

