<?php

class Shreeji_Manageimage_Model_Manageimage extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('manageimage/manageimage');
    }

    public function FindDuplicateImage(){
        $already=Mage::getModel('manageimage/manageimage')->getCollection()->getData();
        $producttable = Mage::getSingleton("core/resource")->getTableName("catalog_product_entity"); 
        $query="SELECT entity_id FROM $producttable";
        $AllProdictIDs=$this->getReadConnection()->fetchAll($query);
        $total = count($AllProdictIDs);
        $nameattributeid= Mage::getSingleton("eav/config")->getAttribute('catalog_product', "name")->getData('attribute_id');
        $baseattributeid= Mage::getSingleton("eav/config")->getAttribute('catalog_product', "image")->getData('attribute_id');
        $count = 0;
        if($total>0){
            foreach($AllProdictIDs as $singleproductid)
            {    
                $_images="";
                $sku="";
                $productname="";
                $_md5_values="";
                $mediatable=Mage::getSingleton("core/resource")->getTableName("catalog_product_entity_media_gallery"); 
                $querymedia="SELECT value FROM $mediatable WHERE entity_id=".$singleproductid['entity_id'];
                $_imagesdb=$this->getReadConnection()->fetchAll($querymedia);
                foreach($_imagesdb as $sigleimage){
                    $_images[]=$sigleimage['value'];
                }
                $skutable=Mage::getSingleton("core/resource")->getTableName("catalog_product_entity");
                $skuquery="SELECT sku from $skutable where entity_id=".$singleproductid['entity_id'];
                $skudb=$this->getReadConnection()->fetchAll($skuquery); 
                foreach($skudb as $siglesku){
                    $sku=$siglesku['sku'];
                }
                $nametable=Mage::getSingleton("core/resource")->getTableName("catalog_product_entity_varchar");
                $namequery="SELECT value from $nametable where attribute_id=$nameattributeid AND entity_id=".$singleproductid['entity_id'];
                $namedb=$this->getReadConnection()->fetchAll($namequery);
                foreach($namedb as $singlename){
                    $productname=$singlename['value'];
                }
                $basequery="SELECT value from $nametable where attribute_id=$baseattributeid AND entity_id=".$singleproductid['entity_id'];
                $basedb=$this->getReadConnection()->fetchAll($basequery);
                foreach($basedb as $singlebase){
                    $base_image=$singlebase['value'];
                }
                $_md5_values = array();
                if($base_image != 'no_selection' && $base_image !=NULL) {
                    $filepath =  Mage::getBaseDir('media') .'/catalog/product' . $base_image  ;
                    if(file_exists($filepath))
                        $_md5_values[] = md5(file_get_contents($filepath));
                }
                if($_images){
                    foreach($_images as $_image){
                        $insert=false; 
                        $skusame=false;
                        $skusameimage="";
                        if($_image == $base_image)
                            continue;
                        $filepath =  Mage::getBaseDir('media') .'/catalog/product' . $_image  ;
                        if(file_exists($filepath))
                            $md5 = md5(file_get_contents($filepath));
                        else
                            continue;
                        if(in_array($md5, $_md5_values)){
                            foreach($already as $alreadysingle){
                                if($alreadysingle['sku']==$sku){
                                    $skusame=true;
                                    if($alreadysingle['filename']==$_image){
                                        unset($skusameimage);
                                        $skusame=true;
                                        break;
                                    }
                                    else{
                                        $skusameimage=$_image;
                                        $skusame=false;
                                    }
                                }
                                else{
                                    $skusameimage=$_image;
                                }
                            }
                            if((!empty($skusameimage)|| empty($already)) && $skusame==false){
                                $model  = Mage::getModel('manageimage/manageimage');                                            
                                $model->setProductname($productname)
                                ->setFilename($_image)
                                ->setSku($sku)
                                ->save();
                            }

                        } else {
                            $_md5_values[] = $md5;
                        }    
                    }
                } 
            }    
        }
    }

    public function getReadConnection(){
        $resource = Mage::getSingleton('core/resource')->getConnection('core_read');
        return $resource;

    }
}