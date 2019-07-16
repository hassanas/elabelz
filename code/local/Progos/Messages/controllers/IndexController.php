<?php
class Progos_Messages_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
            $this->loadLayout();
            $this->renderLayout();
    }

     public function messagesAction()
    {
            
            $threadid = Mage::app()->getRequest()->getParam('thread');
            $threadid =  explode( '/', $threadid ) ;
		    $threadid = intval($threadid[0]);
		    $data = array('read_status'=> 'yes'); 
		    $collection = Mage::getModel('messages/conversation')->getCollection();
		    $collection->getSelect()->where('thread_id = ?', $threadid );
		     foreach($collection as $coll):
		          $model = Mage::getModel('messages/conversation')->load($coll['conversation_id'])->addData($data);
		          try {
		          $model->setId($coll['conversation_id'])->save();    
		         } 
		         catch (Exception $e){
		          echo $e->getMessage(); 
		        }
		    endforeach;
            $this->loadLayout();  
            $this->renderLayout();
    }
  

    public function getUserCredentials(){
    	if (Mage::getSingleton('customer/session')->isLoggedIn()) {
		    // Get the customer object from customer session
		    $customer = Mage::getSingleton('customer/session')->getCustomer(); 

		    return $customer;
        }

    }

    public function createThreadAction()
		{
			$data = $this->getRequest()->getPost();
			$session = Mage::getSingleton('core/session');
			$person = Mage::getModel('messages/thread');
			$buyer= $this->getUserCredentials();
			$buyer_id = $buyer->getId();
            $buyerName = $buyer->getName();
            $sellerName = Mage::app()->getRequest()->getParam('seller_name');
            $seller_id = Mage::app()->getRequest()->getParam('seller_id');

            $date = Mage::getModel('core/date')->date('Y-m-d H:i:s');
			$person->setData('name', $buyerName.','.$sellerName);
			$person->setData('seller_id', $seller_id);
			$person->setData('buyer_id', $buyer_id);
			$person->setData('delete_by_seller', 0);
			$person->setData('delete_by_buyer', 0);
			$person->setData('date_time', $date );
			try{
			$person->save();
			$session->addSuccess('Add a message sucessfully');
			}catch(Exception $e){
			$session->addError('Add Error');
			}
			$this->_redirect('messages/index/messages?thread='.$person->getId().'');
		}

	public function createMessageAction()
		{
			
			$data = $this->getRequest()->getPost();
			$thread  = Mage::app()->getRequest()->getParam('thread');
			$sellerId  = Mage::app()->getRequest()->getParam('seller_id');
			$buyerId  = Mage::app()->getRequest()->getParam('buyer_id');
			$session = Mage::getSingleton('core/session');
			$message = Mage::getModel('messages/messages');
            $date = Mage::getModel('core/date')->date('Y-m-d H:i:s');
			$message->setData('message', $data['message']);
			try{
			$message->save();
			$session->addSuccess('Add a message sucessfully');
			}catch(Exception $e){
			$session->addError('message not added');
			}
			$conversation = Mage::getModel('messages/conversation');
			$conversation->setData('thread_id', $thread);
			$conversation->setData('messages_id', $message->getId());
            $conversation->setData('seller_id', $sellerId);
            $conversation->setData('buyer_id', $buyerId);
            $conversation->setData('read_status', 'no');
            $conversation->setData('delete_by_seller', 0);
            $conversation->setData('delete_by_buyer', 0);
            $conversation->setData('date_time', $date);
			try{
			$conversation->save();
			$session->addSuccess('Add a message successfully');
			}catch(Exception $e){
			$session->addError('Add Error');
			}

			$this->_redirect('messages/index/messages?thread='.$thread.'');
		}
        
        public function deletebuyerMessageAction()
		{
			 $threadid  = Mage::app()->getRequest()->getParam('thread');
			 $threadid =  explode( '/', $threadid ) ;
             $threadid = intval($threadid[0]);
			 $data = array('delete_by_buyer'=> 1);
		          $model = Mage::getModel('messages/thread')->load($threadid)->addData($data);
		          try {
		          $model->setId($threadid)->save();
		          echo "Data updated successfully.";    
		         } 
		         catch (Exception $e){
		          echo $e->getMessage(); 
		        }
            $this->_redirect('messages/index/index');
		}

		public function deletesellerMessageAction()
		{
			 $threadid  = Mage::app()->getRequest()->getParam('thread');
			 $threadid =  explode( '/', $threadid ) ;
             $threadid = intval($threadid[0]);
			 $data = array('delete_by_seller'=> 1);
		          $model = Mage::getModel('messages/thread')->load($threadid)->addData($data);
		          try {
		          $model->setId($threadid)->save();
		          echo "Data updated successfully.";    
		         } 
		         catch (Exception $e){
		          echo $e->getMessage(); 
		        }
            $this->_redirect('messages/index/index');
		}

	

} 