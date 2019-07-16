<?php

class Progos_Productexport_Model_Croncategory
{
    public function __construct(){
        Mage::init();
    }

    public function exprotcsv(){
        $this->getProductCollection();
    }

    public function categoryPath($product)
    {
            $currentCatIds = $product->getCategoryIds();
            $categoryLevelCollection = Mage::getModel('catalog/category')->getCollection()
                ->addAttributeToSelect('name')//2 is actually the first level
                ->addAttributeToFilter('entity_id', array('in' => $currentCatIds))
                ->addAttributeToFilter('level',2)
                ->addAttributeToFilter('is_active', 1);
            $counter_category = 0;
            $counter_newarrivals = 0;
            $counter_sales = 0;
            foreach($categoryLevelCollection as $cat):
                if($cat->getName()!="Sales" && $cat->getName()!="Create Your Own" && $cat->getName()!="New Arrivals"):
                    $counter_category = 1;
                    $curen_category_name = $cat->getName();
                elseif($cat->getName() == "Create Your Own" || $cat->getName() == "New Arrivals"):
                    $counter_newarrivals = 1;
                elseif($cat->getName() == "Sales"):
                    $counter_sales = 1;
                endif;
            endforeach;
            $path = array();
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
                    $topParent = Mage::getModel('catalog/category')->load($ids[2]);
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
                                    'label' => $categories[$categoryId]->getName()
                                );
                            }
                        }
                        break;
                    endif;
                elseif($counter_category != 1 && $counter_newarrivals == 1):
                    if($topParent->getName()=="Create Your Own" || $topParent->getName()=="New Arrivals"):
                        $pathInStore = $cat->getPathInStore();
                        $pathIds = array_reverse(explode(',', $pathInStore));

                        $categories = $cat->getParentCategories();

                        // add category path breadcrumb
                        foreach ($pathIds as $categoryId) {
                            if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                                $path['category'.$categoryId] = array(
                                    'label' => $categories[$categoryId]->getName()
                                );
                            }
                        }
                        break;
                    endif;
                elseif($counter_category != 1 && $counter_newarrivals != 1 && $counter_sales == 1):
                    if($topParent->getName() == "Sales"):
                        $pathInStore = $cat->getPathInStore();
                        $pathIds = array_reverse(explode(',', $pathInStore));

                        $categories = $cat->getParentCategories();

                        // add category path breadcrumb
                        foreach ($pathIds as $categoryId) {
                            if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                                $path['category'.$categoryId] = array(
                                    'label' => $categories[$categoryId]->getName()
                                );
                            }
                        }
                        break;
                    endif;
                endif;
            endforeach;
            $categoryPath = "";
            foreach ($path as $category ):
                $label = str_replace( '&',' and ',str_replace( '&amp;','',  $category['label'] ));
                $categoryPath .= $label." -> ";
            endforeach;
            return rtrim($categoryPath , ' -> ');
    }

    public function getProductCollection(){
        ini_set('memory_limit', '-1');
        if( !file_exists( Mage::getBaseDir().DS.'media'.DS.'var'.DS.'import') ){
            mkdir(Mage::getBaseDir().DS.'media'.DS.'var'.DS.'import' ,0777, true);
        }
        $mainDirectory = Mage::getBaseDir().DS.'media'.DS.'var'.DS.'import'.DS.'productcategoryexport.csv';

        if( !file_exists( $mainDirectory ) ){
            $data = "Elabelz Sku,Category Path,Product Type,Product Id \n";
            chmod($mainDirectory, 0777);
        }else{
            unlink($mainDirectory);
            $data = "Elabelz Sku,Category Path,Product Type,Product Id \n";
            chmod($mainDirectory, 0777);
        }

        file_put_contents($mainDirectory, $data , FILE_APPEND );
        chmod($mainDirectory, 0777);
        $collection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('type_id', array('eq' => 'simple'))
            ->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');

        $parentObject = array();
        foreach( $collection as $product ){
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            if( !empty( $parentIds ) ){
                $parentId = $parentIds[0];
                if( !isset( $parentObject[$parentId] ) ){
                    $parentObject[$parentId] = array();

                    $configProduct = Mage::getModel('catalog/product')
                        ->getCollection()
                        ->addAttributeToSelect('*')
                        ->addAttributeToFilter('entity_id', array('eq' => $parentId));
                    $data = "Elabelz Sku,Category Path,Product Type,Product Id \n";
                    foreach( $configProduct as $config){
                        $parentObject[$parentId]['categoryPath']            =    $this->categoryPath($config);
                        $categoryPath   =   $parentObject[$parentId]['categoryPath'];
                        $cdata = $config->getSku().","; $cdata .= $categoryPath.",";
                        $cdata .= $config->getTypeId().","; $cdata .= $config->getId().","; $cdata .= " \n";
                        file_put_contents( $mainDirectory, $cdata , FILE_APPEND|LOCK_EX );
                    }
                }else{
                    $categoryPath       =   $parentObject[$parentId]['categoryPath'];
                }
            }else{
                $categoryPath = "";
            }

            $data  = $product->getSku().",";  $data .= $categoryPath.","; $data .= $product->getTypeId().","; $data .= $product->getId().","; $data .= " \n";
            file_put_contents( $mainDirectory, $data , FILE_APPEND|LOCK_EX );
        }
        return true;
    }
}