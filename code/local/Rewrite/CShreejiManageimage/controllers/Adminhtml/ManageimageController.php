<?php
/*
** @author: Sooraj Malhi <sooraj.malhi@progos.org>
** @package: Rewrite_CShreejiManageimage  
** @description: Delete duplicate product images even if product doesn't exist in our system 
*/
require_once "Shreeji/Manageimage/controllers/Adminhtml/ManageimageController.php"; 

class Rewrite_CShreejiManageimage_Adminhtml_ManageimageController extends Shreeji_Manageimage_Adminhtml_ManageimageController
{

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
                    /*
                     * Custom Work Start Dated: 30-Jan-2017
                     * Delete product images even if product doesn't exist in our system
                     */
                    $filepath =  Mage::getBaseDir('media') .'/catalog/product' . $modeldata->getData('filename')  ;
                    if(file_exists($filepath)){
                        unlink($filepath); // for delete image
                    }
                    $model = Mage::getModel('manageimage/manageimage');
                    $model->setId($this->getRequest()->getParam('id'))
                    ->delete();
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully deleted'));
                    /*
                     * Custom Work End
                     */
                    
                    //Mage::getSingleton('adminhtml/session')->addError('Unable to find product in your system');
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
                    } else {
                        /*
                        * Custom Work Start Dated: 30-Jan-2017
                        * Delete product images even if product doesn't exist in our system
                        */
                        $removeFile=$modeldata->getData('filename');
                        $filepath =  Mage::getBaseDir('media') .'/catalog/product' . $modeldata->getData('filename')  ;
                        if(file_exists($filepath)){
                            unlink($filepath); // for delete image
                        }
                        $manageimage = Mage::getModel('manageimage/manageimage')->load($manageimageId);
                        $manageimage->delete();
                        /*
                        * Custom Work End
                        */
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
}