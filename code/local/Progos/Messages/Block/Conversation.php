<?php
class Progos_Messages_Block_Conversation extends Mage_Core_Block_Template {
	
	public function _prepareLayout() {
        return parent::_prepareLayout();
    }

    public function getUserCredentials(){
    	if (Mage::getSingleton('customer/session')->isLoggedIn()) {
		    // Get the customer object from customer session
		    $customer = Mage::getSingleton('customer/session')->getCustomer(); 

		    return $customer;
        }

    }

    public function loadbuyerConversation(){

       $user = $this->getUserCredentials();
       $collection = Mage::getModel('messages/conversation')->getCollection();
       $collection->getSelect()->where('buyer_id = ?', $user->getId() );
       return $collection;

    }

    public function loadbuyerThread(){
       $user = $this->getUserCredentials();
       $collection = Mage::getModel('messages/conversation')->getCollection();
       $condition = new Zend_Db_Expr("main_table.thread_id = th.thread_id AND th.delete_by_buyer = 0");
       $collection->getSelect()->join(array('th' => $collection->getTable('messages/thread')),
            $condition,
            array('threadid' => 'th.thread_id' , 'threadname' => 'th.name'));
       $collection->getSelect()->where('main_table.buyer_id = ?', $user->getId() );
       return $collection;

    }

    public function loadbuyerThreadnew(){
       $user = $this->getUserCredentials();
       $collection = Mage::getModel('messages/thread')->getCollection();
       $collection->getSelect()->where('buyer_id = ?', $user->getId() );
       return $collection;

    }

    public function loadsellerThread(){
       $user = $this->getUserCredentials();
       $collection = Mage::getModel('messages/conversation')->getCollection();
       $condition = new Zend_Db_Expr("main_table.thread_id = th.thread_id AND th.delete_by_seller = 0 ");
       $collection->getSelect()->join(array('th' => $collection->getTable('messages/thread')),
            $condition,
            array('threadid' => 'th.thread_id' , 'threadname' => 'th.name'));
       $collection->getSelect()->where('main_table.seller_id = ?', $user->getId() );
       return $collection;

    }

    public function loadsellerThreadnew(){
       $user = $this->getUserCredentials();
       $collection = Mage::getModel('messages/thread')->getCollection();
       $collection->getSelect()->where('seller_id = ?', $user->getId() );
       return $collection;

    }

    public function getnewUrl($threadid){
       
       return $this->getUrl('messages/index/messages?thread='.$threadid.'');
    }

    
    
    public function getdelbuyer($threadid){ 
       
      return $this->getUrl('messages/index/deletebuyerMessage?thread='.$threadid.'');
    }

    public function getdelseller($threadid){ 
        return $this->getUrl('messages/index/deletesellerMessage?thread='.$threadid.'');
    }

    public function showunreadbuyerMessages(){
    $user = $this->getUserCredentials();
    $collection = Mage::getModel('messages/conversation')->getCollection();
    $collection->addFieldToFilter('read_status','no');
    $collection->addFieldToFilter('buyer_id', $user->getId());
        //->load();
        return $collection;
  }
  
  public function showunreadsellerMessages(){
    $user = $this->getUserCredentials();
    $collection = Mage::getModel('messages/conversation')->getCollection();
    $collection->addFieldToFilter('read_status','no');
    $collection->addFieldToFilter('seller_id', $user->getId());
        //->load();
        return $collection;
  }

  // public function addMessageLink()
  //   {
  //       if ($parentBlock = $this->getParentBlock()) {
  //           $count = $this->helper('checkout/cart')->getSummaryCount();

  //           if( $count == 1 ) {
  //               $text = $this->__('My Cart (%s item)', $count);
  //           } elseif( $count > 0 ) {
  //               $text = $this->__('My Cart (%s items)', $count);
  //           } else {
  //               $text = $this->__('My Cart');
  //           }

  //           $parentBlock->addLink($text, 'checkout/cart', $text, true, array(), 50, null, 'class="top-link-cart"');
  //       }
  //       return $this;
  //   }
}