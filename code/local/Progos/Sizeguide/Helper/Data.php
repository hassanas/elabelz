<?php
class Progos_Sizeguide_Helper_Data extends Mage_Core_Helper_Abstract{

    /*
    * Split Category at level 2 from Product Category Ids
    */
    public function getSplitCategoryLevel( $productCats ){
        $levelArray = array();
        $ids = '';
        $totalCount = count($productCats);
        $count = 1;
        $levelSplit = array();

        $categories = Mage::getModel('catalog/category')
                ->getCollection()
                ->addAttributeToSelect('name')//2 is actually the first level
                ->addAttributeToFilter('entity_id', array('in' => $productCats))
                ->addAttributeToFilter('level',2)
                ->addAttributeToFilter('is_active', 1);

        
        foreach( $categories as $category ){
            $levelArray[] = $category->getId();
        }

        
        foreach( $productCats as $productCat ){
            if( in_array($productCat, $levelArray) ){
                if( empty($ids) ){
                    $ids .= $productCat;
                }else{
                    $levelSplit[] = $ids;
                    $ids = '';
                    $ids .=$productCat;
                }
            }else{
                $ids .= ','.$productCat;
            }

            if( $totalCount ==  $count )
                $levelSplit[] = $ids;
            $count++;
        }

        return $levelSplit;
    }

    public function minusCategoryLevel( $categoryValue ){
        return  substr($categoryValue, 0, strrpos( $categoryValue, ',') );
    }

    public function getSizeGuideCSV( $levelSplited , $_manufacturerId , $storeId ){
        $collection =  Mage::getModel('sizeguide/sizeguide')
                  ->getCollection()
                  ->addFieldToFilter('status' , 1 )
                  ->addFieldToFilter('store_id', array(
                                    array('like' => '%,'.$storeId.',%'), //spaces on each side
                                    array('like' => '%'.$storeId.',%'),
                                    array('like' => '%'.$storeId.'%'), //space before and ends with $needle
                                    array('like' => '%,'.$storeId),
                                    array('like' => '0')))
                  ->addFieldToFilter('brand_ids', array(
                                    array('like' => '%,'.$_manufacturerId.',%'), //spaces on each side
                                    array('like' => '%'.$_manufacturerId.',%'),
                                    array('like' => '%,'.$_manufacturerId),
                                    array('like' => 'all'),
                                    array('like' => $_manufacturerId)))
                  ->addFieldToFilter('categories', array(
                                    array('like' => '%|'.$levelSplited.'|%'), //spaces on each side
                                    array('like' => '%'.$levelSplited.'|%'),
                                    array('like' => '%|'.$levelSplited),
                                    array('like' => $levelSplited)));
        $sortBy = 'brand_ids = "all" asc limit 1';
        $collection->getSelect()->order(new Zend_Db_Expr($sortBy));
        if( empty($collection->getData()) ){
           if( count( explode(',', $levelSplited) ) == 1 )
                return $collection;
           $levelSplited = $this->minusCategoryLevel( $levelSplited );
           $collection = $this->getSizeGuideCSV( $levelSplited , $_manufacturerId , $storeId );
        }
        return $collection;
    }
}