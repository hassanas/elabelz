<?php

class VES_PdfProCustomVariables_Adminhtml_VariablesController extends Mage_Adminhtml_Controller_Action
{

	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('pdfprocustomvariables/items')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Variables Manager'), Mage::helper('adminhtml')->__('Variable Manager'));
		
		return $this;
	}   
 
	public function indexAction() {
		$this->_initAction()
			->renderLayout();
	}

	public function editAction() {
		$id     = $this->getRequest()->getParam('id');
		$model  = Mage::getModel('pdfprocustomvariables/pdfprocustomvariables')->load($id);

		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('pdfprocustomvariables_data', $model);

			$this->loadLayout();
			$this->_setActiveMenu('pdfprocustomvariables/items');

			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Variable Manager'), Mage::helper('adminhtml')->__('Variable Manager'));
			$this->_addBreadcrumb(Mage::helper('adminhtml')->__('Variable News'), Mage::helper('adminhtml')->__('Variable News'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

			$this->_addContent($this->getLayout()->createBlock('pdfprocustomvariables/adminhtml_pdfprocustomvariables_edit'))
				->_addLeft($this->getLayout()->createBlock('pdfprocustomvariables/adminhtml_pdfprocustomvariables_edit_tabs'));

			$this->renderLayout();
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('pdfprocustomvariables')->__('Variable does not exist'));
			$this->_redirect('*/*/');
		}
	}
 
	public function newAction() {
		$this->_forward('edit');
	}
 
	public function saveAction() {
		if ($data = $this->getRequest()->getPost()) {
			try {
				$testModel = Mage::getModel('pdfprocustomvariables/pdfprocustomvariables')->load($data['name'],'name');
				$id = $this->getRequest()->getParam('id');
				if(($testModel->getId() && !$id) || ($id && ($testModel->getId() != $id))){
					throw new Mage_Core_Exception(Mage::helper('pdfprocustomvariables')->__('The variable name "%s" is already exist. Please use other variable name.',$data['name']));
				}
			
				$model = Mage::getModel('pdfprocustomvariables/pdfprocustomvariables');		
				$model->setData($data)
				->setId($id);
				
				$model->save();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('pdfprocustomvariables')->__('Variable was successfully saved'));
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
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('pdfprocustomvariables')->__('Unable to find Variable to save'));
        $this->_redirect('*/*/');
	}
 
	public function deleteAction() {
		if( $this->getRequest()->getParam('id') > 0 ) {
			try {
				$model = Mage::getModel('pdfprocustomvariables/pdfprocustomvariables');
				 
				$model->setId($this->getRequest()->getParam('id'))
					->delete();
					 
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Variable was successfully deleted'));
				$this->_redirect('*/*/');
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
			}
		}
		$this->_redirect('*/*/');
	}

    public function massDeleteAction() {
        $pdfprocustomvariablesIds = $this->getRequest()->getParam('pdfprocustomvariables');
        if(!is_array($pdfprocustomvariablesIds)) {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select Variable(s)'));
        } else {
            try {
                foreach ($pdfprocustomvariablesIds as $pdfprocustomvariablesId) {
                    $pdfprocustomvariables = Mage::getModel('pdfprocustomvariables/pdfprocustomvariables')->load($pdfprocustomvariablesId);
                    $pdfprocustomvariables->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($pdfprocustomvariablesIds)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
	
    public function massStatusAction()
    {
        $pdfprocustomvariablesIds = $this->getRequest()->getParam('pdfprocustomvariables');
        if(!is_array($pdfprocustomvariablesIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select Variable(s)'));
        } else {
            try {
                foreach ($pdfprocustomvariablesIds as $pdfprocustomvariablesId) {
                    $pdfprocustomvariables = Mage::getSingleton('pdfprocustomvariables/pdfprocustomvariables')
                        ->load($pdfprocustomvariablesId)
                        ->setStatus($this->getRequest()->getParam('status'))
                        ->setIsMassupdate(true)
                        ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', count($pdfprocustomvariablesIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
  
    public function exportCsvAction()
    {
        $fileName   = 'pdfprocustomvariables.csv';
        $content    = $this->getLayout()->createBlock('pdfprocustomvariables/adminhtml_pdfprocustomvariables_grid')
            ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName   = 'pdfprocustomvariables.xml';
        $content    = $this->getLayout()->createBlock('pdfprocustomvariables/adminhtml_pdfprocustomvariables_grid')
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

    protected function _isAllowed() {
        return true;
    }
    
}