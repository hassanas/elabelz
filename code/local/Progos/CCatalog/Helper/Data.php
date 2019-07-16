<?php
/**
 * Catalog data helper
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Progos_CCatalog_Helper_Data extends Mage_Catalog_Helper_Data
{ 
	public function getBreadcrumbPath()
    {
        if (!$this->_categoryPath) { 
            $path = array();
            //Hassan Ali Shahzad: Need Optimization for PDP in this function if product page no need to render native block or even if required do it in else
            // I am not doing optimization at this time due to urgent task but need to check it
            if ($category = $this->getCategory()) {
                $pathInStore = $category->getPathInStore();
                $pathIds = array_reverse(explode(',', $pathInStore));

                $categories = $category->getParentCategories();

                // add category path breadcrumb
                foreach ($pathIds as $categoryId) {
                    if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                        $path['category'.$categoryId] = array(
                            'label' => $categories[$categoryId]->getName(),
                            'link' => $this->_isCategoryLink($categoryId) ? $categories[$categoryId]->getUrl() : ''
                        );
                    }
                }
            }

            if ($this->getProduct()) {

            	$currentCatIds = $this->getProduct()->getCategoryIds();
            	$categoryLevelCollection = Mage::getModel('catalog/category')->getCollection()
                    ->addAttributeToSelect('name')//2 is actually the first level
                    ->addAttributeToFilter('entity_id', array('in' => $currentCatIds))
                    ->addAttributeToFilter('level',2)
                    ->addAttributeToFilter('is_active', 1);
                $counter_category = 0;
                $counter_newarrivals = 0;
                $counter_sales = 0;
                // 213:New Arrivals, 423:Sales // For Developers on local due to different sales & New Arrivals category id it will not work properly if you have task related to this plz modify these ids as per yours
                foreach($categoryLevelCollection as $cat):
                	if($cat->getId()!= 423 && $cat->getId() != 213):
                       $counter_category = 1;
                       $curen_category_name = $cat->getName();                 
                    elseif($cat->getId() == 213):
                       $counter_newarrivals = 1;
                    elseif($cat->getId() == 423):
                       $counter_sales = 1;
                    endif;
                endforeach;
            	$path = array();
            	// optimization here as well just for DESC no need to get cats again need to check this logic
            	 $categoryCollection = 
                Mage::getModel('catalog/category')->getCollection()
                    ->addAttributeToSelect('*')//2 is actually the first level
                    ->addAttributeToFilter('entity_id', array('in' => $currentCatIds))
                    ->addAttributeToFilter('is_active', 1)
                    ->addAttributeToSort('level', DESC);
                foreach($categoryCollection as $cat):
                    $path_cat = $cat->getPath();
                    $ids = explode('/', $path_cat); 

					if (isset($ids[2])){
						$topParent = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getId())->load($ids[2]);	
					}
					else{
						$topParent = null;//it means you are in one catalog root.
					}
					if($counter_category == 1):
				       if($topParent->getName() == $curen_category_name):
                       $pathInStore = $cat->getPathInStore();
                       $pathIds = array_reverse(explode(',', $pathInStore));

                       $categories = $cat->getParentCategories();

                       // add category path breadcrumb
                        foreach ($pathIds as $categoryId) {
                        if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                        $path['category'.$categoryId] = array(
                            'label' => $categories[$categoryId]->getName(),
                            'link' => $this->_isCategoryLink($categoryId) ? $categories[$categoryId]->getUrl() : ''
                           );
                           }
                        }
                        break;
				        endif;
				    elseif($counter_category != 1 && $counter_newarrivals == 1):
                        if($topParent->getId() == 213):
				          $pathInStore = $cat->getPathInStore();
                       $pathIds = array_reverse(explode(',', $pathInStore));

                       $categories = $cat->getParentCategories();

                       // add category path breadcrumb
                        foreach ($pathIds as $categoryId) {
                        if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                        $path['category'.$categoryId] = array(
                            'label' => $categories[$categoryId]->getName(),
                            'link' => $this->_isCategoryLink($categoryId) ? $categories[$categoryId]->getUrl() : ''
                           );
                           }
                        }
                        break;
				        endif;
                    elseif($counter_category != 1 && $counter_newarrivals != 1 && $counter_sales == 1):
                    	if($topParent->getId() == 423):
                    		$pathInStore = $cat->getPathInStore();
                       $pathIds = array_reverse(explode(',', $pathInStore));

                       $categories = $cat->getParentCategories();

                       // add category path breadcrumb
                        foreach ($pathIds as $categoryId) {
                        if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                        $path['category'.$categoryId] = array(
                            'label' => $categories[$categoryId]->getName(),
                            'link' => $this->_isCategoryLink($categoryId) ? $categories[$categoryId]->getUrl() : ''
                           );
                           }
                        }
                        break;
                        endif;
                    endif;

                    endforeach;
                    $path['product'] = array('label'=>$this->getProduct()->getName());
            }

            $this->_categoryPath = $path;
            // Get Breadcrumb PAth into Product DataLayer for Enhance eCommerce.
            if ($this->getProduct()) {
                $mageSession = Mage::getSingleton("core/session",  array("name"=>"frontend"));
            if( !empty ($mageSession->getBreadcrumbProductDatalayer()) ) // Unset Breadcrump if Product Page refresh.
                $mageSession->unsBreadcrumbProductDatalayer( $path );
            $mageSession->setBreadcrumbProductDatalayer( $path ); // Set Breadcrumb Session for Product
            }
        }
        return $this->_categoryPath;
    }

    public function getParentCategory($path_cat,$category)
    {
        $ids = explode('/', $path_cat); 

        if (isset($ids[2])){
            $topParent = Mage::getModel('catalog/category')->setStoreId(Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)->load($ids[2]);
              if($topParent->getName() !== "Women" && $topParent->getName() !== "Kids" && $topParent->getName() !== "Men" && $topParent->getName() !== "kids"  ){
                  $ids_new = explode('/', $path_cat);
                    if(isset($ids[3])){
                        $topParent_new = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getStoreId())->load($ids[3]);
                        $topParent = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getStoreId())->load($ids[2]);
                        $category_name = $this->__($topParent_new->getName())." ".$this->__($category->getName())." - ".$this->__($topParent->getName());
                      }
                    }   
                else{
                  $topParent = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getStoreId())->load($ids[2]);
                  $category_name = $this->__($topParent->getName())." ".$this->__($category->getName());
                }
        }
        else{
          $topParent = null;//it means you are in one catalog root.
        }
        return $category_name;
      
    }

}