<?php
class VES_AdvancedPdfProcessor_Block_Invoicepro extends Mage_Adminhtml_Block_Abstract
{
	protected $_api_keys;
	

	public function getSkinDir($fileName){
		$params= array('_area'	=> 'adminhtml','_package'=>'default','_theme'=>'default');
		return Mage::getDesign()->getSkinBaseDir($params).DS.$fileName;
	}
	
	/**
	 * Get body html
	 * @param string $html
	 */
	public function getBody($html){
		$html = preg_replace('#{{header}}(.*?){{\/header}}#is', '', $html);
		$html = preg_replace('#{{footer}}(.*?){{\/footer}}#is', '', $html);
		return $html;
	}
	
	/**
	 * Get header by html
	 * @param string $html
	 */
	public function getHeader($html){
		preg_match('/{{header}}(.*?){{\/header}}/si', $html, $matches);
		if($matches && (sizeof($matches)==2)){
			return $matches[1];
		}
		return '';
	}
	/**
	 * Get footer
	 * @param string $html
	 */
	public function getFooter($html){
		preg_match('/{{footer}}(.*?){{\/footer}}/si', $html, $matches);
		if($matches && (sizeof($matches)==2)){
			return $matches[1];
		}
		return '';
	}
	
	/**
	 * process template by invoice
	 * @param unknown $_invoice
	 */
	public function processTemplate($_invoice){
		$apiKey = $_invoice['key'];
		$data = $_invoice['data'];
		//var_dump($data);die();
		if($this->getType() != 'order') {$data['order'] 	= $data['order']['data'];}
		if($this->getType() == 'custom1' or $this->getType() == 'custom2' or $this->getType() == 'custom3') {
			$order =  Mage::getModel('sales/order')->load($data->getData('entity_id'));
			//var_dump($order->getData());die();
			$orderData			= 	Mage::getModel('pdfpro/order')->initOrderData($order);
			$data['order']      = unserialize($orderData);
		}
		$apiKey 		= $this->getApiKeyInfo($_invoice['key']);
		$pdfProApiKey 	= Mage::getModel('pdfpro/key')->load($_invoice['key'],'api_key');
		$logoUrl		= Mage::getBaseDir('media').DS.$pdfProApiKey->getLogo();
		$invoiceProduct = $this->getInvoice($_invoice['key']);
		/* Get domain config */
		if($apiKey->getConfig()){
    		$additionData = json_decode($apiKey->getConfig(),true);
    	}else{
    		$additionData = array();
    	}
    	$config 	= new Varien_Object($additionData);
		
    	/* Process the body of PDF */
    	$processor 	= Mage::getModel('advancedpdfprocessor/template_filter');
		$customer 	= new Varien_Object($data->getCustomer());
		$variables	= array('system'=>new Varien_Object(Mage::helper('advancedpdfprocessor')->getTaxDisplayConfig()), 'type'=>$this->getType(),$this->getType()=>$data,'customer'=>$customer,'billing'=>$data['billing'],'shipping'=>$data['shipping'],'payment'=>new Varien_Object($data['payment']),'NUMBER_OF_INVOICE'=>count($data),'config'=>$config,'MY_LOGO'=>'<img src="'.$logoUrl.'" />');
		if($this->getType() != 'order') $variables['order'] = $data['order'];
	    if($this->getType() == 'custom1' or $this->getType() == 'custom2' or $this->getType() == 'custom3') {
			$variables['order'] = $data['order']->getData('data');
		}
		$processor->setVariables($variables);
		
		//var_dump($variables['order']);die();
	    $template = $apiKey->getData($this->getType().'_template');
	    
	    $template = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $template);
		return $processor->filter($template);
	}
	
	/**
	 * get css path from template table
	 * -> css from user uploaded
	 * @return multitype:unknown
	 */
	public function getCssUrls(){
		$cssUrls = array();
		foreach($this->getApiKeys() as $index=>$key){
			$templateId = $key->getTemplateId();
			$cssUrl = $this->getSkinUrl(Mage::getModel('advancedpdfprocessor/template')->load($templateId)->getCssPath());
			$cssUrls[] = $cssUrl;
		}
		return $cssUrls;
	}
	
	public function getAdditionCss(){
		$css = '';
		foreach($this->getApiKeys() as $key){
			$css .= $key->getAdditionCss();
		}
		return $css;
	}
	
	/**
	 * get css in DB
	 * ->css addition from user add into each api_key
	 * @return string
	 */
	public function getCss() {
		$css = '';
		foreach($this->getApiKeys() as $api_key => $key){
			$css .= $key->getCss();
		}
		return $css;
	}
	
	/**
	 * get invoice sku(received from template table)
	 * @param unknown $apiKey
	 */
	public function getInvoiceSku($apiKey){
		foreach($this->getApiKeys() as $index=>$key){
			if($apiKey == $index) {
				$templateId = $key->getTemplateId();
				return Mage::getModel('advancedpdfprocessor/template')->load($templateId)->getSku();
			}
		}
	}
	
	public function getApiKeyInfo($apiKey){
		$apiKeys = $this->getApiKeys();
		return $apiKeys[$apiKey];
	}
	
	public function getAdditionData($apiKey){
		$apiKeys = $this->getApiKeys();
		$additionData = json_decode($apiKeys[$apiKey]->getConfig(),true);
		return $additionData;
	}
}