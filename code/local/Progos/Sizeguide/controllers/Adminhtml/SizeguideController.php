<?php
class Progos_Sizeguide_Adminhtml_SizeguideController extends Mage_Adminhtml_Controller_Action
{
    
    /**
     * Initialize action
     *
     * Here, we set the breadcrumbs and the active menu
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _initAction()
    {
            // Make the active menu match the menu config nodes (without 'children' inbetween)
            $this->loadLayout()
            ->_setActiveMenu('progossizeguide/sizeguide')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Sizeguide Manager'), Mage::helper('adminhtml')->__('Sizeguide Manager'));
        
        return $this;
    }
    
     public function indexAction()
    {   
        // Let's call our initAction method which will set some basic params for each action
        $this->_title($this->__('Sizeguide'))
            ->_title($this->__('Manage Sizeguide'));
            
        $this->_initAction()
            ->renderLayout();
    }  
    
    public function newAction()
    {  //   echo 'New';
        // We just forward the new action to a blank edit form
        $this->_forward('edit');
    }  
    
    public function editAction()
    {  
        $this->_initAction();
        // Get id if available
        $id  = $this->getRequest()->getParam('id');
        $model = Mage::getModel('sizeguide/sizeguide')->load($id);
        if($model->getId() || $id == 0 ){
            
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if(!empty($data)) {
                $model->setData($data);
            }
            Mage::register('sizeguide_data', $model);
            $this->_title($this->__('Sizeguide'))
                ->_title($this->__('Manage Sizeguide'));
            if ($model->getId()){
                $this->_title($model->getTitle());
            }else{
                $this->_title($this->__('New Sizeguide'));
            }
            
            $this->loadLayout();
            $this->_setActiveMenu('progossizeguide/sizeguide');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Sizeguide Manager'), Mage::helper('adminhtml')->__('Sizeguide Manager'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Sizeguide News'), Mage::helper('adminhtml')->__('Sizeguide News'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            //$this->_addContent($this->getLayout()->createBlock('contactform/adminhtml_contactform_edit'))
            $this->_addLeft($this->getLayout()->createBlock('sizeguide/adminhtml_sizeguide_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('sizeguide')->__('This sizeguide no longer exists.'));
            $this->_redirect('*/*/');
        }
    }
     
    public function saveAction()
    {
        if ($postData = $this->getRequest()->getPost()) {
            $type = 'sizeguide_file';
            $path = Mage::getBaseDir() . DS . 'sizeguide' . DS;
            if( $_FILES[$type]['name'] != '') {

                try {
                    $uploader = new Varien_File_Uploader('sizeguide_file');
                    $uploader->setAllowedExtensions(array('csv'));
                    $uploader->setAllowRenameFiles(true);

                    $uploader->save($path, $_FILES[$type]['name'] );
                    $filename = $uploader->getUploadedFileName();

                } catch (Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                    $this->_redirectReferer();
                }
            }

            $model = Mage::getModel('sizeguide/sizeguide');
            $model->setData($postData)->setId($this->getRequest()->getParam('id'));
 
            try {
                $brand_ids     = implode(',',$this->getRequest()->getPost('brand_ids'));
                if(in_array('0',$this->getRequest()->getPost('store_id')))
                    $stores = '0';
                else
                    $stores     = implode(',',$this->getRequest()->getPost('store_id'));

                $categoryIds     = implode('|',$this->getRequest()->getPost('categories'));
                $model->setCategories($categoryIds);
                $model->setBrandIds($brand_ids);
                $model->setStoreId($stores);
                if( $_FILES[$type]['name'] != '') 
                    $model->setSizeguideFile($filename); 
                
                
                /* Parse CSV File and convert Array into Serialized Array */
                if( $_FILES[$type]['name'] != '') {
                    $file =  Mage::getBaseDir ().'/sizeguide/'.$filename;
                    $csvObject = new Varien_File_Csv();
                    $csvDatas = $csvObject->getData($file);
                    $code = false;
                    $tabArray = 0;
                    $csvArray = array();
                    $row = 0;
                    foreach( $csvDatas as $csvData ){
                        if( $code == true ){
                            $csvArray['codes'][]  = $csvData;
                            continue;
                        }
                        $tabStart = false;
                        $headStart = false;
                        $subheadStart = false;
                        foreach( $csvData as $value ){
                            if( strpos( $value , 'Tab:') !== false && $code !=true ){
                                $tabArray++;
                                $csvArray['tab'][$tabArray] = $value;
                                $tabStart = true;
                            }else if( ( $tabStart == true || $headStart ==true || $subheadStart == true  ) && empty($value) ){
                                continue;
                            }else{
                                if( strpos( $value , 'head:') !== false && $code !=true  ){
                                    $csvArray['head'][$tabArray][] = $value;
                                    $headStart = true;
                                }else if( strpos( $value , 'headsub:') !== false && $code !=true  ){
                                    $csvArray['headsub'][$tabArray][] = $value;
                                    $subheadStart = true;
                                }else if( $value == 'Code'  ){
                                    $code = true;
                                    break;
                                }else{
                                    $tabStart = false;
                                    $headStart = false;
                                    $subheadStart = false;
                                    $csvArray['data'][$tabArray][$row][] = $value;
                                }

                            }
                        }
                        $row++;
                    }
                    $model->setDetails( serialize( $csvArray ) );
                }
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The Sizeguide has been saved.'));
                
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
 
                return;
            }  
            catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('An error occurred while saving this Sizeguide.'));
            }
 
            Mage::getSingleton('adminhtml/session')->setFormData($postData);
            $this->_redirectReferer();
        }
    }
    
    public function deleteAction() {
        if( $this->getRequest()->getParam('id') > 0 ) {
            try {
                $model = Mage::getModel('sizeguide/sizeguide');
                 
                $model->setId($this->getRequest()->getParam('id'))
                    ->delete();
                     
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }
    
     public function massDeleteAction() {
        $sizeguideIds = $this->getRequest()->getParam('sizeguide');
        if(!is_array($sizeguideIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($sizeguideIds as $sizeguideId) {
                    $sizeguide = Mage::getModel('sizeguide/sizeguide')->load($sizeguideId);
                    $sizeguide->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($sizeguideIds)
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
        $fileName   = 'sizeguide.csv';
        $content    = $this->getLayout()->createBlock('sizeguide/adminhtml_sizeguide_grid')
            ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'sizeguide.xml';
        $content    = $this->getLayout()->createBlock('sizeguide/adminhtml_sizeguide_grid')
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
     
}
