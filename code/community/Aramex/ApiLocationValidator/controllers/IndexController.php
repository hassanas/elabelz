<?php
class Aramex_ApiLocationValidator_IndexController extends Mage_Core_Controller_Front_Action{
    
	protected function _isAllowed()
	{
			return true;
	}
	public function IndexAction() {
      
	  $this->loadLayout();   
	  $this->getLayout()->getBlock("head")->setTitle($this->__("ApiLocationValidator"));
	        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
      $breadcrumbs->addCrumb("home", array(
                "label" => $this->__("Home Page"),
                "title" => $this->__("Home Page"),
                "link"  => Mage::getBaseUrl()
		   ));

      $breadcrumbs->addCrumb("apilocationvalidator", array(
                "label" => $this->__("ApiLocationValidator"),
                "title" => $this->__("ApiLocationValidator")
		   ));

      $this->renderLayout(); 
	  
    }
	
	/* This method restore the Api Countries in local database */	
	public function RestoreCountriesAction(){
		Mage::getModel('apilocationvalidator/api')->fetchCountriesList();
	}
	
	public function SearchAutoCitiesAction(){
		//print json_encode(array('aa','bbb'));
		$countryCode = $this->getRequest()->getParam('country_code');
		$value = $this->getRequest()->getParam('value');
		
		$post = $this->getRequest()->getPost();
        if ( $post ) {
			$countryCode = $this->getRequest()->getPost('country_code');
			$value = $this->getRequest()->getPost('value');
		}
		
		
		$cities = Mage::getModel('apilocationvalidator/api')->fetchCities($countryCode, $value);
		if(count($cities)>0){
			$cities = array_unique($cities);
			$sortCities = array();
			foreach($cities as $v){
				$sortCities[] = ucwords(strtolower($v));
			}
			asort($sortCities,SORT_STRING);			
			echo '<ul>';
			 foreach($sortCities as $val) echo '<li>'.$val.'</li>';
			echo '</ul>';
		}else{
			echo '<ul></ul>';
		}
	}
	
	public function searchallcitiesJsonAction(){
		$countryCode = $this->getRequest()->getParam('country_code');
		$term = $this->getRequest()->getParam('term');
		print json_encode(Mage::getModel('apilocationvalidator/api')->fetchJsonCities($countryCode,	$term));
	}
	
	public function SearchAllCitiesAction(){		
		$countryCode = $this->getRequest()->getParam('country_code');
		$term = $this->getRequest()->getParam('term');
		
		$post = $this->getRequest()->getPost();
		
        if ( $post ) {
			$countryCode = $this->getRequest()->getPost('country_code');
			$term = $this->getRequest()->getPost('term');
		}
		
		$cities = Mage::getModel('apilocationvalidator/api')->fetchCities($countryCode, $term);
		if(count($cities)>0){			
			foreach($cities as $v){
				$sortCities[] = strtolower($v);
			}
			asort($sortCities,SORT_STRING);			
			echo '<ul>';
			 foreach($sortCities as $val) echo '<li>'.$val.'</li>';
			echo '</ul>';
		}	
	}
	
	/* To check give  city  and post code is valid  or not*/
	public function ApplyValidationAction(){
		$address = array();		
		$address['city'] = $this->getRequest()->getPost('city');
		$address['post_code'] = $this->getRequest()->getPost('post_code');
		$address['country_code'] = $this->getRequest()->getPost('country_code');		
		$result = Mage::getModel('apilocationvalidator/api')->validateAddress($address);
		print json_encode($result);
	}
	
	
	public function RestoreSystemAction(){
		Mage::getModel('apilocationvalidator/api')->resetStoreGeneralOptions();
	}
	
	public function ApplyApiValidatorAction(){
		$country_code = $this->getRequest()->getPost('country_code');
		$collection = Mage::getModel("apilocationvalidator/country")->getCollection()->addFieldToSelect(array('code','post_code_required','state_required'))->addFieldToFilter('code',$country_code)->setPageSize(1);
		
		/* to avoid magento level error */
		$storeId = Mage::app()->getStore()->getStoreId();
		$state_requires = explode(',',Mage::getStoreConfig('general/region/state_required',$storeId));
		if($collection && $collection->count()>0){	          
			print json_encode($collection->getFirstItem()->getData());
		}else{
			print json_encode(array('no_data'=>true));
		}
	}
	
	
	
}