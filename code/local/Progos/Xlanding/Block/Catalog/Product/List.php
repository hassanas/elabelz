<?php
/**
 * @author : Humera Batool
 * @created_at : 19 April 2018
 * @purpose: seprating ctaegory default sort order from brand on line 16 to 22 and line 38 to 42
 */
class Progos_Xlanding_Block_Catalog_Product_List extends Mage_Catalog_Block_Product_List
{
    protected $_requiredAttributes = array('color', 'small_image');

    /**
     * @param Mage_Catalog_Model_Product $product
     * @return string
     */
    public function getProductHtml($product)
    {
        $block = $this->getLayout()->createBlock('catalog/product_list')->setTemplate('catalog/product/list/product.phtml');

        if ($children = $this->_getConfigurableChildren($product)) {
            $html = '';

            foreach ($children as $child) {
                $product->setChild($child);
                $html .= $block->setProduct($product)->toHtml();
            }

            return $html;
        }

        return $block->setProduct($product)->toHtml();
    }

    /**
     * Get configurable product's children with required attributes
     * Returns null if only 1 child is found
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array|null
     */
    protected function _getConfigurableChildren($product)
    {
        if ($product->getTypeId() !== Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE) {
            return null;
        }

        $children = Mage::getModel('catalog/product_type_configurable')->getUsedProducts($this->_getRequiredAttributeIds(), $product);

        // We store the color as the key so we can check if we are returning only one color
        // Colors can repeat if the product has multiple sizes for one color value
        $uniqueColorChildren = array();
        foreach ($children as $key => $child) {
            if (!array_key_exists($child->getColor(), $uniqueColorChildren)) {
                $uniqueColorChildren[$child->getColor()] = Mage::getModel('catalog/product')->load($child->getId());
            }
        }

        if (count($uniqueColorChildren) <= 1) {
            return null;
        }

        return $uniqueColorChildren;
    }

    /**
     * @return array
     */
    protected function _getRequiredAttributeIds()
    {
        $requiredAttributeIds = array();

        foreach ($this->_requiredAttributes as $code) {
            $requiredAttributeIds[] = Mage::getModel('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, $code)->getId();
        }

        return $requiredAttributeIds;
    }

    protected function _beforeToHtml()
    {
        $toolbar = $this->getToolbarBlock();

        // called prepare sortable parameters
        $collection = $this->_getProductCollection();

        //checking if the page is brand page or category page
        $request = Mage::app()->getRequest();
        $pathinfo = $request->getPathInfo();
        $brand_page = false;
        if (strpos($pathinfo, "brand/index/view/") !== false || strpos($pathinfo, "brand" ) !== false) {
            $brand_page = true;
        }

        // use sortable parameters
        if ($orders = $this->getAvailableOrders()) {
            $toolbar->setAvailableOrders($orders);
        }
        if ($sort = $this->getSortBy()) {
            $toolbar->setDefaultOrder($sort);
        }
        if ($dir = $this->getDefaultDirection()) {
            $toolbar->setDefaultDirection($dir);
        }
        if ($modes = $this->getModes()) {
            $toolbar->setModes($modes);
        }

        //assigning sort and order value to brand pages
        if ($brand_page == true) {
            $toolbar->setDefaultDirection('desc');
            $toolbar->setDefaultOrder('created_at');
        }

        if($this->getSortBy() == "created_at"){
            $toolbar->setDefaultDirection('desc');
        }
        // set collection to toolbar and apply sort
        $toolbar->setCollection($collection);

        $this->setChild('toolbar', $toolbar);
        Mage::dispatchEvent('catalog_block_product_list_collection', array(
            'collection' => $this->_getProductCollection()
        ));

        $this->_getProductCollection()->load();

        return parent::_beforeToHtml();
    }

    protected function _getProductCollection()
    {
        if (is_null($this->_productCollection)) {
            if (Mage::getStoreConfig('amlanding/xlanding/merchandising')) {
                $request = Mage::app()->getRequest();
                $module = $request->getControllerModule();
                $module_controller = $request->getControllerName();
                $module_controller_action = $request->getActionName();
                if ($module == 'Rewrite_CAmastyXlanding' && $module_controller == 'page' && $module_controller_action == 'view') {
                    $page = Mage::registry('amlanding_page');
                    $layer = $this->getLayer();
                    /* @var $layer Mage_Catalog_Model_Layer */
                    if ($this->getShowRootCategory()) {
                        $this->setCategoryId(Mage::app()->getStore()->getRootCategoryId());
                    }
                    $origCategory = null;
                    if ($this->getCategoryId()) {
                        $category = Mage::getModel('catalog/category')->load($this->getCategoryId());
                        if ($category->getId()) {
                            $origCategory = $layer->getCurrentCategory();
                            $layer->setCurrentCategory($category);
                            $this->addModelTags($category);
                        }
                    }

                    $this->_productCollection = $page->getPageProducts();

                    if ($origCategory) {
                        $layer->setCurrentCategory($origCategory);
                    }
                    return $this->_productCollection;
                }
            }

            $layer = $this->getLayer();
            /* @var $layer Mage_Catalog_Model_Layer */
            if ($this->getShowRootCategory()) {
                $this->setCategoryId(Mage::app()->getStore()->getRootCategoryId());
            }

            // if this is a product view page
            if (Mage::registry('product')) {
                // get collection of categories this product is associated with
                $categories = Mage::registry('product')->getCategoryCollection()
                    ->setPage(1, 1)
                    ->load();
                // if the product is associated with any category
                if ($categories->count()) {
                    // show products from this category
                    $this->setCategoryId(current($categories->getIterator()));
                }
            }

            $origCategory = null;
            if ($this->getCategoryId()) {
                $category = Mage::getModel('catalog/category')->load($this->getCategoryId());
                if ($category->getId()) {
                    $origCategory = $layer->getCurrentCategory();
                    $layer->setCurrentCategory($category);
                    $this->addModelTags($category);
                }
            }
            $this->_productCollection = $layer->getProductCollection();

            $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());

            if ($origCategory) {
                $layer->setCurrentCategory($origCategory);
            }
        }

        return $this->_productCollection;
    }

}