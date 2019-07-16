<?php

class Progos_Catalog_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * @param Varien_Event_Observer $observer
     */
    public function fixConfigurableProductImageGallery(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getProduct();

        if ($product->getTypeId() != Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) return;

        Mage::helper('progos_catalog')->resetDefaultMediaGalleryImage($product);
    }
    
    public function addCacheButton()
    {
        try {
            $layout = Mage::app()->getLayout();

            $productEditBlock = $layout->getBlock('product_edit');
            $entity_id = Mage::app()->getRequest()->getParam('id');
            $myButton = $layout->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'     => Mage::helper('progos_catalog')->__('Clesr Cache'),
                    'onclick'   => "clearProductCache('{$this->getUrl('*/fpc/product', array('id' => $entity_id) )}', true)",
                    'class'  => 'save'
                ));

            $container = $layout->createBlock('core/text_list', 'button_container');
            $saveAndContinueButton = $productEditBlock->getChild('save_and_edit_button');
            $container->append($saveAndContinueButton);
            $container->append($myButton);
            $productEditBlock->setChild('save_and_edit_button', $container);
        } catch (Exception $exc) {
            Mage::log('Cache Button: Product '. $exc->getMessage(), Zend_Log::INFO, 'clear_cache_button.log');
        }
    }
    
    
    public function getUrl($route = '', $params = array())
    {
        return Mage::getModel('core/url')->getUrl($route, $params);
    }
}