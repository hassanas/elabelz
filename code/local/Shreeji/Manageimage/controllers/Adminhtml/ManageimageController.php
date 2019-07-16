<?php

class Shreeji_Manageimage_Adminhtml_ManageimageController extends Mage_Adminhtml_Controller_Action
{

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/manageimageitems');
    }

    protected function _initAction() {
        $this->loadLayout()
        ->_setActiveMenu('catalog/products')
        ->_title($this->__('Manage Duplicate Images'))
        ->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

        return $this;
    }   

    public function indexAction() {
        $this->_initAction()
        ->renderLayout();
    }

    public function newAction() {
        $model = Mage::getModel('manageimage/manageimage')->FindDuplicateImage();        
        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('manageimage')->__('Duplicate images was successfully find.'));
        $this->_redirect('*/*/');
    }

    public function deleteAction() {
        if( $this->getRequest()->getParam('id') > 0 ) {
            try {
                $modeldata=Mage::getModel('manageimage/manageimage')->load($this->getRequest()->getParam('id')); 
                $productId=Mage::getModel("catalog/product")->getIdBySku($modeldata->getData('sku'));            
                if(!empty($productId)){
                    $removeFile=$modeldata->getData('filename');
                    $removeImage=$this->removeImage($productId,$removeFile);
                    if($removeImage==true){
                        $filepath =  Mage::getBaseDir('media') .'/catalog/product' . $modeldata->getData('filename')  ;
                        if(file_exists($filepath)){
                            unlink($filepath); // for delete image
                        }
                        $model = Mage::getModel('manageimage/manageimage');
                        $model->setId($this->getRequest()->getParam('id'))
                        ->delete();
                        Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully deleted'));
                        $this->_redirect('*/*/');
                    }else{
                        Mage::getSingleton('adminhtml/session')->addError('Unable to delete image');
                        $this->_redirect('*/*/');
                    }
                }
                else{
                    Mage::getSingleton('adminhtml/session')->addError('Unable to find product in your system');
                    $this->_redirect('*/*/');
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/');
            }
        }
        $this->_redirect('*/*/');
    }

    public function massDeleteAction() {
        $manageimageIds = $this->getRequest()->getParam('manageimage');
        if(!is_array($manageimageIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($manageimageIds as $manageimageId) {                    
                    $modeldata=Mage::getModel('manageimage/manageimage')->load($manageimageId);
                    $productId=Mage::getModel("catalog/product")->getIdBySku($modeldata->getData('sku'));            
                    if($productId){
                        $removeFile=$modeldata->getData('filename');
                        $removeImage=$this->removeImage($productId,$removeFile);
                        $filepath =  Mage::getBaseDir('media') .'/catalog/product' . $modeldata->getData('filename')  ;
                        if(file_exists($filepath)){
                            unlink($filepath); // for delete image
                        }
                        $manageimage = Mage::getModel('manageimage/manageimage')->load($manageimageId);
                        $manageimage->delete();
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__(
                'Total of %d record(s) were successfully deleted', count($manageimageIds)
                )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }


    public function exportCsvAction()
    {
        $fileName   = 'manageimage.csv';
        $content    = $this->getLayout()->createBlock('manageimage/adminhtml_manageimage_grid')
        ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'manageimage.xml';
        $content    = $this->getLayout()->createBlock('manageimage/adminhtml_manageimage_grid')
        ->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

    protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK','');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }

    public function findproductwithnoimagesAction(){
        $this->loadLayout()
        ->_setActiveMenu('catalog/products')
        ->_title($this->__('Product with No Base Image'))
        ->renderLayout();
    }

    protected function _getWriteConnection(){
        $resource = Mage::getSingleton('core/resource')->getConnection('core_write');
        return $resource;
    }

    public function removeImage($productId,$filename){
        if(!empty($productId) && !empty($filename)){
            $writeConnection=$this->_getWriteConnection();
            $mediaTable=Mage::getSingleton("core/resource")->getTableName('catalog_product_entity_media_gallery');
            $query="DELETE FROM $mediaTable WHERE entity_id=$productId and BINARY value='$filename'";
            try{
                $writeConnection->query($query);  
            }
            catch (Exception $e) {
                Mage::logException($e);
            }
            return true;
        }else{
            return null;
        }
    }
}