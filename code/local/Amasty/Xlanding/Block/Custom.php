<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Xlanding
 */

class Amasty_Xlanding_Block_Custom extends Mage_Core_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate("amasty/amlanding/custom.phtml");
    }
    
    protected function _getPage(){
        return Mage::registry('amlanding_page');
    }
    
    protected function getHeading(){
        return $this->_getPage()->getLayoutHeading();
    }
    
    protected function getDescription(){
        return $this->_getPage()->getLayoutDescription();
    }
    
    protected function getFile(){
        return $this->_getPage()->getLayoutFileUrl();
    }
    
    protected function getFileName(){
        return $this->_getPage()->getLayoutFileName();
    }
    
    protected function getFileAlt(){
        return $this->_getPage()->getLayoutFileAlt();
    }

    public function getBreadCrumb(){
        if ($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs')) {
            $breadcrumbsBlock->addCrumb('home', array(
                'label'=>Mage::helper('catalog')->__('Home'),
                'title'=>Mage::helper('catalog')->__('Go to Home Page'),
                'link'=>Mage::getBaseUrl()
            ));

        // code for getting current category path
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        
        // getting url path for whats new
        if (strpos($currentUrl,'what-is-new') !== false) {
        $breadcrumbsBlock->addCrumb('WhatsNew', array('label' => 'Whats new', 'title' => 'Whats new' , 'link' => Mage::getBaseUrl()."what-is-new/"));
        $str = 'what-is-new?cat=';
        }
        
         // getting url path for Sale
        elseif (strpos($currentUrl,'sale') !== false) {
        $breadcrumbsBlock->addCrumb('Sale', array('label' => 'Sale', 'title' => 'Sale' , 'link' => Mage::getBaseUrl()."sale/"));
        $str = 'sale/?cat=';
        }
       
         // getting url path for Sports
        elseif (strpos($currentUrl,'sports') !== false) {
        $breadcrumbsBlock->addCrumb('SportsShop', array('label' => 'Sports Shop', 'title' => 'Sports Shop' , 'link' => Mage::getBaseUrl()."sports-shop/"));
        
         // getting url path for women sale collection
        if(strpos($currentUrl,'women-sports-collection') !== false) {
            $str='women-sports-collection?cat=';
        }
        
         // getting url path for men sale collection
        elseif(strpos($currentUrl,'men-sports-collection') !== false) {
            $str = 'men-sports-collection?cat=';
        }

        }

        if(strpos($currentUrl,'what-is-new') !== false || strpos($currentUrl,'sale') !== false || strpos($currentUrl,'sports') !== false ){
        
        if($this->getRequest()->getParam('cat')){
        $current_category = $this->getRequest()->getParam('cat');
        $_categories = Mage::getModel('catalog/category')->load($current_category);
        $pathInStore = $_categories->getPath();
        $pathIds = explode('/', $pathInStore);
        $categories = $_categories->getParentCategories();
        
        foreach ($pathIds as $categoryId) {
                    
                    if (isset($categories[$categoryId]) && $categories[$categoryId]->getName()) {
                        $path['category'.$categoryId] = array(
                            'label' => $categories[$categoryId]->getName(),
                            'link' => Mage::getBaseUrl().$str.$categoryId
                        );
                    }
                }
            
         $this->_categoryPath = $path;
    
         foreach ($this->_categoryPath as $name => $breadcrumb) {

                $breadcrumbsBlock->addCrumb($name, $breadcrumb);
                $title[] = $breadcrumb['label'];
            }

            if ($headBlock = $this->getLayout()->getBlock('head')) {
                $headBlock->setTitle(join($this->getTitleSeparator(), array_reverse($title)));
            }
        }
        elseif(!$this->getRequest()->getParam('cat') && (strpos($currentUrl,'women-sports-collection') !== false || strpos($currentUrl,'men-sports-collection') !== false)){
         
         if(strpos($currentUrl,'women-sports-collection') !== false){
            $breadcrumbsBlock->addCrumb('women-sports-collection', array('label' => 'Women Sports Collection', 'title' => 'Women Sports Collection'));
         }
         elseif(strpos($currentUrl,'men-sports-collection') !== false){
            $breadcrumbsBlock->addCrumb('men-sports-collection', array('label' => 'Men Sports Collection', 'title' => 'Men Sports Collection'));
         }
        
        }
     
    }
    }
        return parent::getBreadCrumb();

    }

    
}
