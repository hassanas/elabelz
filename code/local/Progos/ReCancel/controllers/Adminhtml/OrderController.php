<?php

class Progos_ReCancel_Adminhtml_OrderController extends Mage_Adminhtml_Controller_Action
{
	public function recancelAction()
	{
		$Id = $this->getRequest()->getParam('id');
		$order = Mage::getModel('sales/order')->load($Id);
		if($order->getId()){
            $allItemsApproved = Mage::helper('marketplace/marketplace')->approved_items($order->getId());
                /*
                 *  if all items are approved before uncancelling the status will be confirmed
                */
                $order->setData('state','new')
                    ->setData('status',$allItemsApproved);
                $order_status = $allItemsApproved;

            /*
             * unsetting order cancel data
            */
			$order->setData('base_discount_canceled',0)
					->setData('base_shipping_canceled',0)
					->setData('base_subtotal_canceled',0)
					->setData('base_tax_canceled',0)
					->setData('base_total_canceled',0)
					->setData('discount_canceled',0)
					->setData('shipping_canceled',0)
					->setData('subtotal_canceled',0)
					->setData('tax_canceled',0)
					->setData('total_canceled',0)
					;

            //reverting items information

            /*
             * Getting marketplace order data for rejected items
             */

            $marketplace_order = Mage::getModel('marketplace/commission')->getCollection();
            $marketplace_order->addFieldToSelect(array('product_id','is_seller_confirmation'));
            $marketplace_order->addFieldToFilter('order_id',$order->getId());

            $rejectedItemsArray=array();

            foreach($marketplace_order as $marketplace){
                if($marketplace->getIsSellerConfirmation() == "Rejected"){
                    $product = Mage::getModel('catalog/product')->load($marketplace->getProductId());
                    /*
                     * adding rejected item skus in rejectedItemsArray()
                     */
                    $rejectedItemsArray[] = $product->getSku();
                }
            }
            /*
             * Getting all items of order
             */


			$items = $order->getItemsCollection();
			$newProArray=array();
			foreach($items as $item) {
			    if(!in_array($item->getSku(), $rejectedItemsArray)) {
                    /*
                     * Changing back only those items whose sku is not avilable in rejectedItemsArray()
                     */
                    $product_id = $item->getProductId();
                    $item->setData('qty_canceled', 0);

                    $orderPrice = $item->getBasePrice() * $item->getQtyOrdered();
                    $products = Mage::helper('marketplace/marketplace')->getProductInfo($product_id);

                    $sellerId = $products->getSellerId();
                    $sku = $item->getSku();
                    $product_id = Mage::getModel('catalog/product')->getIdBySku($sku);
                    $products = Mage::getModel('catalog/product')->load($product_id);
                    if ($products->getTypeID() != 'configurable' && !in_array($product_id, $newProArray)):
                        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($products);
                        if ($stockItem->getId() > 0 and $stockItem->getManageStock()) { // checking manage stock is  enable
                            $qty = $stockItem->getQty() - $item->getQtyOrdered();
                            $stockItem->setQty($qty);
                            $stockItem->save();
                        }
                        $sellerCollection = Mage::helper('marketplace/marketplace')->getSellerCollection($sellerId);
                        /*
                         * adding commision fee back to items on marketplace grid
                        */
                        $sellerShippingEnabled = Mage::getStoreConfig('carriers/apptha/active');
                        if ($sellerShippingEnabled == 1 && $products->getTypeID() == 'simple') {
                            /**
                             * Get the product national shipping price
                             * and international shipping price
                             * and shipping country
                             */
                            $nationalShippingPrice = $products->getNationalShippingPrice();
                            $internationalShippingPrice = $products->getInternationalShippingPrice();
                            $sellerDefaultCountry = $products->getDefaultCountry();
                            $shippingCountryId = $order->getShippingAddress()->getCountry();
                        }

                        $shippingPrice = Mage::helper('marketplace/market')->getShippingPrice($sellerDefaultCountry, $shippingCountryId, $orderPrice, $nationalShippingPrice, $internationalShippingPrice, $productQty);
                        /**
                         * Getting seller commission percent
                         */
                        $sellerCollection = Mage::helper('marketplace/marketplace')->getSellerCollection($sellerId);
                        $percentperproduct = $sellerCollection ['commission'];

                        $commissionFee = $orderPrice * ($percentperproduct / 100);
                        $sellerAmount = $shippingPrice - $commissionFee;
                        $qty_ordered = $item->getQtyOrdered();
                        $data = array('order_status' => $order_status, 'product_qty' => $qty_ordered, 'seller_amount' => $sellerAmount, 'commission_fee' => $commissionFee);
                        $products = Mage::getModel('marketplace/commission')->getCollection();
                        $products->addFieldToSelect(array('id'));
                        $products->addFieldToFilter('order_id', $order->getId());
                        $products->addFieldToFilter('product_id', $product_id);
                        if ($products) {
                            foreach ($products as $product):
                                $id = $product->getId();
                            endforeach;
                            $model = mage::getmodel('marketplace/commission')->load($id);
                            $model->addData($data);
                            $model->save();
                        }
                    endif;
                    $newProArray[] = $product_id;
                }
			}
			try{
				$items->save();
				$order->save();
				$this->_getSession()->addSuccess(
                    $this->__('The order has been unCancelled successfully.')
                );
			}catch(Exception $ex){
				Mage::log('Error uncancel order: '.$ex->getMessage());
				$this->_getSession()->addError($this->__('The order can not been unCancelled.'));


			}
			$this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));


		}
		$this->_redirectReferer();

	}
    protected function _isAllowed()
    {
        return true;
    }

}
