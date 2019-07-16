<?php
class Progos_DirectAccess_Model_Automateorder extends Mage_Core_Model_Abstract
{

    public function process( $order_increment_id ){
        //$order = Mage::getModel('sales/order')->load($order_increment_id, 'increment_id');
        $result = array();
        $order = Mage::getModel("sales/order")->loadByIncrementId($order_increment_id);
        if( $order->getId() ) {
            $order_id = $order->getEntityId();
            if( $order->getStatus() != 'processing' ) {
                $result = $this->acceptCustomerSeller($order, $order_increment_id);
                if ($result['status']) {
                    $result = $this->invoice($order_increment_id, $order);
                }
            }else{
                $result['status'] = false;
                $result['msg'] = $order_increment_id.' Order already processed.';
                $this->addLog( $order_increment_id.' Order already processed.' );
            }
        }else{
            $result['status'] = false;
            $result['msg'] = $order_increment_id.' Invalid Order';
            $this->addLog( $order_increment_id.' Invalid Order' );
        }
        return $result ;
    }

    public function addLog($log){
        Mage::log( $log , null,'automateorder.log' );
        return;
    }

    public function acceptCustomerSeller( $order , $order_increment_id ){
        $result = array();
        try{
            $buyerStatus = $this->getBuyerStatus();
            $order_id =  $order->getEntityId();
            $marketplace_collection = Mage::getModel("marketplace/commission")->getCollection()
                ->addFieldToSelect(array('id','is_buyer_confirmation','order_id','product_id'))
                ->addFieldToFilter('order_id',$order_id)
                ->addFieldToFilter('item_order_status', array('neq' => 'canceled'))
                ->addFieldToFilter('order_status',array('neq'=>'canceled'));

            if($marketplace_collection->count() > 0) {
                $currentDateTime = date('Y-m-d H:i:s');
                foreach ($marketplace_collection as $marketplace) {
                    $model = Mage::getModel('marketplace/commission')->load($marketplace->getId());

                    if( $buyerStatus ) {
                        if ($model->getIsBuyerConfirmation() != 'Yes') {
                            $model->setIsBuyerConfirmation('Yes')
                                ->setIsBuyerConfirmationDate($currentDateTime);
                        }
                    }

                    if( $model->getIsSellerConfirmation() != 'Yes' ){
                        $model->setIsSellerConfirmationDate($currentDateTime)
                            ->setIsSellerConfirmation('Yes');
                    }
                    $model->setItemOrderStatus('ready')
                        ->save();
                    $model->save();
                }
                // Add buyer product confirmation comment to order
                $comment = "All Order Items are <strong>accepted</strong> by buyer And Supplier Using Automatic Order Process. ";
                $order->addStatusHistoryComment($comment, $order->getStatus())
                    ->setIsVisibleOnFront(0)
                    ->setIsCustomerNotified(0);
                $order->save();
                // Set 'Pending Supplier Confirmation' order status
                Mage::helper('orderstatuses')->setOrderStatusConfirmed($order);
                //sending email to seller
                if( $buyerStatus ) {
                    Mage::helper('marketplace/marketplace')->successAfter($order_id);
                }
                $result['status'] = true;
            }else{
                $result['status'] = false;
                $this->addLog( $order_increment_id ." May be all items are already processed. If not please try again." );
                $result['msg'] = $order_increment_id ." Order is not new. Its status already changed.";
            }
        }catch( Exception $e ){
            $this->addLog( $order_increment_id ." Error on Buyer And Seller Acceptance.".$e->getMessage() );
            $result['status'] = false;
            $result['msg'] = $order_increment_id ." Error on Buyer And Seller Acceptance.";
        }
        return $result;
    }

	public function invoice( $order_id  , $order ){
        try {
            if(!$order->canInvoice()){
                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice. May be its already created.'));
            }
            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

            if (!$invoice->getTotalQty()) {
                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
            }

            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();

            $comment = "Invoice <strong>created</strong> with Automatic Order Process. ";
            $order->addStatusHistoryComment($comment, $order->getStatus())
                ->setIsVisibleOnFront(0)
                ->setIsCustomerNotified(0);
            $order->save();
            usleep(500000);
            Mage::getResourceModel('mageworx_ordersgrid/order_grid')->syncOrders( array( $order->getEntityId() ),true );

            $result['status'] = false;
            $result['msg'] = 'Invoice Created';
        }catch (Mage_Core_Exception $e) {
            $this->addLog( $order->getIncrementId() ." Error on Buyer And Seller Acceptance.".$e->getMessage() );
            $result['status'] = false;
            $result['msg'] = $e->getMessage();
        }
        return $result;
    }

    public function getBuyerStatus(){
        $status = Mage::getStoreConfig('directaccess/automate/buyer');
        if( $status == '1' )
            $status = true;
        else
            $status = false;
        return $status;
    }
}
