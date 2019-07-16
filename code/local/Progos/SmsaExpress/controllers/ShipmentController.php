<?php 
	class Progos_SmsaExpress_ShipmentController extends Mage_Adminhtml_Controller_Action
	{
	
	const XML_PATH_TRANS_IDENTITY_EMAIL  = 'trans_email/ident_general/email';
	const XML_PATH_TRANS_IDENTITY_NAME   = 'trans_email/ident_general/name';
	const XML_PATH_SHIPMENT_EMAIL_TEMPLATE = 'aramexsettings/template/shipment_template';
	const XML_PATH_SHIPMENT_EMAIL_COPY_TO     = 'aramexsettings/template/copy_to';
	const XML_PATH_SHIPMENT_EMAIL_COPY_METHOD = 'aramexsettings/template/copy_method';
	
		protected function _isAllowed()
		{
				return true;
		}
		public function postAction()
		{
			$post = $this->getRequest()->getPost();
			$orderId = $post['smsaexpress_shipment_original_reference'];
			$order = Mage::getModel("sales/order")->loadByIncrementId( $orderId );
			if( ! $order->hasInvoices() ){
              	Mage::getSingleton('core/session')->addError('Please create invoice before Shipment.');
              	$this->_redirectUrl($post['smsaexpress_shipment_referer']);
              	return;
            }

			try{
				if( empty($post) ){
					Mage::throwException($this->__('Invalid form data.'));
				}

				if( true ){ // If plugin is active / inactive.
					$model = Mage::getModel('progos_smsaexpress/SmsaExpress');
					$response = $model->createShipment( $post );
					if( $response['status'] ){
						Mage::getSingleton('core/session')->addSuccess('Smsa Express Shipment Number: '.$response['shipmentId'].' has been created.');

						//Email Portion will be call here.

						$this->_redirectUrl($post['smsaexpress_shipment_referer']);
					}else{
						$formSession=Mage::getSingleton('adminhtml/session');
						$formSession->setData("form_data",$post);
						$strip=strstr($post['smsaexpress_shipment_referer'],"smsaexpresspopup",true);
						$url=$strip;
						if(empty($strip)){
							$url=$post['smsaexpress_shipment_referer'];
						}					
						$this->_redirectUrl($url . 'smsaexpresspopup/show');
					}
				}
			}catch( Exception $e ){
				$formSession=Mage::getSingleton('adminhtml/session');
				$formSession->setData("form_data",$post);
				$strip=strstr($post['smsaexpress_shipment_referer'],"smsaexpresspopup",true);
				$url=$strip;
				if(empty($strip)){
					$url=$post['smsaexpress_shipment_referer'];
				}					
				$this->_redirectUrl($url . 'smsaexpresspopup/show');
			}
		}
		
		public function printLabelAction(){
			$helper = Mage::helper('progos_smsaexpress');
			$previuosUrl = Mage::getSingleton('core/session')->getPreviousUrl();
			$orderId = $this->getRequest()->getParam('order_id');
			$_order = Mage::getModel('sales/order')->load($this->getRequest()->getParam('order_id'));
			try{
				$shipmentDetail = Mage::getModel('sales/order_shipment_track')
					->getCollection()
					->addFieldToSelect('*')
					->addFieldToFilter('order_id', $orderId);
				$shipmentDetail = $shipmentDetail->getData();
				if( !empty( $shipmentDetail ) ){
					if( $shipmentDetail[0]['carrier_code'] == 'smsaexpress' ){
						$url        = $helper->getApiUrl(); //Dynamics Request
						$client     = new SoapClient($url, array("trace" => 1, "exception" => 0));
						try{
							$params = array(); 
							$params['passkey'] = $helper->getApiKey();
							$params['awbNo'] = $shipmentDetail[0]['track_number'];
							$filepath = $client->getPDF( $params );

		                    $name="{$_order->getIncrementId()}-shipment-label.pdf";
		                    header('Expires: 0'); // no cache
		                    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		                    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
		                    header('Cache-Control: private', false);
		                    header('Content-Type: application/force-download');
		                    header('Content-Disposition: attachment; filename="' . $name . '"');
		                    header('Content-Transfer-Encoding: binary');
		                    header('Content-Length: ' . strlen($filepath->getPDFResult)); // provide file size
		                    header('Connection: close');
		                    echo $filepath->getPDFResult;
		                    exit();
						}catch( Exception $printLabel ){
							Mage::getSingleton('adminhtml/session')->addError($printLabel->getMessage());
							$this->_redirectUrl($previuosUrl);
						}	
					}else{
						Mage::throwException($this->__('Smsa Express Not used for Shipment'));
						Mage::getSingleton('adminhtml/session')->addError($this->__('Smsa Express Not used for Shipment.'));
						$this->_redirectUrl($previuosUrl);
					}
				}else{
					Mage::getSingleton('adminhtml/session')->addError($this->__('Shipment is empty or not created yet.'));
						$this->_redirectUrl($previuosUrl);
				}
			}catch( Exception $e ){
					Mage::getSingleton('adminhtml/session')->addError($this->__('Shipment is empty or not created yet.'));
					$this->_redirectUrl($previuosUrl);
			}
		}
		
	}
?>