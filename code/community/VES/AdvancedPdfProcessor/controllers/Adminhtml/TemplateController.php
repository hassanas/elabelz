<?php

class VES_AdvancedPdfProcessor_Adminhtml_TemplateController extends Mage_Adminhtml_Controller_Action
{

	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('advancedpdfprocessor/items')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Template Manager'), Mage::helper('adminhtml')->__('Template Manager'));
		$vesHelper = Mage::helper('ves_core');
		return $this;
	}   
 
	public function indexAction() {
		$vesHelper = Mage::helper('ves_core');
		$this->_initAction()
			->renderLayout();
	}

	public function editAction() {
		$id     = $this->getRequest()->getParam('id');
		$model  = Mage::getModel('advancedpdfprocessor/template')->load($id);
		$vesHelper = Mage::helper('ves_core');
		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('template_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('advancedpdfprocessor/items');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Template Manager'), Mage::helper('adminhtml')->__('Template Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Template News'), Mage::helper('adminhtml')->__('Template News'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('advancedpdfprocessor/adminhtml_template_edit'))
				->_addLeft($this->getLayout()->createBlock('advancedpdfprocessor/adminhtml_template_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('advancedpdfprocessor')->__('Template does not exist'));
			$this->_redirect('*/*/');
		}
	}
 
	public function newAction() {
		$this->_forward('edit');
	}
 
	public function saveAction() {
		if ($data = $this->getRequest()->getPost()) {
			$model = Mage::getModel('advancedpdfprocessor/template');
			$vesCoreHelper = Mage::helper('ves_core');
			if(isset($_FILES['template']['name']) && $_FILES['template']['name'] != '') {
				try {	
					/* Starting upload */	
					$uploader = new Varien_File_Uploader('template');
					
					// Any extention would work
	           		$uploader->setAllowedExtensions(array('xml'));
					$uploader->setAllowRenameFiles(false);
					$uploader->setFilesDispersion(false);	
					// We set media as the upload dir
					$path = Mage::getBaseDir('media') . DS.'ves_pdfpro'.DS.'tmp'.DS;
					$uploader->save($path, $_FILES['template']['name'] );
					$xml = new Zend_Config_Xml($path . $uploader->getUploadedFileName());
					$xml_array = $xml->toArray();
					$sku = $xml_array['name'];
					$css_path = $xml_array['css_path'];
					$order_template 		= $xml_array['order_template'];
					$invoice_template 		= $xml_array['invoice_template'];
					$shipment_template 		= $xml_array['shipment_template'];
					$creditmemo_template 	= $xml_array['creditmemo_template'];
				} catch (Exception $e) {
		      		Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
	                Mage::getSingleton('adminhtml/session')->setFormData($data);
	                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
	                return;
		        }
	        
		        //this way the name is saved in DB
	  			$data['filename'] = $_FILES['template']['name'];
	  			$data['sku'] = $sku;
	  			$data['css_path'] = $css_path;
	  			$data['order_template'] = $order_template;
	  			$data['invoice_template'] = $invoice_template;
	  			$data['shipment_template'] = $shipment_template;
	  			$data['creditmemo_template'] = $creditmemo_template;
	  			
			} else {
				$skuData = $model->load($this->getRequest()->getParam('id'))->getSku();
				if($skuData) {$data['sku'] = $skuData; $data['css_path'] = $model->load($this->getRequest()->getParam('id'))->getCssPath();}
			}
	  					
			$model->setData($data)
				->setId($this->getRequest()->getParam('id'));
			
			try {
				$model->save();
				if(isset($_FILES['template']['name']) && $_FILES['template']['name'] != '') {
					unlink($path . $uploader->getUploadedFileName());	//remove xml file
				}
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('advancedpdfprocessor')->__('Template was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);

				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('id' => $model->getId()));
					return;
				}
				$this->_redirect('*/*/');
				return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('advancedpdfprocessor')->__('Unable to find template to save'));
        $this->_redirect('*/*/');
	}
 
	public function deleteAction() {
		if( $this->getRequest()->getParam('id') > 0 ) {
			try {
				$model = Mage::getModel('advancedpdfprocessor/template');
				 
				$model->setId($this->getRequest()->getParam('id'))
					->delete();
					 
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Template was successfully deleted'));
				$this->_redirect('*/*/');
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
			}
		}
		$this->_redirect('*/*/');
	}

    public function massDeleteAction() {
        $advancedpdfprocessorIds = $this->getRequest()->getParam('advancedpdfprocessor');
        if(!is_array($advancedpdfprocessorIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select template(s)'));
        } else {
            try {
                foreach ($advancedpdfprocessorIds as $advancedpdfprocessorId) {
                    $advancedpdfprocessor = Mage::getModel('advancedpdfprocessor/template')->load($advancedpdfprocessorId);
                    $advancedpdfprocessor->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($advancedpdfprocessorIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
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
    
    protected function _isAllowed() {
        return true;
    }

}