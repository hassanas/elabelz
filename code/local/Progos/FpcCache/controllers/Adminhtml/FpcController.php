<?php
class Progos_FpcCache_Adminhtml_FpcController extends Mage_Adminhtml_Controller_Action 
{
    public function brandAction()
    {
        $id = (int) $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $lesti = Mage::getSingleton('fpc/fpc');
                $lesti->clean(sha1('brand_view_' . $id));
                echo 'Cache is cleared for brand id '. $id;
            }
            catch (Exception $e){
                echo $e->getMessage();
            }
        } else {
            echo 'brand id not found';
        }
    }
    
    public function productAction()
    {
        $id = (int) $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $lesti = Mage::getSingleton('fpc/fpc');
                $lesti->clean(sha1('product_' . $id));
                echo 'Cache is cleared for product id '. $id;
            }
            catch (Exception $e){
                echo $e->getMessage();
            }
        } else {
            echo 'product id not found';
        }
    }
    
    public function categoryAction()
    {
        $id = (int) $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $lesti = Mage::getSingleton('fpc/fpc');
                $lesti->clean(sha1('category_' . $id));
                echo 'Cache is cleared for category id '. $id;
            }
            catch (Exception $e){
                echo $e->getMessage();
            }
        }
    }
    
}
