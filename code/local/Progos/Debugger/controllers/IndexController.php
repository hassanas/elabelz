<?php
class Progos_Debugger_IndexController extends Mage_Core_Controller_Front_Action {        
    
    public function indexAction() {
        //echo 'debug mode';
        
        $product = Mage::getModel('catalog/product')->load((int) $_GET['product_id']);
        
        echo '<pre>'; print_r($product->getData());
        /*
        $helper = Mage::helper('debugger');
        $model = Mage::getModel('debugger/debug');
        echo '<pre>'; print_r($helper); print_r($model);
        
         * 
         */
    }
    
    
}