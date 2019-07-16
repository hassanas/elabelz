<?php
class Progos_Emapi_Model_Sales_Resource_Quote_Item_Collection extends Mage_Sales_Model_Resource_Quote_Item_Collection
{
    /**
     * Add products to items and item options
     *
     * @return Mage_Sales_Model_Resource_Quote_Item_Collection
     */
    protected function _assignProducts()
    {
        Varien_Profiler::start('QUOTE:'.__METHOD__);
        $productIds = array();
        foreach ($this as $item) {
            $productIds[] = (int)$item->getProductId();
        }
        $this->_productIds = array_merge($this->_productIds, $productIds);

        $productCollection = Mage::getModel('catalog/product')->getCollection()
            ->setStoreId($this->getStoreId())
            ->addIdFilter($this->_productIds)
            ->addAttributeToSelect(Mage::getSingleton('sales/quote_config')->getProductAttributes())
            ->addOptionsToResult()
            ->addStoreFilter()
            ->addUrlRewrite()
            ->addTierPriceData();

        Mage::dispatchEvent('prepare_catalog_product_collection_prices', array(
            'collection'            => $productCollection,
            'store_id'              => $this->getStoreId(),
        ));
        Mage::dispatchEvent('sales_quote_item_collection_products_after_load', array(
            'product_collection'    => $productCollection
        ));

        $recollectQuote = false;
        foreach ($this as $item) {
            $product = $productCollection->getItemById($item->getProductId());
            if ($product) {
                $product->setCustomOptions(array());
                $qtyOptions         = array();
                $optionProductIds   = array();
                foreach ($item->getOptions() as $option) {
                    /**
                     * Call type-specific logic for product associated with quote item
                     */
                    $product->getTypeInstance(true)->assignProductToOption(
                        $productCollection->getItemById($option->getProductId()),
                        $option,
                        $product
                    );

                    if (is_object($option->getProduct()) && $option->getProduct()->getId() != $product->getId()) {
                        $optionProductIds[$option->getProduct()->getId()] = $option->getProduct()->getId();
                    }
                }

                if ($optionProductIds) {
                    foreach ($optionProductIds as $optionProductId) {
                        $qtyOption = $item->getOptionByCode('product_qty_' . $optionProductId);
                        if ($qtyOption) {
                            $qtyOptions[$optionProductId] = $qtyOption;
                        }
                    }
                }

                $item->setQtyOptions($qtyOptions)->setProduct($product);
            } else {
                $item->isDeleted(true);
                $recollectQuote = true;
            }
            /*
             * Code added to avoid newrelic error by Naveed Abbas
             */
            if($item->getProductId()) {
                $item->checkData();
            }
        }
        if ($recollectQuote && $this->_quote) {
            $this->_quote->collectTotals();
        }
        Varien_Profiler::stop('QUOTE:'.__METHOD__);

        return $this;
    }
}
