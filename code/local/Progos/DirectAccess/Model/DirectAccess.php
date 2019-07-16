<?php
class Progos_DirectAccess_Model_DirectAccess extends Mage_Core_Model_Abstract
{
	public function generate($order_id){
		$state = '';
		$order = Mage::getModel("sales/order")->loadByIncrementId($order_id);
		if( $order->getId() ){
	        $items = Mage::getModel("marketplace/commission")->getCollection()
	        ->addFieldToFilter("increment_id",  $order_id)
	        ->addFieldToFilter(array("is_buyer_confirmation","is_seller_confirmation"),
	         array(array("eq"=>"No"),array("eq"=>"No")))
	        ->addFieldToFilter(array("item_order_status"),
	         array(array("neq"=>"canceled")));

	        $items_seller_rejected = Mage::getModel("marketplace/commission")->getCollection()
	        ->addFieldToFilter("increment_id",  $order_id)
	        ->addFieldToFilter(array("is_buyer_confirmation","is_seller_confirmation"),
	         array(array("eq"=>"Rejected"),array("eq"=>"Rejected")))      

	        ->addFieldToFilter(array("item_order_status"),
	         array(array("neq"=>"canceled")));
	        
	        if ($items->getSize()) {
	            $state = 'Cannot create invoice, confirm every order item from both customer and merchant.';
	        }else if ($items_seller_rejected->getSize()){
	            $state = 'Cannot create invoice, every rejected order item must be removed via edit order.';
	        } else {
	          $state = $this->invoice( $order_id , $order );
	        }
	    }else{
	    	$state = "Invalid Order Id.";
	    }
        return $state;
	}

