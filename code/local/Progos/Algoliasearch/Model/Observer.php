<?php

/**
 * Progos_Algoliasearch.
 * @category Elabelz
 * @Author Hassan Ali Shahzad   <hassan.ali@progos.org>
 * @Date 13-07-2018
 *
 */
class Progos_Algoliasearch_Model_Observer extends Mage_Core_Model_Abstract
{
    protected static $_currencies;

    /**
     * This function will add extra paramens to sync for Mobile App Like below
     * price_app
     * @param Varien_Event_Observer $observer
     */
    public function addDataForMobileApp(Varien_Event_Observer $observer)
    {

        $productData = $observer->getEvent()->getProductData();
        $product     = $observer->getEvent()->getData('productObject');
        $store       = $product->getStore();
        if (Mage::helper('core')->isModuleEnabled('Mage_Weee') &&
            Mage::helper('weee')->getPriceDisplayType($product->getStore()) == 0) {
            $weeeTaxAmount = Mage::helper('weee')->getAmountForDisplay($product);
        } else {
            $weeeTaxAmount = 0;
        }
        $baseCurrencyCode = $store->getBaseCurrencyCode();
        $currency_code = $store->getCurrentCurrencyCode();

        $taxHelper = Mage::helper('tax');
        $directoryHelper = Mage::helper('directory');

        $price = (double)$taxHelper->getPrice($product, $product->getPrice(), false, null, null, null, $product->getStore(), null);
        $price = $directoryHelper->currencyConvert($price, $baseCurrencyCode, $currency_code);
        $price += $weeeTaxAmount;

        $priceAppDefault = 0;
        $priceApp['default'] = $price;
        $priceApp['default_formated'] = $this->formatPrice($price, false, $currency_code);
        $priceApp['special_from_date'] = strtotime($product->getSpecialFromDate());
        $priceApp['special_to_date'] = strtotime($product->getSpecialToDate());
        $priceApp['currency'] = Mage::helper('progos_algoliasearch')->__($currency_code);
        $priceApp['special_price'] = 0;

        $special_price = (double)$taxHelper->getPrice($product, $product->getFinalPrice(), false, null, null, null, $product->getStore(), null);
        $special_price = $directoryHelper->currencyConvert($special_price, $baseCurrencyCode, $currency_code);
        $special_price += $weeeTaxAmount;


        if ($special_price && $special_price < $price) {
            $priceApp['special_price'] = $special_price;
        }

        $productData->setData('price_app', $priceApp);

        // Get 92-color option_id
        $colorLabels = $productData->getColor();
        if(!empty($colorLabels)){
            $colorLabels = Mage::helper('progos_algoliasearch')->getOptionIds( 92,$colorLabels);
        }
        $productData->setData('color_app', $colorLabels);
        // Get 148-size option_id
        $sizeLabels = $productData->getSize();
        if(!empty($sizeLabels)){
            $sizeLabels = Mage::helper('progos_algoliasearch')->getOptionIds(148,$sizeLabels);
        }
        $productData->setData('size_app', $sizeLabels);

        // price_app_default facet
        $priceAppDefault = ($special_price && $special_price < $price)?$special_price:$price;
        $productData->setData('price_app_default', $priceAppDefault);
        return;
    }

    /**
     * This function will add custom product setting
     * like I added price_app_default custom filed as a facet
     * @param Varien_Event_Observer $observer
     */
    public function addCustomIndex(Varien_Event_Observer $observer){
        $indexSettingsObj = $observer->getEvent()->getIndexSettings();
        $attributesForFaceting = $indexSettingsObj->getData('attributesForFaceting');
        $attributesForFaceting[] = "price_app_default";
        $indexSettingsObj->setData('attributesForFaceting', $attributesForFaceting);
        return;
    }

    /**
     * This function will add isBrandPage to true if we have brand pages and also include its config values
     * 
     * @param Varien_Event_Observer $observer
     * @throws Mage_Core_Model_Store_Exception
     */
    public function updatConfigurations(Varien_Event_Observer $observer){
        $app = Mage::app();
        $configuration = $observer->getEvent()->getConfiguration();
        $modifiedConfiguration = $configuration->getdata();
        $modifiedConfiguration['isBrandPage']  = false;
        $modifiedConfiguration['brandLabel']   = "";
        if($modifiedConfiguration['instant']['enabled'] && $app->getRequest()->getModuleName() == 'brand' && $app->getRequest()->getActionName() == 'view'){
            $modifiedConfiguration['isSearchPage'] = true;
            $modifiedConfiguration['isBrandPage']  = true;
            $brandId    = $app->getRequest()->getParam('id');
            $store      = $app->getStore();
            $storeId    = $store->getId();
            $brandLabel = Mage::getModel('shopbybrand/brandvalue')->loadAttributeValue($brandId, $storeId, 'name');
            $brandLabel = $brandLabel->getValue();
            if($brandLabel == null){
                $brandLabel = Mage::getModel('shopbybrand/brand')->getCollection()
                    ->addFieldToSelect('name')
                    ->addFieldToFilter('brand_id',$brandId)
                    ->getFirstItem();
                $brandLabel = $brandLabel->getName();
            }
            $modifiedConfiguration['brandLabel']  = $brandLabel;
            $configuration->setData($modifiedConfiguration);
        }
        return;
    }

    protected function formatPrice($price, $includeContainer, $currency_code)
    {
        /** @var Mage_Directory_Model_Currency $directoryCurrency */
        $directoryCurrency = Mage::getModel('directory/currency');

        if (!isset(static::$_currencies[$currency_code])) {
            static::$_currencies[$currency_code] = $directoryCurrency->load($currency_code);
        }

        /** @var Mage_Directory_Model_Currency $currency */
        $currency = static::$_currencies[$currency_code];

        if ($currency) {
            return $currency->format($price, array(), $includeContainer);
        }

        return $price;
    }


}