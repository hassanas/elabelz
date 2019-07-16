<?php
require_once "Mage/Adminhtml/controllers/Catalog/ProductController.php"; 
class Progos_Catalog_Adminhtml_Catalog_ProductController extends Mage_Adminhtml_Catalog_ProductController
{
    public function massPartialIndexerAction()
    {   
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $partialIndex= $resource->getTableName('catalog_product_partialindex');
        $productIds = $this->getRequest()->getParam('product');
        $sortOrder = $this->getRequest()->getParam('partialindexer');
       
        if (!is_array($productIds)) {
            $this->_getSession()->addError($this->__('Please select product(s).'));
        } else {
            if (!empty($productIds)) {
                try {
                    foreach ($productIds as $productId) {
                        $insertData = array(
                            'product_id' => $productId,
                            'sort_order' => $sortOrder
                        );
                       $writeConnection->insertOnDuplicate($partialIndex, $insertData, array('product_id', 'sort_order'));
                    }
                    $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) have been moved to partial indexer.', count($productIds))
                    );
                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            }
        }
        $this->_redirect('*/*/index');
    }
}
