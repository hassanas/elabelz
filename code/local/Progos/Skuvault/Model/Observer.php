<?php

/**
 * @Author Hassan Ali Shahzad
 * @Date 20-06-2017
 *
 */
class Progos_Skuvault_Model_Observer
{
    /**
     * @param $observer
     * @return $this
     */
    public function addButton(Varien_Event_Observer $observer)
    {
        $container = $observer->getBlock();
        $buttonUrl = Mage::helper('adminhtml')->getUrl('*/brand/index');
        if(null !== $container && $container->getType() == 'shopbybrand/adminhtml_brand') {
            $data = [
                'label'     => 'Sync Brands with SKU-Vault Supplier',
                'class'     => 'sync-brands-class',
                'onclick'   => 'setLocation(\' '  . $buttonUrl . '\')',
            ];
            $container->addButton('sync_brands', $data);
        }

        return $this;
    }

    /**
     * @param $observer
     * This function called after product modification
     * and change the status of field skuvault_updated back to 0 for next pick in table catalog_product_entity
     * if products manufacturer attr change
     */
    public function updateSkuvaultProductStatus(Varien_Event_Observer $observer){
        $product = $observer->getProduct();
        if(Mage::getResourceModel('catalog/product')->getAttributeRawValue($product->getEntityId(), 'manufacturer') != $product->getManufacturer()) {
            $productSku[] = $product->getSku();
            $flag = 0;
            Mage::helper("progos_skuvault")->updatedSkuvaultProductCollection($productSku,$flag);
        }
    }
}