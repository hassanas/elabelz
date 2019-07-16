<?php
class Progos_Messages_Block_Messages extends Mage_Core_Block_Template {
	
    protected $seller_id="";
	public function _prepareLayout() {
        return parent::_prepareLayout();
    }
    
    public function getThraed($threadid){     
         $collection = Mage::getModel('messages/thread')->getCollection();
         $collection->getSelect()->where('thread_id = ?', $threadid);
         return $collection;
    }

     public function getActionOfForm($thread){
            $thread_data = $this->getThraed($thread);
            foreach($thread_data as $coll):
               $buyer_id = $coll['buyer_id'];
               $seller_id = $coll['seller_id'];
        endforeach; 
			return $this->getUrl('messages/index/createMessage?thread='.$thread.'&&seller_id='.$seller_id.'&&buyer_id='.$buyer_id.'');
	}
	
	public function getSeller($seller_id){
    
     	$seller_data = Mage::getModel('customer/customer')->load($seller_id);
    	return $seller_data;
    }

	 public function createThread($seller_id){
	 	    $seller_name = $this->getSeller($seller_id);
			return $this->getUrl('messages/index/createThread?seller_name='.$seller_name->getName().'&&seller_id='.$seller_name->getId().'');
	}
	

	public function loadMessages(){
       
	   $thread  = Mage::app()->getRequest()->getParam('thread');
       $collection = Mage::getModel('messages/conversation')->getCollection();
       $condition = new Zend_Db_Expr("main_table.messages_id = mes.messages_id");
       $collection->getSelect()->join(array('mes' => $collection->getTable('messages/messages')),
            $condition,
            array('message' => 'mes.message'));
       $collection->getSelect()->where('main_table.thread_id = ?', $thread);
       return $collection;
	}

	public function getUserCredentials(){
    	if (Mage::getSingleton('customer/session')->isLoggedIn()) {
		    // Get the customer object from customer session
		    $customer = Mage::getSingleton('customer/session')->getCustomer(); 

		    return $customer;
        }

    }
   
    public function getdelUrl(){
    }

};