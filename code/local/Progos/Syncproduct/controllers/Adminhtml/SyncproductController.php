<?php

class Progos_Syncproduct_Adminhtml_SyncproductController extends Mage_Adminhtml_Controller_Action
{

    protected function _isAllowed()
    {
        return true;
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('syncproduct/items')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()
            ->renderLayout();
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('progos_syncproduct/syncproduct')->load($id);

        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }

            Mage::register('syncproduct_data', $model);

            $this->loadLayout();
            $this->_setActiveMenu('syncproduct/items');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

            $this->_addContent($this->getLayout()->createBlock('progos_syncproduct/adminhtml_syncproduct_edit'))
                ->_addLeft($this->getLayout()->createBlock('progos_syncproduct/adminhtml_syncproduct_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('progos_syncproduct')->__('Item does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {

            if (isset($data['sku'])) {
                $sku = rtrim($data['sku'],',');
                $sku = explode(',', $sku);
            }

            if (isset($_FILES['filename']['name']) && $_FILES['filename']['name'] != '') {

                $file = $_FILES['filename']['tmp_name'];
                $csv = new Varien_File_Csv();
                $csvdata = $csv->getData($file);
                //make one array from csv multidimensional array
                $sku = array_filter(call_user_func_array('array_merge', $csvdata));
            }


            $id = $this->getRequest()->getParam('id');
            foreach ($sku as $item) {
                $productsync = Mage::getModel('progos_syncproduct/syncproduct');
                $syncId = $productsync->loadBySku($item)->getId();
                //if record exists, update record
                if ($syncId !== null) {
                    $id = $syncId;
                }
                $productsync->setSku($item)
                    ->setStatus($data['status'])
                    ->setId($id);

                if ($productsync->getCreatedTime() == NULL || $productsync->getUpdateTime() == NULL) {
                    $productsync->setCreatedTime(now())
                        ->setUpdateTime(now());
                } else {
                    $productsync->setUpdateTime(now());
                }

                $productsync->save();

            }
        }

        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel('progos_syncproduct/syncproduct');

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

    public function massDeleteAction()
    {
        $syncproductIds = $this->getRequest()->getParam('syncproduct');
        if (!is_array($syncproductIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($syncproductIds as $syncproductId) {
                    $syncproduct = Mage::getModel('progos_syncproduct/syncproduct')->load($syncproductId);
                    $syncproduct->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($syncproductIds)
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
        $syncproductIds = $this->getRequest()->getParam('syncproduct');
        if (!is_array($syncproductIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
        } else {
            try {
                foreach ($syncproductIds as $syncproductId) {
                    $syncproduct = Mage::getSingleton('progos_syncproduct/syncproduct')
                        ->load($syncproductId)
                        ->setStatus($this->getRequest()->getParam('status'))
                        ->setIsMassupdate(true)
                        ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', count($syncproductIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function exportCsvAction()
    {
        $fileName = 'syncproduct.csv';
        $content = $this->getLayout()->createBlock('progos_syncproduct/adminhtml_syncproduct_grid')
            ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName = 'syncproduct.xml';
        $content = $this->getLayout()->createBlock('progos_syncproduct/adminhtml_syncproduct_grid')
            ->getXml();

        $this->_sendUploadResponse($fileName, $content);
    }

    protected function _sendUploadResponse($fileName, $content, $contentType = 'application/octet-stream')
    {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK', '');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }
}
