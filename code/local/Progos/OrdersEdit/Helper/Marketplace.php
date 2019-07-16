<?php
/**
 * Progos
 * Admin Order Editor extension
 *
 * @category   Progos
 * @package    Progos_OrdersEdit
 * @author     Saroop
 * @changes    Line # 114-134 ( To update the Previous Row instead of adding new row in marketplace_commistion table for change color/size if config product )
 */
class Progos_OrdersEdit_Helper_Marketplace extends Apptha_Marketplace_Helper_Marketplace {
    public function addOrderItem($child,$qty,$newProduct , $cancledProductArray = array() ){
        $sellerDefaultCountry = '';
        $nationalShippingPrice = $internationalShippingPrice = 0;
        $order = Mage::getModel('sales/order')->load($child->getOrderId());
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $getCustomerId = $customer->getId();
        $grandTotal = $order->getGrandTotal();
        $status = $order->getStatus();
        $shippingCountryId = '';
        $getProductId = $newProduct->getId();
        $createdAt = $order->getCreatedAt();
        $paymentMethodCode = $order->getPayment()->getMethodInstance()->getCode();
        $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')
            ->getParentIdsByChild($newProduct->getId());
        $parent_item = Mage::getModel('catalog/product')->load($parentIds[0]);
        $products = Mage::helper('marketplace/marketplace')->getProductInfo($parent_item->getId());
        $products_new = Mage::getModel('catalog/product')->load($newProduct->getId());

        $sellerId = $products->getSellerId();

        $productType = $products->getTypeID();

        $sellerShippingEnabled = Mage::getStoreConfig('carriers/apptha/active');

        if ($sellerShippingEnabled == 1 && $productType == 'simple') {
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

        if ($sellerId) {

            $orderPrice = $products->getPrice() * $qty;

            $productAmt = $products->getPrice();

            $productQty = $qty;

            $is_buyer_confirmation = 'No';
            $item_order_status = 'pending';
            $shippingPrice = Mage::helper('marketplace/market')->getShippingPrice($sellerDefaultCountry, $shippingCountryId, $orderPrice, $nationalShippingPrice, $internationalShippingPrice, $productQty);


            /**
             * Getting seller commission percent
             */

            $sellerCollection = Mage::helper('marketplace/marketplace')->getSellerCollection($sellerId);
            $percentperproduct = $sellerCollection ['commission'];
            $commissionFee = $orderPrice * ($percentperproduct / 100);

            $sellerAmount = $shippingPrice - $commissionFee;

            if($newProduct->getProductType() == 'simple')
            {
                $getProductId = $newProduct->getProductId();
            }

            /**
             * Storing commission information in database table
             */
            if ($commissionFee >= 0 || $sellerAmount >= 0) {

                if ($products->getSpecialPrice()) {
                    $orderPrice_sp = $products->getSpecialPrice() * $qty;
                    $orderPrice_base = $products->getPrice() * $qty;

                    $commissionFee = $orderPrice_sp * ($percentperproduct / 100);
                    $sellerAmount = $orderPrice_sp - $commissionFee;
                } else {
                    $orderPrice_base = $products->getPrice() * $qty;
                    $commissionFee = $orderPrice_base * ($percentperproduct / 100);
                    $sellerAmount = $shippingPrice - $commissionFee;
                }
                $commissionDataArr = array(
                    'seller_id' => $sellerId,
                    'product_id' => $getProductId,
                    'product_qty' => $productQty,
                    'product_amt' => $productAmt,
                    'commission_fee' => $commissionFee,
                    'seller_amount' => $sellerAmount,
                    'order_id' => $order->getId(),
                    'increment_id' => $order->getIncrementId(),
                    'order_total' => $grandTotal,
                    'order_status' => $status,
                    'credited' => $credited,
                    'customer_id' => $getCustomerId,
                    'status' => 1,
                    'created_at' => $createdAt,
                    'payment_method' => $paymentMethodCode,
                    'item_order_status' => $item_order_status,
                    'is_buyer_confirmation' => $is_buyer_confirmation,
                    'sms_verify_code' => $data,
                    'commission_percentage' => $sellerCollection ['commission']
                );

                if( !empty($cancledProductArray) ){
                    $commissionDataArr = $cancledProductArray[0];
                    $commissionDataArr['seller_id'] = $sellerId;
                    $commissionDataArr['product_id'] = $getProductId;
                    $commissionDataArr['product_qty'] = $productQty;
                    $commissionDataArr['product_amt'] = $productAmt;
                    $commissionDataArr['commission_fee'] = $commissionFee;
                    $commissionDataArr['seller_amount'] = $sellerAmount ;
                    $commissionDataArr['order_id'] = $order->getId();
                    $commissionDataArr['increment_id'] = $order->getIncrementId();
                    $commissionDataArr['order_total'] = $grandTotal;
                    $commissionDataArr['customer_id'] = $getCustomerId;
                    $model = mage::getmodel('marketplace/commission')->load($commissionDataArr['id']);
                    $model->addData($commissionDataArr);
                    $model->setId($commissionDataArr['id'])->save();
                }else{
                    $commissionId = $this->storeCommissionConfiguredData($commissionDataArr);
                    return $commissionId;
                }
            }
        }
    }
}