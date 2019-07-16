<?php
class Shreeji_Manageimage_Model_Observer 
{
    public function deleteDuplicateImage(Varien_Event_Observer $observer)
    {
        $product=$observer->getEvent()->getProduct();
        $sku=$product->getSku();
        try{
            if(!empty($sku)){
                $model=Mage::getModel('manageimage/manageimage');
                $manageimagedata  = Mage::getModel('manageimage/manageimage')->getCollection()
                ->addFieldToFilter('sku',$sku);
                if(!empty($manageimagedata)){
                    foreach($manageimagedata as $singleimagedata){
                        if(!empty($singleimagedata['manageimage_id'])){
                            $model->load($singleimagedata['manageimage_id'])->delete();
                        }
                    }
                }
            }
        }
        catch (Exception $e)    {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        } 
    }
}