	public function invoice($order_id , $order ){
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
            return "Success";
        }catch (Mage_Core_Exception $e) {
            return $e->getMessage();
        }
    }
    public function generateDestinationInvoice( $orderId , $folder ){
    	$result = array();
    	$order = Mage::getModel("sales/order")->loadByIncrementId($orderId);
		if ($order->getOrderCurrencyCode() == "AED") {
			$shipping_description = $order->getMspCashondelivery() + $order->getShippingAmount();
			$order->setShippingDescription($shipping_description);
		}else{
			$shipping_description = $order->getMspBaseCashondelivery() + $order->getBaseShippingAmount();
			$order->setShippingDescription($shipping_description);
		}
		$order->save();
		$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
		$orderData= Mage::getModel('pdfpro/order')->initOrderData($order);
		try{	
			$mainDirectory = Mage::getBaseDir().DS.'progos'.DS.'destinationInvoice'.DS.$folder;
			if( !file_exists( $mainDirectory ) ){
				mkdir($mainDirectory ,0777, true);
			}

			$fileName = $mainDirectory.DS.Mage::helper('pdfpro')->getFileName('order',$order).'.pdf';
			$result = $this->initPdf(array($orderData),'order' , $fileName );
			if($result['success']){
				return 'Success';
			}else{
				return $result['msg'];
			}
		}catch(Exception $e){
			return $e->getMessage();
		}
    }

   	/**
     * Init Pdf by given invoice data
     * @param array $invoiceData
     * @description : Method copied from VES_PdfPro_Helper_Data
     * Modified lines:
  	  - return $this->process($apiKey, $datas, $type); To
  	  - return $processor->process($apiKey, $datas, $type,$processor);
     */
	public function initPdf($datas = array(),$type='invoice' , $fileName ){
		$processorConfig 	= Mage::getStoreConfig('pdfpro/config/processor');
        $processor 			= Mage::getModel($processorConfig);
		$apiKey 			= $this->getDefaultApiKey();
		return $this->process($apiKey, $datas, $type,$processor , $fileName);
	}

	/**
     *
     * @param unknown $apiKey	=> api key
     * @param unknown $datas	=> serialize array
     * @param unknown $type		=> type(order, invoice, shipment)
     * @throws Mage_Core_Exception
     * @return multitype:boolean Ambigous <string, NULL>
     * @description : Method copied from VES_AdvancedPdfProcessor_Model_Mpdf
     * Modified lines:
  	  - $this->getInfo($apiKey) To $processor->getInfo($apiKey)
  	  - if($type == 'all') return $this->processAllPdf($datas,$apiKey); To 
  	 	if($type == 'all') return $processor->processAllPdf($datas,$apiKey);
  	  - $pdfInfo 	= $this->getInfo($tmpData['key']); To
  	  	$pdfInfo 	= $processor->getInfo($tmpData['key']);
  	  - $content = $mpdf->Output('', 'S'); To
  	  	$content = $mpdf->Output(Mage::getBaseDir().DS.'pdf1/test.pdf', 'F');
  	  - return array('success'=>true,'content' => $content); To
  	  	return array('success'=>true);
     */
    public function process($apiKey, $datas, $type , $processor , $fileName ){
        //get config tax
        $config = Mage::helper('advancedpdfprocessor')->getTaxDisplayConfig();

        /*Get API Key information*/
        $apiKeyInfo = $processor->getInfo($apiKey);		//get info of api (css, template, sku)

        if($type == 'all') return $processor->processAllPdf($datas,$apiKey);	//check type of invoice(order,invoice....)
        $vesHelper = Mage::helper('ves_core');
        $sources = array();
        $apiKeys = array();	/*store all api key*/
        foreach($datas as $data){
            $tmpData 	= unserialize($data);

            /*Get API Key information*/
            $pdfInfo 	= $processor->getInfo($tmpData['key']);

            if(!is_array($pdfInfo) || !isset($pdfInfo[$type.'_template'])){
                $errMsg = Mage::helper('advancedpdfprocessor')->__('Your API key is not valid.');
                if(Mage::app()->getLayout()->getArea() == 'adminhtml'){
                    throw new Mage_Core_Exception($errMsg);
                }else{
                    Mage::log($errMsg, Zend_Log::ERR,'easypdfinvoice.txt');
                    throw new Mage_Core_Exception(Mage::helper('advancedpdfprocessor')->__('Can not generate PDF file. Please contact administrator about this error.'));
                }
            }

            if(!isset($apiKeys[$tmpData['key']])) $apiKeys[$tmpData['key']] = new Varien_Object($pdfInfo);
            $sources[] = $tmpData;
        }

        $className = Mage::getConfig()->getBlockClassName('advancedpdfprocessor/invoicepro');
        $block = new $className;

        $block->setData(array('config'=>$config, 'source'=>$sources,'type'=>$type,'api_keys'=>$apiKeys))->setArea('adminhtml')->setIsSecureMode(true)->setTemplate('ves_advancedpdfprocessor/template-pro.phtml');

        $config = Mage::helper('advancedpdfprocessor/mconfig');
        $config->loadPdfConfig();
        $pageSize = Mage::getStoreConfig('pdfpro/advanced/page_size');
        $orientation = Mage::getStoreConfig('pdfpro/advanced/orientation');
        include_once Mage::getBaseDir().'/app/code/community/VES/AdvancedPdfProcessor/Mpdf/mpdf.php';
        $complex_font = Mage::getStoreConfig('pdfpro/advanced/complex_font');

        if(!$complex_font) {
            $mpdf = new mPDF('c');
        }
        else {
            $mpdf = new mPDF('utf-8',$pageSize.'-'.$orientation);
        }

        $mpdf->SetProtection(array('print'));
        $mpdf->SetTitle("VnEcoms");
        $mpdf->SetAuthor("VnEcoms");

        if($complex_font) {
            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;
        }
        $mpdf->SetDisplayMode('fullpage');

        $html = preg_replace('/>\s+</', "><", $block->toHtml());
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        $mpdf->WriteHTML($html);
        $content = $mpdf->Output($fileName, 'F');
        return array('success'=>true);
    }
}
