<?php
/*
** @author: Sooraj Malhi <sooraj.malhi@progos.org>
** @package: Rewrite_CAmastyXlanding  
** @description: Rewrite Amasty_Xlanding controller viewAction for canonical url issue
*/ 
require_once (Mage::getModuleDir('controllers','Amasty_Xlanding').DS.'PageController.php');

class Rewrite_CAmastyXlanding_PageController extends Amasty_Xlanding_PageController {

    /**
     * View CMS page action
     *
     */
    public function viewAction()
    {
    	/* @var $hlp Amasty_Xlanding_Helper_Data */
    	$hlp = Mage::helper('amlanding');
    	
        $pageId = $this->getRequest()
            ->getParam('page_id', null);
		
        /* @var $page Amasty_Xlanding_Model_Page */    
        $page = Mage::getModel('amlanding/page')->load($pageId);
        if (!$page) {
        	return;
        }
        
        /*
         * Store page for future to use in helper
         */
        Mage::register('amlanding_page', $page);

        Mage::getSingleton('catalog/design')->applyCustomDesign((string) $page->getCustomDesign());
        
        $page->applyPageRules();

        $this->_initCategory(Mage::getSingleton('catalog/layer')->getCurrentCategory());

        $this->loadLayout();
        
        $this->enableLayeredNavigation();
		
        /*
         * Apply custom template and layout update if set
         */
        $root = $this->getLayout()->getBlock('root');
        if ($root) {
                $this->_applyLayoutUpdate($page);

                $pageLayout = $page->getRootTemplate();
                if ($pageLayout != 'empty') {
                    $this->getLayout()->helper('page/layout')->applyTemplate($pageLayout);
                }
        }
		
    	/*
         * Set Meta Information If Set
         */
        $head = $this->getLayout()->getBlock('head');
        
	/*
         * Custom Work Start Dated: 14-02-2017
         */	
        $url = '';
        $endPoint = '';
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        // Remove '?p=' from canonical Task:2812
        $currentUrl = preg_replace('/\?.*/', '', $currentUrl);

        if (strpos($currentUrl, '?cat') !== false) {
            $currentUrl = substr($currentUrl, 0, strpos($currentUrl, '?cat'));
        }
        $baseCanonicalUrl = Mage::getBaseUrl() . $page->getIdentifier();
            $pos = strpos($currentUrl, $page->getIdentifier());
            $endPoint = substr($currentUrl, $pos+strlen($page->getIdentifier())); 
        if ($page->getIdentifier() == 'men-sports-collection' || $page->getIdentifier() == 'women-sports-collection') {
            if (strpos($endPoint, '--') !== false) {
                $endPoint = substr($endPoint, 0, strpos($endPoint, '--'));
            }
            $url = $baseCanonicalUrl . $endPoint . Mage::getStoreConfig('catalog/seo/category_url_suffix');
        } else {
            $url = $baseCanonicalUrl . Mage::getStoreConfig('catalog/seo/category_url_suffix'); 
        }
        
        $url = str_replace('//', '/', $url);
        /*
         * Custom Work End
         */
        
        $head->addLinkRel('canonical', $url);

        if ($page->getMetaTitle() != '') {
            $head->setTitle($this->trim($page->getMetaTitle()));
        }
        if ($page->getMetaKeywords() != '') { 
            $head->setKeywords($this->trim($page->getMetaKeywords()));
        }
        if ($page->getMetaDescription() != '') {
            $head->setDescription($this->trim($page->getMetaDescription()));
        }

        $toolbar = $this->getLayout()->getBlock('product_list_toolbar');

        if ($toolbar instanceof Amasty_Xlanding_Block_Catalog_Product_List_Toolbar) {
            $pager = $toolbar->getAmastyPager();
            $this->_handlePrevNextTags($pager);
        }

        /*
         * Set Custom Column Count
         */
        $list = $this->getLayout()->getBlock('product_list');

        if ($list) {
            $list->setColumnCount($page->getColumnsCount() > 0 ? $page->getColumnsCount() : $hlp->getColumnCount());
        }

        if ($topBlock = $page->getLayoutStaticTop()) {
            $this->getLayout()->getBlock('content')->insert($this->getLayout()
                ->createBlock('cms/block')
                ->setBlockId($topBlock));	
        }

        if ($page->getLayoutHeading() != '' ||
                $page->getLayoutFile() != '' ||
                $page->getLayoutDescription()
                ) { 

            $this->getLayout()->getBlock('content')->insert(
                $this->getLayout()->createBlock('amlanding/custom')
            );
        }

        if ($page->getLayoutFooter()) {
            $this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('amlanding/footer'));
        }

        if ($bottomBlock = $page->getLayoutStaticBottom()) {
            $this->getLayout()->getBlock('content')->append($this->getLayout()
                ->createBlock('cms/block')
                ->setBlockId($bottomBlock));	
        }

        $this->_moveNavigation($page);

        if ('true' === (string)Mage::getConfig()->getNode('modules/Amasty_Shopby/active')){
            Mage::getSingleton('amshopby/observer')->handleLayoutRender();
        }	

        $this->_initLayoutMessages('catalog/session');
        $this->_initLayoutMessages('checkout/session');
        $this->renderLayout();
    }
	
    /**
     * Enable layered navigation block if seo links is on
     * @return void|boolean
     */
    private function enableLayeredNavigation()
    {
    	//if (!Mage::helper('amlanding')->seoLinksActive()) {
    	//	return false;
    	//}
    	
    	$categoryId = (int) Mage::app()->getStore()->getRootCategoryId();
        if (!$categoryId) {
            $this->_forward('noRoute'); 
            return;
        }

        $category = Mage::getModel('catalog/category')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($categoryId);
            
        if (!Mage::registry('current_category'))
            Mage::register('current_category', $category);

        Mage::getSingleton('catalog/session')->setLastVisitedCategoryId($category->getId());  
          
        // need to prepare layer params
        try {
            Mage::dispatchEvent('catalog_controller_category_init_after', 
                array('category' => $category, 'controller_action' => $this));
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            return;
        }
    }
	
    private function trim($str)
    {
        $str = strip_tags($str);
        $str = str_replace('"', '', $str);
        return trim($str, " -");
    }

}
