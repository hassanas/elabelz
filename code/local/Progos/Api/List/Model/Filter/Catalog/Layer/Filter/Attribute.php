<?php

/**
 * this class created due to Mage::getBlockSingleton('page/html_pager')->getPageVarName() as in api Block are not loaded and this function called in Amasty_Shopby_Model_Catalog_Layer_Filter_Attribute
 * line no 173 in addState()
 * @todo Need to get a way to load Only Specific Block in rest api
 *
 * @author gul.muhammad@progos.org
 */
class Progos_Api_List_Model_Filter_Catalog_Layer_Filter_Attribute extends Amasty_Shopby_Model_Catalog_Layer_Filter_Attribute
{
    /**
     * Apply attribute option filter to product collection
     *
     * @param   Zend_Controller_Request_Abstract $request
     * @param   Varien_Object $filterBlock
     * @return  Mage_Catalog_Model_Layer_Filter_Attribute
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $currentVals = Mage::helper('amshopby')->getRequestValues($this->_requestVar);
        if ($currentVals) {

            $attributeCode = $this->getAttributeModel()->getAttributeCode();
            /** @var Amasty_Shopby_Helper_Attributes $attributeHelper */
            $attributeHelper = Mage::helper('amshopby/attributes');
            if (!$attributeHelper->lockApplyFilter($attributeCode, 'attr')) {
                return $this;
            }

            $this->applyFilterToCollection($currentVals);

            // check if need to add state
            $controller = Mage::app()->getRequest()->getControllerModule();
            $branding = $controller == 'Amasty_Shopby'
                && count($currentVals) == 1
                && trim(Mage::getStoreConfig('amshopby/brands/attr')) == $attributeCode;
            if (!$branding) {
                $this->addState($currentVals);
            }

            if (count($currentVals) > 1) {
                /** @var Amasty_Shopby_Helper_Layer_Cache $cache */
                $cache = Mage::helper('amshopby/layer_cache');
                $cache->limitLifetime(Amasty_Shopby_Helper_Layer_Cache::LIFETIME_SESSION);
            }
        }
        return $this;
    }

    protected function addState($currentVals)
    {
        //generate Status Block
        $attribute = $this->getAttributeModel();
        $text = '';
        $options = Mage::helper('amshopby/attributes')->getAttributeOptions($attribute->getAttributeCode());

        $children = array();

        foreach ($options as $option) {
            $k = array_search($option['value'], $currentVals);
            if (false !== $k){

                $exclude = $currentVals;
                unset($exclude[$k]);
                $exclude = implode(',', $exclude);
                if (!$exclude)
                    $exclude = null;

                $query = array(
                    $this->getRequestVar() => $exclude,
                    'p' => null // exclude current page from urls
                );
                $url = Mage::helper('amshopby/url')->getFullUrl($query);

                $text .= $option['label'] . " ";

                $children[] = array(
                    'label' => $option['label'],
                    'url' => $url,
                );
            }
        }

        /** @var Amasty_Shopby_Model_Catalog_Layer_Filter_Item $state */
        $state = $this->_createItem($text, $currentVals)
            ->setVar($this->_requestVar);

        if (count($children) > 1) {
            $state->setData('children', $children);
        }

        $this->getLayer()->getState()->addFilter($state);
    }
}
