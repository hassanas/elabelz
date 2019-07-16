<?php
class Progos_Sizeguide_Helper_Sizeguide extends Mage_Core_Helper_Abstract{

	public function getBrandCollection(){
		
		$attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'manufacturer'); //"color" is the attribute_code
		$allOptions = $attribute->getSource()->getAllOptions(true, true);
		$brandArray = array();
		$brandArray[] = array(
	            'label' => 'All',
	            'value' => 'all',
	        );
		foreach ($allOptions as $instance) {
			if( empty($instance['value']) )
				continue;
		    $brandArray[] = array(
	            'label' => $instance['label'],
	            'value' => $instance['value']
	        );
		}
		return $brandArray;
	}

	public function getBrandGridCollection(){
		
		$attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'manufacturer'); //"color" is the attribute_code
		$allOptions = $attribute->getSource()->getAllOptions(true, true);
		$brandArray = array();
		$brandArray['all'] = 'All';
		foreach ($allOptions as $instance) {
	            $brandArray[$instance['value']] = $instance['label'];
	        
		}
		return $brandArray;
	}

	/*
	* Get All Category Into dropdown with Parent to child categories.
	*/
	public function getCategories(){
		$data = array();
		$gender = array('women','men','kids');
		$categories = Mage::getModel('catalog/category')->getCategories(2);
		foreach( $categories as $category ){ //Level 1
			if( !in_array( strtolower( $category->getName() ), $gender ) )
				continue;
			if ( $category->hasChildren() ) {
				$data[] = array('label'=>$category->getName(),'value'=>$category->getId()) ;
                $categoriesSub = Mage::getModel('catalog/category')->getCategories($category->getId());
                foreach( $categoriesSub as $categorySub ){ //Level2
                	if( $categorySub->hasChildren() ){
                		$data[] = array('label'=>$category->getName().' > '.$categorySub->getName(),
            							'value'=> $category->getId().','.$categorySub->getId()); 
                		$categoriesSubSub = Mage::getModel('catalog/category')->getCategories($categorySub->getId());
                		foreach( $categoriesSubSub as $categorySubSub ){ //Level3
                			if( $categorySubSub->hasChildren() ){
                				$data[] = array(
		            						'label'=> $category->getName().' > '.$categorySub->getName().' > '.$categorySubSub->getName() ,
		            						'value'=>$category->getId().','.$categorySub->getId().','.$categorySubSub->getId());

                				$categoriesSubSubSub = Mage::getModel('catalog/category')->getCategories($categorySubSub->getId());
                				foreach( $categoriesSubSubSub as $categorySubSubSub ){ //Level4
                					if( $categorySubSubSub->hasChildren() ){
                						$data[] = array(
            										'label'=> $category->getName().' > '.$categorySub->getName().' > '.$categorySubSub->getName().' > '.$categorySubSubSub->getName(),
            										'value'=>$category->getId().','.$categorySub->getId().','.$categorySubSub->getId().','.$categorySubSubSub->getId()
            									  ); 
                						
                						$categoriesSubSubSubSub = Mage::getModel('catalog/category')->getCategories($categorySubSubSub->getId());
                						foreach( $categoriesSubSubSubSub as $categorySubSubSubSub ){ //Level5
            								$data[] = array(
            											'label'=> $category->getName().' > '.$categorySub->getName().' > '.$categorySubSub->getName().' > '.$categorySubSubSub->getName().' > '.$categorySubSubSubSub->getName() ,
            											'value'=> $category->getId().','.$categorySub->getId().','.$categorySubSub->getId().','.$categorySubSubSub->getId().','.$categorySubSubSubSub->getId()
            										  ); 
                						}
                					}else{
            							$data[] = array(
            										'label'=> $category->getName().' > '.$categorySub->getName().' > '.$categorySubSub->getName().' > '.$categorySubSubSub->getName(),
            										'value'=>$category->getId().','.$categorySub->getId().','.$categorySubSub->getId().','.$categorySubSubSub->getId()
            									  ); 
                					}
                				}
                			}else{
            					$data[] = array(
		            						'label'=> $category->getName().' > '.$categorySub->getName().' > '.$categorySubSub->getName() ,
		            						'value'=>$category->getId().','.$categorySub->getId().','.$categorySubSub->getId()); 
                			}
                		}
                	}else{
                		$data[] = array('label'=>$category->getName().' > '.$categorySub->getName(),
            							'value'=> $category->getId().','.$categorySub->getId()); 
                	}
                }
            }else{
            	//$data[] = array('label'=>,'value'=>); 
            	$data[] = array('label'=>$category->getName(),'value'=>$category->getId()) ;
            }
		}
		return $data;
	}

	public function getCategoriesGridCollection(){
		$data = array();
		$gender = array('women','men','kids');
		$categories = Mage::getModel('catalog/category')->getCategories(2);
		$data[] = array(); 
		foreach( $categories as $category ){ //Level 1
			if( !in_array( strtolower( $category->getName() ), $gender ) )
				continue;
			if ( $category->hasChildren() ) {
				$data[$category->getId()] = $category->getName();
                $categoriesSub = Mage::getModel('catalog/category')->getCategories($category->getId());
                foreach( $categoriesSub as $categorySub ){ //Level2
                	if( $categorySub->hasChildren() ){
                		$data[$category->getId().','.$categorySub->getId()] = $category->getName().' > '.$categorySub->getName();
            
                		$categoriesSubSub = Mage::getModel('catalog/category')->getCategories($categorySub->getId());
                		foreach( $categoriesSubSub as $categorySubSub ){ //Level3
                			if( $categorySubSub->hasChildren() ){
                				$data[$category->getId().','.$categorySub->getId().','.$categorySubSub->getId()] = $category->getName().' > '.$categorySub->getName().' > '.$categorySubSub->getName();
                				
                				$categoriesSubSubSub = Mage::getModel('catalog/category')->getCategories($categorySubSub->getId());
                				foreach( $categoriesSubSubSub as $categorySubSubSub ){ //Level4
                					if( $categorySubSubSub->hasChildren() ){
                						$data[$category->getId().','.$categorySub->getId().','.$categorySubSub->getId().','.$categorySubSubSub->getId()] = $category->getName().' > '.$categorySub->getName().' > '.$categorySubSub->getName().' > '.$categorySubSubSub->getName();
                						
                						$categoriesSubSubSubSub = Mage::getModel('catalog/category')->getCategories($categorySubSubSub->getId());
                						foreach( $categoriesSubSubSubSub as $categorySubSubSubSub ){ //Level5
                							$data[$category->getId().','.$categorySub->getId().','.$categorySubSub->getId().','.$categorySubSubSub->getId().','.$categorySubSubSubSub->getId()] = $category->getName().' > '.$categorySub->getName().' > '.$categorySubSub->getName().' > '.$categorySubSubSub->getName().' > '.$categorySubSubSubSub->getName();
                						}
                					}else{
                						$data[$category->getId().','.$categorySub->getId().','.$categorySubSub->getId().','.$categorySubSubSub->getId()] = $category->getName().' > '.$categorySub->getName().' > '.$categorySubSub->getName().' > '.$categorySubSubSub->getName();
                					}
                				}
                			}else{
                				$data[$category->getId().','.$categorySub->getId().','.$categorySubSub->getId()] = $category->getName().' > '.$categorySub->getName().' > '.$categorySubSub->getName();
                			}
                		}
                	}else{
                		$data[$category->getId().','.$categorySub->getId()] = $category->getName().' > '.$categorySub->getName();
                	}
                }
            }else{
            	$data[$category->getId()] = $category->getName();
            }
		}
		return $data;
	}
}