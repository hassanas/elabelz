<?php

/**
 * Class Apptha_Marketplace_AjaxController
 */
class Apptha_Marketplace_AjaxController extends Mage_Core_Controller_Front_Action
{
    public function filterAction()
    {
        $isAjax = Mage::app()->getRequest()->isAjax();
        $name = $this->getRequest()->getPost('name');
        $type = $this->getRequest()->getPost('type');
        $page = $this->getRequest()->getPost('page');
        $this->loadLayout();
        $layout = $this->getLayout();
        /** @var $block Apptha_Marketplace_Block_Product_Manage */
        $block = $layout->getBlock('marketplace_productmanages');
        $pager = $layout->getBlock(Apptha_Marketplace_Block_Product_Manage::PAGER_BLOCK_NAME);
        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = $block->getCollection();
        if ($isAjax && $block) {
            if (!is_null($name) && $type === 'name') {
                $collection->addAttributeToFilter('name', array(
                    array('like' => '%' . trim($name) . '%'),
                ));
            }
            if (!is_null($name) && $type === 'sku') {
                $collection->addAttributeToFilter('sku', array(
                    array('like' => '%' . (string)trim($name) . '%'),
                ));
            }
            if (!is_null($page)) {
                $collection->setCurPage((int)$page);
            }

            $collection->addAttributeToSort('entity_id', 'DESC');

            $block->setCollection($collection);
            $pager->setCollection($collection);

            return $this->getResponse()->setBody($block->toHtml());
        }
    }
}