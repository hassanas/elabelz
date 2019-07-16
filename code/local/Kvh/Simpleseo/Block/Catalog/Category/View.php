<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Category View block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Kvh_Simpleseo_Block_Catalog_Category_View extends Mage_Catalog_Block_Category_View
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
		
		$category_meta_title = Mage::getStoreConfig('catalog/simpleseo/category_meta_title');
		$category_meta_keyword = Mage::getStoreConfig('catalog/simpleseo/category_meta_keyword');
		$category_meta_description = Mage::getStoreConfig('catalog/simpleseo/category_meta_description');
		
		$string=$category_meta_title." ".$category_meta_keyword." ".$category_meta_description;
		
		preg_match_all("/\[(.*?)\]/",$string,$words);
		
		
		$category = $this->getCurrentCategory();
		 
		$word=array_unique($words[1]);
		  
		
		foreach($word as $w)
		{ 
			$p= strpos($w,'category_');
		
			if($p==0)
			{
				$attribute=substr($w,9);	
				$attributeModel = Mage::getModel('eav/entity_attribute')->loadByCode("catalog_category",$attribute);
				 
				$attrtype=$attributeModel->getFrontendInput();
				
				switch($attrtype)
				{
				case "text":
						$data=$category->getData($attribute); 
					break;
				case  "select":
						$data=$category->getAttributeText($attribute); 
					break;
				
				} 
				$category_meta_title=str_replace("[".$w."]",$data,$category_meta_title);  
				$category_meta_keyword=str_replace("[".$w."]",$data,$category_meta_keyword);  
				$category_meta_description=str_replace("[".$w."]",$data,$category_meta_description);  
			} 
		
		}   
			$headBlock = $this->getLayout()->getBlock('head');
			if ($headBlock) {
			
				$title = $category->getMetaTitle();
				
				if (!$title) {
					 $headBlock->setTitle($category_meta_title); 
				}
		
			}
		
		
			$keyword = $category->getMetaKeyword();
             
            if (!$keyword) {
                $headBlock->setKeywords($category_meta_keyword);
            }  
		
		 $description = $category->getMetaDescription();
            if (!$description) {
                $headBlock->setDescription($category_meta_description);
		}
			 
		
	}

    

   }  