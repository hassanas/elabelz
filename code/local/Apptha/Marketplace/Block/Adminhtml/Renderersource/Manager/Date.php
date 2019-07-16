<?php
/*
* Description : Get Buyer Confirmation Date
* Date : 05-07-2017
* Author : Saroop
*/
class Apptha_Marketplace_Block_Adminhtml_Renderersource_Manager_Date extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    /**
     * Return the Date of the buyer confirmation.
     * @return date
     */
    public function render(Varien_Object $row) {
        $orderId = $row->getData($this->getColumn()->getIndex());

        $ordersItems = Mage::getModel ( 'marketplace/commission' )->getCollection ()
            ->addFieldToSelect ( 'is_buyer_confirmation_date' )
            ->addFieldToFilter ( 'order_id', array ('eq' => $orderId))
            ->setOrder ( 'created_at', 'DESC' );

            $result ="";
            // for csvExport only return status string instead of html
            if($this->getRequest()->getActionName()=="exportCsv"){
                if($ordersItems->getSize()){
                    foreach($ordersItems as $item){
                        if( $item['is_buyer_confirmation_date'] == "0000-00-00 00:00:00" ){
                            return '0000-00-00';
                        }else{
                            $date = date_create($item['is_buyer_confirmation_date']);
                            return date_format($date,"Y-m-d");
                        }
                    }
                }
            }
    }
}
