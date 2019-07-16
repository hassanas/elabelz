<?php
class Support_Messaging_Block_Conversation extends Mage_Core_Block_Template {

	protected function _construct() {
		$thread_id = $this->getRequest()->getParam("id");
		$is_seller = Mage::helper("messaging")->isSeller();
		$current_user = Mage::helper("messaging")->getSession();

        $thread = Mage::getModel("messaging/thread")->load($thread_id);
        $for = $thread->getFor();
        $from = $thread->getFrom();
        $ban = false;
		if ($is_seller && ($current_user->getCustomerId() != $for)) {
			$ban = true;
		} else {
			if (!$is_seller && ($current_user->getCustomerId() != $from)) {
				$ban = true;

			}
		}

		if ($ban) {
		    Mage::getSingleton('core/session')->addError($this->__("Conversation not found") );
		    $url = Mage::getUrl('messaging/history/');
		    Mage::app()->getFrontController()->getResponse()->setRedirect($url);
		}

		$this->conversations();
	}

  //   protected function _prepareLayout() {
		// parent::_prepareLayout();
		// $collection = $this->conversations();
		// $this->setCollection($collection);
		// return $this;
  //   }

    public function conversations() {

		if ($this->getRequest()->isPost()) {

			$attachment_id = null;
			$message = $this->getRequest()->getPost("msg");
			$thread_id = $this->getRequest()->getParam("id");


			$message = array(
				'message' => $message
			);
			$add_message = Mage::getModel('messaging/messages')->setData($message);
			try {
				$insert_id_message = $add_message->save()->getId();
			} catch (Exception $e){
				echo $e->getMessage();   
			}



			if (isset($_FILES['attachment']['name']) && $_FILES['attachment']['name'] != '') {
				// echo $_FILES['attachment']['name'];
                try { 
					// $fileName       = $_FILES['attachment']['name'];
					// $fileExt        = strtolower(substr(strrchr($fileName, ".") ,1));
					// $fileNamewoe    = rtrim($fileName, $fileExt);
					// $fileName       = preg_replace('/\s+', '', $fileNamewoe) . time() . '.' . $fileExt;
                    $uploader = new Varien_File_Uploader('attachment');
                    $uploader->setAllowedExtensions(array('jpg', 'jpeg','png','pdf','zip','docx'));
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(false);
                    $path = Mage::getBaseDir('media') . DS . 'messages' . DS ;
                    if(!is_dir($path)){
                        mkdir($path, 0777, true);
                    }
                    $uploader->save($path, $_FILES['attachment']['name'] );             
                    $file_name = $uploader->getUploadedFileName();

					$attachment = array(
						'file_name' => $file_name,
						'message_id' => $insert_id_message
					);
					$add_attachment = Mage::getModel('messaging/attachments')->setData($attachment);
					try {
						$insert_id_attachment = $add_attachment->save()->getId();
					} catch (Exception $e){
						echo $e->getMessage();   
					}

                } catch (Exception $e) {
                    $error = true;
                }

            }

			$model = Mage::getModel("messaging/thread")->load($thread_id);
			$for = $model->getFor();
			$from = $model->getFrom();

			if (Mage::helper("messaging")->isSeller()) {
				$thread_u = array(
					'read_from' => 0,
					'last_activity' => time()
				);
			} else {
				$thread_u = array(
					'read_for' => 0,
					'last_activity' => time()
				);
			}
			$model->setData($thread_u)->setId($thread_id)->save();


			$conversation = array(
				'thread_id'    => $thread_id,
				'seller_id'    => $for,
				'buyer_id'     => $from,
				'message_id'   => $insert_id_message,
				'message_from' => Mage::helper("messaging")->getSession("frontend")->getCustomerId()
			);
			$add_to_conversation = Mage::getModel('messaging/conversation')->setData($conversation);
			try {
				$add_to_conversation_insert_id = $add_to_conversation->save()->getId();
			    Mage::getSingleton('core/session')->addSuccess($this->__("Message sent!") );
			    $url = Mage::getUrl ('messaging/history/show/id/'.$thread_id);
			    Mage::app()->getFrontController()->getResponse()->setRedirect($url);
			} catch (Exception $e){
				echo $e->getMessage();   
			}
		}

		// $id = $this->getRequest()->getParam("id");
		// $collection = Mage::getModel('messaging/conversation')->getCollection();
		// $collection->addFieldToFilter("thread_id", $id);
		// $collection->setOrder("date_time", "asc");
		// return $collection;

	}

