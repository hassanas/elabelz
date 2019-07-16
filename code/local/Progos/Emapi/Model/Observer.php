<?php

class Progos_Emapi_Model_Observer
{
    public function adminhtmlWidgetContainerHtmlBefore($event)
    {
        $block = $event->getBlock();
        //$this->getOrder()->getRealOrderId();
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_View) {
            $order = $block->getOrder();
            $reservedOrderId = $order->getIncrementId();
            $quoteId = $order->getQuoteId();
            $quote = Mage::getModel("sales/quote")->loadByIdWithoutStore($quoteId);
            $allItems = $quote->getAllItems();
            $visibleItems = $quote->getAllVisibleItems();
            $mdlEmapi = Mage::getModel('restmob/quote_index');
            $id = $mdlEmapi->getIdByRealOrderId($reservedOrderId);
            if ((count($allItems) == 0 || count($visibleItems) == 0 || (count($allItems) / 2) != count($visibleItems)) && $id) {
                $message = 'Are you sure you want to repair this order?';
                $block->addButton('emapi_repair_order', array(
                    'label' => 'Repair Order',
                    'onclick' => "confirmSetLocation('{$message}', '{$block->getUrl('*/RepairOrder/index')}')",
                    'class' => 'go'
                ));
            }
        }
    }

    /*
     * Function to clear FPC cache for API
     */
    public function clearCategoriesCacheForApi(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getProduct();
        if($product->getTypeId() == 'configurable') {
            $category_ids = $product->getCategoryIds();
            $category_ids[] = 2;
            $type = "category";
            Mage::helper('fpccache')->addDataToCacheTable($category_ids, $type);
        }
    }
}

?>