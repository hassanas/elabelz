<?php
        
        require_once('../app/Mage.php');
        umask(0);

        Mage::app();
        $orderId = ( int ) Mage::app ()->getRequest ()->getParam (780);
        $creditmemo = $observer->getEvent ()->getCreditmemo ();
        $items = $creditmemo->getAllItems ();
        foreach ( $items as $item ) {        	
        	$itemsArr = array();
        	$itemsArr[] = $item;        
        	Mage::getModel('marketplace/order')->updateOrderStatusForSellerItems($itemsArr,$orderId);
        	
            $getProductIdValue = $item->getProductId ();
            /**
             * Gettings commission information in database table
             */
            $commissions = Mage::getModel ( 'marketplace/commission' )->getCollection ()->addFieldToFilter ( 'order_id', $orderId )->addFieldToFilter ( 'product_id', $getProductIdValue )->addFieldToSelect ( 'id' )->addFieldToSelect ( 'product_qty' );
            foreach ( $commissions as $commission ) {
                $commissionId = $commission->getId ();
                $commissionQty = $commission->getProductQty ();
                $qty = $commissionQty - $item->getQty ();
                $sellerId = $commission->getSellerId ();
                $orderPrice = $item->getPrice () * $qty;
                /**
                 * Gettings seller information in database table
                 */
                $sellerCollection = Mage::helper ( 'marketplace/marketplace' )->getSellerCollection ( $sellerId );
                $percentperproduct = $sellerCollection ['commission'];
                /**
                 * Calculate admin commission Fee
                 */
                $commissionFee = $orderPrice * ($percentperproduct / 100);
                $sellerAmount = $orderPrice - $commissionFee;
                /**
                 * Check whether seller amount is empty
                 * if it is assign status is
                 * else status is 1
                 */
                if (empty ( $sellerAmount )) {
                    $status = 0;
                } else {
                    $status = 1;
                }
                /**
                 * update commission information in database table
                 */
                if (! empty ( $commissionId )) {
                    $Data = array (
                            'product_qty' => $qty,
                            'commission_fee' => $commissionFee,
                            'seller_amount' => $sellerAmount,
                            'status' => $status 
                    );
                    /**
                     * Save Commission Data
                     */
                    Mage::helper ( 'marketplace/transaction' )->saveCommissionData ( $Data, $commissionId );
                }
            }
        }