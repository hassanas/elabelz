<?php
/**
 * User: Hasasn Ali Shahzad
 * Modified Date: 03/17/2017
 * Time: 9:51 AM
 */ 
class Progos_Magidev_Model_Sort_Observer extends Magidev_Sort_Model_Observer {
    public function addTabs( $observer ){
        if( Mage::app()->getRequest()->getParam('store') ){
            Mage::getSingleton('core/session')->setMagiBackendStoreId(Mage::app()->getRequest()->getParam('store'));
        }
        try{
            $_block=$observer->getTabs()->getLayout()->createBlock(
                'magidev_sort/adminhtml_catalog_category_tab_sort',
                'magidev.products.sort'
            )->toHtml();
        } catch( Exception $e ){
            $_block='Error: '.$e->getMessage().' Try to set image placeholders (System > Configuration > Catalog > Catalog > Product Image Placeholders). <a target="_blank" href="http://www.magentocommerce.com/knowledge-base/entry/configuration-catalog-product-image-placeholders">Magento Knowledge Base</a>';
        }
        $observer->getTabs()->addTab('magidev_sort', array(
            'label'     => Mage::helper('catalog')->__('Merchandising'),
            'content'   => $_block,
        ));

        try{
            //will start work from here tomorrow
            $_outofstock_block = $observer->getTabs()->getLayout()->createBlock(
                'progos_magidev/adminhtml_catalog_category_tab_outofstockproduct',
                'category.outofstockproduct.grid'
            )->toHtml();
        }
        catch(Exception $e){

        }
        /** Remove tab because conflict with product tab **/
//        $observer->getTabs()->addTab('catalog_outofstock', array(
//            'label'     => Mage::helper('catalog')->__('Others/outOfStocks'),
//            'content'   => $_outofstock_block,
//        ));
    }
}