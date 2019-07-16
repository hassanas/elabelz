<?php
class Support_Messaging_SendController extends Mage_Core_Controller_Front_Action {
	
	protected function _getSession() {
		Mage::getSingleton('core/session', array('name'=>$this->_sessionNamespace));
		return Mage::getSingleton('customer/session');
	}
	
	protected function _construct() {
		if(!$this->_getSession()->isLoggedIn()){
			Mage::getSingleton('core/session')->addError($this->__( 'You must login to access this page'));
			$this->_redirect('customer/account/login');
			return;
	    }
	}
    
    public function indexAction() {
        // $this->loadLayout();
        // $this->renderLayout();
        if ($this->getRequest()->isPost()) {
        	$thread_id = $this->getRequest()->getParam("id");
        	$for = $this->getRequest()->getPost("for");
        	// $from = $this->getRequest()->getPost("from");
        	$from = Mage::helper("messaging")->getSession()->getCustomerId();
        	$message_from = $this->getRequest()->getPost("message_from");
        	$subject = $this->getRequest()->getPost("subject");
        	$message = $this->getRequest()->getPost("message");
        	$url = $this->getRequest()->getPost("ref");
			
	        // var_dump($this->getRequest()->getPost());

			$thread = Mage::getModel("messaging/thread")->load($from, "from");
			$existing = $thread->getData()?true:false;
			// if ($thread->getData()) {
				if (!$existing) {
					$thread = array(
						'name' => $subject,
						'for' => $for,
						'from' => $from,
						'read_from_time' => time(),
						'read_for' => 0,
						'read_from' => 1
					);
					$create_thread = Mage::getModel('messaging/thread')->setData($thread);
					try {
						$thread_id = $create_thread->save()->getId();
					} catch (Exception $e){
						echo $e->getMessage();   
					}
				} else {
					$thread = array(
						'read_for' => 0,
						'read_from_time' => time(),
					);
					$update_thread = Mage::getModel('messaging/thread')->load($thread_id)->setData($thread)->setId($thread_id)->save();
				}


				$message = array(
					'message' => $message
				);
				$add_message = Mage::getModel('messaging/messages')->setData($message);
				try {
					$insert_id_message = $add_message->save()->getId();
				} catch (Exception $e){
					echo $e->getMessage();   
				}



				$conversation = array(
					'thread_id'    => $thread_id,
					'status'       => 1,
					'seller_id'    => $for,
					'buyer_id'     => $from,
					'message_id'   => $insert_id_message,
					'message_from' => $from
				);
				$add_to_conversation = Mage::getModel('messaging/conversation')->setData($conversation);
				try {
					$add_to_conversation_insert_id = $add_to_conversation->save()->getId();
				    Mage::getSingleton('core/session')->addSuccess($this->__("Message sent!") );
				    $url = Mage::getUrl("messaging/history/show/id/".$thread_id);
				    Mage::app()->getFrontController()->getResponse()->setRedirect($url);
				} catch (Exception $e){
					echo $e->getMessage();   
				}

			// } else {

			// }
			
        }
    }

}