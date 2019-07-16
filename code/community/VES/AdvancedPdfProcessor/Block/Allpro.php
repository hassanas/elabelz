<?php
class VES_AdvancedPdfProcessor_Block_Allpro extends Mage_Core_Block_Template
{
	protected $_api_keys;
	
	public function getSkinUrl($file=null,array $params = array()){
		return $this->getSkinDir($file);
		$params['_area']	= 'adminhtml';
		return parent::getSkinUrl($file,$params);
	}
	
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
	
	public function processTemplate($_invoice,$type){
		$apiKey = $_invoice['key'];
		$data 	= $_invoice['data'];
		
		if($type != 'order') {$data['order'] 	= $data['order']['data'];} /* $data['order']= array('apikey'=>'dfgdfgdf','data'=>data)*/
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
		$variables	= array('system'=>new Varien_Object(Mage::helper('advancedpdfprocessor')->getTaxDisplayConfig()), 'type' => $type, $type=>$data,'customer'=>$customer,'billing'=>new Varien_Object($data['billing']),'shipping'=>new Varien_Object($data['shipping']),'payment'=>new Varien_Object($data['payment']),'NUMBER_OF_INVOICE'=>count($data),'config'=>$config,'MY_LOGO'=>'<img src="'.$logoUrl.'" />');
		if($type != 'order') {$variables['order'] = new Varien_Object($data['order']);}
	    $processor->setVariables($variables);
		
	    $template = $apiKey->getData($type.'_template');
	    
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