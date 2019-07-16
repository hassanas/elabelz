<?php
class Progos_Ccache_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout();
        return $this;
    }
	
    public function indexAction()
    {   
        Mage::register('ccache_key', 'product');
        $this->_initAction();
        $this->renderLayout();
    }
    public function categoryAction()
    {   
        Mage::register('ccache_key', 'category');
        $this->_initAction();
        $this->renderLayout();
    }
    
    public function manufacturerAction()
    {   
        Mage::register('ccache_key', 'manufacturer');
        $this->_initAction();
        $this->renderLayout();
    }
    
    public function warmupproductAction()
    {
        $this->_initAction();
        $this->renderLayout();
    }
    
    public function warmupcategoryAction()
    {
        $this->_initAction();
        $this->renderLayout();
    }
    
    public function warmupmanufacturerAction()
    {
        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * This Function will clear the Brand page cache and also clear the
     * Mobile layered nave filters cache (specific to that brand) as well to sync layered nav filters options after product save
     */
    public function clearBrandAction()
    {
        $id = (int) $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $lesti = Mage::getSingleton('fpc/fpc');
                $lesti->clean(sha1('brand_view_' . $id));
                $lesti->clean(sha1('api_filters_layerednav_manufacturer_' . $id));
                $this->updateCount($id);
                echo 'Cache is cleared for brand id '. $id;
            }
            catch (Exception $e){
                echo $e->getMessage();
            }
        } else {
            echo 'brand id not found';
        }
    }
    
    public function clearProductAction()
    {
        $id = (int) $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $lesti = Mage::getSingleton('fpc/fpc');
                $lesti->clean(sha1('product_' . $id));
                $lesti->clean(sha1('api_products_productasso_product_' . $id));
                $this->updateCount($id);
                echo 'Cache is cleared for product id '. $id;
            }
            catch (Exception $e){
                echo $e->getMessage();
            }
        } else {
            echo 'product id not found';
        }
    }
    
    public function clearCategoryAction()
    {
        $id = (int) $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $lesti = Mage::getSingleton('fpc/fpc');
                $lesti->clean(sha1('category_' . $id));
                $lesti->clean(sha1('api_products_productsfilter_category_' . $id));
                $this->updateCount($id);
                echo 'Cache is cleared for category id '. $id;
            }
            catch (Exception $e){
                echo $e->getMessage();
            }
        }
    }
    /**
     * 
     * @param type $id
     */
    public function updateCount($id)
    {
        $ccache = Mage::getModel('ccache/ccache');
        $ccache->load($ccache->getIdByTypeId($id));
        if($ccache->getId()) {
            $ccache->setCount(0);
            $ccache->save();
        }
    }
}
