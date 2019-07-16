<?php

/**
 * @auther gul.muhammad@progos.org
 */
class Progos_Partialindex_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{
    /**
     * 
     * @return \Progos_Partialindex_Adminhtml_IndexController
     */
    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('system/partial_index_list');
        return $this;
    }
    /**
     * 
     */
    public function indexAction()
    {
        $this->_initAction();
        $this->renderLayout();
    }
        
    
    /**
     * 
     */
    public function editAction()
    {
        $productId = $this->getRequest()->getParam('id');
        $product = Mage::getModel('partialindex/product_index')->load($productId);
        if ($product->getProductId() || $productId == 0)
        {
            Mage::register('partialindex_data', $product);
            $this->loadLayout();
            $this->_setActiveMenu('system/partial_index_list');
            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()
                    ->createBlock('partialindex/adminhtml_partialindex_edit'))
                    ->_addLeft($this->getLayout()
                    ->createBlock('partialindex/adminhtml_partialindex_edit_tabs')
            );
            $this->renderLayout();
        }
        else
        {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('partialindex')->__('Product does not exist'));
            $this->_redirect('*/*/');
        }
    }
    /**
     * 
     */
    public function newAction()
    {
        $this->_forward('edit');
    }
    /**
     * 
     * @return type
     */
    public function saveAction()
    {
        if($this->getRequest()->getPost()) {
           try {
                $postData = $this->getRequest()->getPost();
                $product = Mage::getModel('partialindex/product_index');
		$productId = $postData['product_id'];
                $count = Mage::getModel('catalog/product')->getCollection()->addFieldToFilter('entity_id',array('eq' => $productId))->count();
		if($productId > 0 && $count == 1) {
                    $catalogProductPartialindex = Mage::getSingleton('core/resource')->getTableName('catalog_product_partialindex');
                    $write = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $sql = "INSERT INTO {$catalogProductPartialindex} (product_id)
                            SELECT * FROM (SELECT '{$productId}') AS tmp
                            WHERE NOT EXISTS (
                                    SELECT product_id FROM {$catalogProductPartialindex} WHERE product_id = '{$productId}'
                            ) LIMIT 1;";
                    Mage::log($sql,null,'requete.log',true);
                    $write->query($sql);
                } else {
                    Mage::getSingleton('adminhtml/session')
                        ->addError(Mage::helper('partialindex')->__('This product ID does not exists'));
                    Mage::getSingleton('adminhtml/session')->setPartialindexData($this->getRequest()->getPost());
                    $this->_redirect('*/*/edit',array('id' => $this->getRequest()->getParam('id')));
                    return;
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('partialindex')->__('Product partial index successfully saved'));
                Mage::getSingleton('adminhtml/session')->setPartialindexData(false);
                $this->_redirect('*/*/');
		return;
				
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setPartialindexData($this->getRequest()->getPost());
                $this->_redirect('*/*/edit',array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }
    /**
     * 
     */
    public function deleteAction()
    {
        if($this->getRequest()->getParam('id') > 0) {
            try {
                $product = Mage::getModel('partialindex/product_index');
                $product->setId($this->getRequest()->getParam('id'))->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('partialindex')->__('Product partial index successfully deleted'));
                $this->_redirect('*/*/');
            }
            catch (Exception $e)
            {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }
}
