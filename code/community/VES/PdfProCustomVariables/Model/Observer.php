<?php
class VES_PdfProCustomVariables_Model_Observer
{
	/**
	 * Get All formated date for givent date
	 * @param string $date
	 * @return Varien_Object
	 */
	public function getFormatedDate($date,$type = null){
		$dateFormated = new Varien_Object(array(
				'full' 		=> Mage::app()->getLocale()->date(strtotime($date))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_FULL)),
				'long' 		=> Mage::app()->getLocale()->date(strtotime($date))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_LONG)),
				'medium' 	=> Mage::app()->getLocale()->date(strtotime($date))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM)),
				'short' 	=> Mage::app()->getLocale()->date(strtotime($date))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT)),
		));
		if($type) return $dateFormated->getData($type);
		return $dateFormated;
	}
	
	
	public function isExistAttribute($product, $att_code) {
		$atts = $product->getAttributes();
		foreach($atts as $att) {
			if($att_code == $att->getAttributeCode()) return true;
		}
		return false;
	}
	/**
	 * Get all available attribute codes of product
	 * @param Mage_Catalog_Model_Product $product
	 */
	public function getAvailableAttributeCodesOfProduct(Mage_Catalog_Model_Product $product){
		$attributes = $product->getAttributes();
		$result 	= array();
		foreach($attributes as $attribute) {
			$result[$attribute->getAttributeCode()] = $attribute->getAttributeCode();
		}
		return $result;
	}
	
	/**
	 * Get all available attribute codes of customer
	 * @param Mage_Catalog_Model_Customer $customer
	 */
	public function getAvailableAttributeCodesOfCustomer(Mage_Customer_Model_Customer $customer){
		$attributes = $customer->getAttributes();
		$result 	= array();
		foreach($attributes as $attribute) {
			$result[$attribute->getAttributeCode()] = $attribute->getAttributeCode();
		}
		return $result;
	}
	
	/**
	 * Add custom variables for PDF Invoice Pro
	 * @param Varien_Event_Observer $observer
	 */
	public function ves_advancedpdfprocessor_init_item_attribute_after(Varien_Event_Observer $observer){
		$groups = $observer->getAttributes(); /*array*/
		$custom_attributes = Mage::getModel('pdfprocustomvariables/pdfprocustomvariables')
		->getCollection()->addFieldToFilter('variable_type','attribute');
		
		$attributes = array();
		foreach($custom_attributes as $_attribute) {
			$_attribute_id = $_attribute->getAttributeId();
			$model = Mage::getModel('eav/entity_attribute')->load($_attribute_id);
				
			$attributes[] = array('title' => $_attribute->getName() , 'code' => 'ves_'.$_attribute->getName());	//not $model->getAttributeCode() 
			//because items in VES_AdvancedPdfProcessor_Model_Template_Filter line 353
			// has product attribute
		}
		
		$attributes[] = array('title' => '---Custom' , 'code' => 'ves_custom');
		
		$observer->getAttributes()->setData('custom',array('label'=>'Custom Attributes','value' => $attributes));
		
	}
	
	/**
	 * Add custom variables for Easy PDF Invoice extension.
	 * @param unknown_type $observer
	 */
    public function ves_pdfpro_data_prepare_after($observer){
    	$type = $observer->getType();
    	if($type=='item'){
    		$itemData 		= $observer->getSource();
    		$item 			= $observer->getModel();
    		$product 		= Mage::getModel('catalog/product')->load($item->getProductId());
    		$itemProduct 	= new Varien_Object();
    		
			if (!($item instanceof Mage_Sales_Model_Order_Item)) {
	            $order = $item->getOrderItem()->getOrder();
	        }else{
				$order 	= $item->getOrder();
			}
    		
    		$orderCurrencyCode 		= $order->getOrderCurrencyCode();
    		
    		$availableAttributes	= $this->getAvailableAttributeCodesOfProduct($product);
    		
    		
    		$collection = Mage::getModel('pdfprocustomvariables/pdfprocustomvariables')->getCollection();
    		foreach ($collection->getData() as $data) {
    			switch($data['variable_type']) {
    				case 'attribute':
    					$attributeInfo = new Varien_Object(Mage::getModel('catalog/product_attribute_api')->info($data['attribute_id']));
						switch($attributeInfo->getFrontendInput()) {
							case 'text':
								isset($availableAttributes[$attributeInfo->getAttributeCode()]) ?
								$src = $product->getData($attributeInfo->getAttributeCode())
								: $src = '';
								$itemProduct->setData($data['name'], $src);
							break;
							
							case 'textarea':
								isset($availableAttributes[$attributeInfo->getAttributeCode()]) ?
								$src = $product->getData($attributeInfo->getAttributeCode())
								: $src = '';
								$itemProduct->setData($data['name'], $src);
							break;
							
							case 'date':
								if(isset($availableAttributes[$attributeInfo->getAttributeCode()])) {
									$date = $product->getData($attributeInfo->getAttributeCode());
									$dateFormated = $date?new Varien_Object(array(
										'full' 		=> Mage::app()->getLocale()->date(strtotime($date))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_FULL)),
										'long' 		=> Mage::app()->getLocale()->date(strtotime($date))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_LONG)),
										'medium' 	=> Mage::app()->getLocale()->date(strtotime($date))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM)),
										'short' 	=> Mage::app()->getLocale()->date(strtotime($date))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT)),
									)):'';
									$itemProduct->setData($data['name'],$dateFormated);
								}else {
									$itemProduct->setData($data['name'],'');
								}
							break;
							
							case 'price':
								isset($availableAttributes[$attributeInfo->getAttributeCode()]) ?
								$price = Mage::helper('pdfpro')->currency($product->getData($attributeInfo->getAttributeCode()),$orderCurrencyCode)
								: $price = '';
								$itemProduct->setData($data['name'], $price);
							break;
							
							case 'multiselect':
								$label_arr = $product->getAttributeText($attributeInfo->getAttributeCode());
								count($label_arr) == 0 ? $label = '' : $label = implode(',', $label_arr);
								$itemProduct->setData($data['name'], $label);
							break;
							
							case 'select':    							
								(isset($availableAttributes[$attributeInfo->getAttributeCode()]) && $product->getData($attributeInfo->getAttributeCode()) != '') ? 
								$label = $product->getResource()->getAttribute($attributeInfo->getAttributeCode())->getFrontend()->getValue($product)
								: $label = '';
								$itemProduct->setData($data['name'], $label);
							break;
							
							case 'boolean':
								isset($availableAttributes[$attributeInfo->getAttributeCode()]) ?
								$label = $product->getResource()->getAttribute($attributeInfo->getAttributeCode())->getFrontend()->getValue($product)
								: $label = '';
								$itemProduct->setData($data['name'], $label);
								break;
							
							case 'media_image':
								if(isset($availableAttributes[$attributeInfo->getAttributeCode()]) && 
								$availableAttributes[$attributeInfo->getAttributeCode()] &&
								($imageFile = $product->getData($attributeInfo->getAttributeCode()))){
									$helper	= Mage::helper('pdfprocustomvariables/image')->init($product, 'image',$imageFile)->resize(60);
									$model = $helper->getModel();
									$url = $helper.'';
									$src 	= $model->getNewFile();
									$src = base64_encode(file_get_contents($src));
									//$src ='<img src="data:image/png;base64,'.$src.'" />';
									$src ='<img src="data:image/jpg;base64,'.$src.'" />';
								}else{
									$src = Mage::getBaseDir('skin').DS.'frontend'.DS.'base'.DS.'default'.DS.'images'.DS.'catalog'.DS.'product'.DS.'placeholder'.DS.'thumbnail.jpg';
									$src = base64_encode(file_get_contents($src));
									//$src ='<img src="data:image/png;base64,'.$src.'" />';
									$src ='<img src="data:image/jpg;base64,'.$src.'" />';
								}
								$itemProduct->setData($data['name'], $src);
							break;
							
							default:
								isset($availableAttributes[$attributeInfo->getAttributeCode()]) ?
								$src = $product->getData($attributeInfo->getAttributeCode())
								: $src = '';
								$itemProduct->setData($data['name'], $src);
							break;
						}
    				break;
    				
    				case 'static':
    					$itemData->setData($data['name'], $data['static_value']);
    				break;
    			}
    		}
    		$itemData->setData('product',$itemProduct);
    	} else if ($type == 'customer') {
    		$customerData 	= $observer->getSource();
    		$item 			= $observer->getModel();
    		$customer 		= Mage::getModel('customer/customer')->load($item->getId());
    		$availableAttributes	= $this->getAvailableAttributeCodesOfCustomer($customer);
    		$collection = Mage::getModel('pdfprocustomvariables/pdfprocustomvariables')->getCollection();
    		
    		foreach($collection->getData() as $data) {
    			if($data['variable_type'] == 'customer') {
    				$attributeInfo = Mage::getModel('pdfprocustomvariables/pdfprocustomvariables')
    				->getAttributeInfo($data['attribute_id_customer']);
    					switch($attributeInfo['frontend_input']) {
    						case 'text':
    							isset($availableAttributes[$attributeInfo['attribute_code']]) ?
    							$src = $customer->getData($attributeInfo['attribute_code'])
    							: $src = '';
    							$customerData->setData($data['name'], $src);
    						break;
    						
    						case 'boolean':
    							isset($availableAttributes[$attributeInfo['attribute_code']]) ? 
    							($customer->getData($attributeInfo['attribute_code']) == 1 ? $label = Mage::helper('core/translate')->__('Yes') : $label = Mage::helper('core/translate')->__('No')) : $label = '';
    							
    							$customerData->setData($data['name'], $label);
    						break;
    						
    						case 'select':
    							(isset($availableAttributes[$attributeInfo['attribute_code']]) && $customer->getData($attributeInfo['attribute_code']) != '') ?
    							$label = $customer->getResource()->getAttribute($attributeInfo['attribute_code'])->getFrontend()->getValue($customer)
    							: $label = '';
    							$customerData->setData($data['name'], $label);
    						break;
    						
    						case 'date':
    							if(isset($availableAttributes[$attributeInfo['attribute_code']])) {
    								$date = $customer->getData($attributeInfo['attribute_code']);
    								$dateFormated = new Varien_Object(array(
    										'full' 		=> Mage::app()->getLocale()->date(strtotime($date))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_FULL)),
    										'long' 		=> Mage::app()->getLocale()->date(strtotime($date))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_LONG)),
    										'medium' 	=> Mage::app()->getLocale()->date(strtotime($date))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM)),
    										'short' 	=> Mage::app()->getLocale()->date(strtotime($date))->toString(Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT)),
    								));
    								$customerData->setData($data['name'],$dateFormated);
    							}else {
    								$customerData->setData($data['name'],'');
    							}
    						break;
    						
    						default:
    							isset($availableAttributes[$attributeInfo['attribute_code']]) ?
    							$src = $customer->getData($attributeInfo['attribute_code'])
    							: $src = '';
    							$customerData->setData($data['name'], $src);
    						break;
    					}
    			}
    		}
    	}
    }
}