	public function updateRead() {
		$thread_id = $this->getRequest()->getParam("id");
		if (Mage::helper("messaging")->isSeller()) {
			$thread = array(
				'read_for' => 1,
				'read_for_time' => time()
			);
		} else {
			$thread = array(
				'read_from' => 1,
				'read_from_time' => time()
			);
		}
		$update_thread = Mage::getModel('messaging/thread')->load($thread_id)->setData($thread)->setId($thread_id)->save();
	}

	public function getMessages() {
		$id = $this->getRequest()->getParam("id");
        $thread = Mage::getModel("messaging/thread")->getCollection()
                ->addFieldToFilter('id', $id)
                ->getFirstItem();
		$users = [];
		$users[$thread->getFor()]["id"] = $thread->getFor();
		$users[$thread->getFor()]["name"] = Mage::helper("messaging")->getCustomer($thread->getFor())->getName();
		$users[$thread->getFor()]["type"] = "Seller";
		$users[$thread->getFrom()]["id"] = $thread->getFrom();
		$users[$thread->getFrom()]["name"] = Mage::helper("messaging")->getCustomer($thread->getFrom())->getName();
		$users[$thread->getFrom()]["type"] = "Buyer";

		$conversation = Mage::getModel('messaging/conversation')->getCollection();
		$conversation->addFieldToFilter("thread_id", $id);
		$conversation->setOrder("date_time", "asc");
		$get_message_ids = [];
		foreach ($conversation as $row) {
			$get_message_ids[] = $row->getMessageId();
		}



		$messages = Mage::getModel('messaging/messages')->getCollection()
		->addFieldToFilter("id", array("in"=>$get_message_ids));
		$get_messages_from_id = [];
		foreach ($messages as $message) {
			$get_messages_from_id[$message->getId()] = $message->getMessage();
		}

		$attachments = Mage::getModel('messaging/attachments')->getCollection()
		->addFieldToFilter("message_id", array("in"=>$get_message_ids));
		$get_attachments_from_messages_id = [];
		foreach ($attachments as $attachment) {
			$get_attachments_from_messages_id[$attachment->getMessageId()]["id"] = $attachment->getId();
			$get_attachments_from_messages_id[$attachment->getMessageId()]["file"] = $attachment->getFileName();
			$get_attachments_from_messages_id[$attachment->getMessageId()]["date_time"] = $attachment->getDateTime();
		}


		$output = [];
		$temp = [];
		foreach ($conversation as $crow) {
			$temp["message"] = $get_messages_from_id[$crow->getMessageId()];
			if (array_key_exists($crow->getMessageId(), $get_attachments_from_messages_id)) {
				$temp["attachment"] = $get_attachments_from_messages_id[$crow->getMessageId()]?$get_attachments_from_messages_id[$crow->getMessageId()]:null;
			} else {
				$temp["attachment"] = false;
			}
			$temp["from"] = $users[$crow->getMessageFrom()];
			$temp["timestamp"] = $crow->getDateTime();
			$output[] = $temp;
		}

		$final = [];
		$final["thread"] = [
			"subject"=>$thread->getName(),
			"for"=> $users[$thread->getFor()],
			"from"=> $users[$thread->getFrom()],
			"lastseen_for"=> $thread->getReadForTime(),
			"lastseen_from"=> $thread->getReadFromTime()
		];
		$final["data"] = $output;
		return $final;
	}



}