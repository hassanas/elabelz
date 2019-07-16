<?php
/**
 
 *
 * @category   CP
 * @package    CP_Catalog
 * @author     CP
 */ 
class Kvh_Simpleseo_Block_Catalog_Product_View extends Mage_Catalog_Block_Product_View
{ 
    /**
     * Add meta information from product to head block
     *
     * @return Mage_Catalog_Block_Product_View
     */
    protected function _prepareLayout()
    { 
    	parent::_prepareLayout();
    	
    	$simpleseo_enable = Mage::getStoreConfig('catalog/simpleseo/simpleseo_enable');
    	
    	if(!$simpleseo_enable) return; 
    	
    	$product_meta_title = Mage::getStoreConfig('catalog/simpleseo/product_meta_title');
    	$product_meta_keyword = Mage::getStoreConfig('catalog/simpleseo/product_meta_keyword');
    	$product_meta_description = Mage::getStoreConfig('catalog/simpleseo/product_meta_description');
    	
    	$string=$product_meta_title." ".$product_meta_keyword." ".$product_meta_description;
    	
    	preg_match_all("/\[(.*?)\]/",$string,$words);
    	
    	
    	$product = $this->getProduct();
    	$currentCategory = Mage::registry('current_category');
    	
    	
    	$word=array_unique($words[1]);
    	
    	
    	foreach($word as $w)
    	{ 
    		$data="";
    		$cdata="";
    		$p= explode("_",$w);
    		
    		if($p[0]=="product")
    		{
    			$attribute=substr($w,8);	
    			$attributeModel = Mage::getModel('eav/entity_attribute')->loadByCode("catalog_product",$attribute);
    			
    			$attrtype=$attributeModel->getFrontendInput();
    			
    			switch($attrtype)
    			{
    				case "text":
    				$data=$product->getData($attribute); 
    				break;
    				case "price":
    				$data=$product->getData($attribute); 
    				break;				
    				case  "select":
    				$data=$product->getAttributeText($attribute); 
    				break;
    				
    			} 
    			$product_meta_title=str_replace("[".$w."]",$data,$product_meta_title);  
    			$product_meta_keyword=str_replace("[".$w."]",$data,$product_meta_keyword);  
    			$product_meta_description=str_replace("[".$w."]",$data,$product_meta_description);  
    			
    		}
    		
    		if($p[0]=="category")
    		{
    			$attribute=substr($w,9);	
    			$attributeModel = Mage::getModel('eav/entity_attribute')->loadByCode("catalog_category",$attribute);
    			
    			$attrtype=$attributeModel->getFrontendInput();
    			
    			switch($attrtype)
    			{
    				case "text":
    				$cdata=$currentCategory->getData($attribute); 
    				break;
    				case  "select":
    				$cdata=$currentCategory->getAttributeText($attribute); 
    				break;
    				
    			} 
    			$product_meta_title=str_replace("[".$w."]",$cdata,$product_meta_title);  
    			$product_meta_keyword=str_replace("[".$w."]",$cdata,$product_meta_keyword);  
    			$product_meta_description=str_replace("[".$w."]",$cdata,$product_meta_description);  
    			
    		} 
    		
    		
    		
    		
    		
    	}   
		// 	$headBlock = $this->getLayout()->getBlock('head');
		// 	if ($headBlock) {
    	
		// 		$title = $product->getMetaTitle();
    	
		// 		if (!$title) {
		// 			 $headBlock->setTitle($product_meta_title); 
		// 		}
    	
		// 	}
    	
    	
		// 	$keyword = $product->getMetaKeyword();
    	
  //           if (!$keyword) {
  //               $headBlock->setKeywords($product_meta_keyword);
  //           }  
    	
		//  $description = $product->getMetaDescription();
  //           if (!$description) {
  //               $headBlock->setDescription($product_meta_description);
		// }
    	$product = $this->getProduct();
    	$title = $product->getMetaTitle();
    	if (!$title) {
    		$product->setMetaTitle( $product_meta_title );
    	}
    	$keyword = $product->getMetaKeyword();
    	if (!$keyword) {
    		$product->setMetaKeyword( $product_meta_keyword );
    	}
    	$description = $product->getMetaDescription();
    	if (!$description) {
    		$product->setMetaDescription( $product_meta_description );
    	}	
    	
    	
    }

    
    
    
}