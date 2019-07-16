<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

class Infomodus_Upslabel_Adminhtml_Upslabel_ListsController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('upslabel/lists')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Items Manager'), Mage::helper('adminhtml')->__('Item Manager'));

        return $this;
    }

    protected function _isAllowed()
    {
        return true;
    }

    public function indexAction()
    {
        $this->_initAction()
            ->renderLayout();
    }

    public function saveAction()
    {
        $idIncrement = $this->getRequest()->getParam('order_id');
        $direction = $this->getRequest()->getParam('direction');
        if ($idIncrement && $idIncrement != 0 && $idIncrement != '') {
            $order = Mage::getModel('sales/order')->load($idIncrement, 'increment_id');
            $orderId = $order->getId();
            $this->_redirect('adminhtml/upslabel_upslabel/intermediate', array('order_id' => $orderId, 'type' => $direction));
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select or input order id'));
            $this->_redirect('*/*/edit');
        }
    }

    public function editAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('adminhtml/upslabel_lists');

        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item Manager'), Mage::helper('adminhtml')->__('Item Manager'));
        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Item News'), Mage::helper('adminhtml')->__('Item News'));

        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $this->_addContent($this->getLayout()->createBlock('upslabel/adminhtml_lists_edit'))
            ->_addLeft($this->getLayout()->createBlock('upslabel/adminhtml_lists_edit_tabs'));

        $this->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id > 0) {
            try {
                $collection = Mage::getModel('upslabel/upslabel')->load($id);
                $order_id = $collection->getOrderId();
                $order = Mage::getModel('sales/order')->load($order_id);
                
                if ($collection->getStatus() == 0) {
                    $AccessLicenseNumber = Mage::getStoreConfig('upslabel/credentials/accesslicensenumber');
                    $UserId = Mage::getStoreConfig('upslabel/credentials/userid');
                    $Password = Mage::getStoreConfig('upslabel/credentials/password');
                    $shipperNumber = Mage::getStoreConfig('upslabel/credentials/shippernumber');

                    $lbl = Mage::getModel('upslabel/ups');

                    $lbl->setCredentials($AccessLicenseNumber, $UserId, $Password, $shipperNumber);
                    $lbl->packagingReferenceNumberCode = Mage::getStoreConfig('upslabel/packaging/packagingreferencenumbercode');
                    $lbl->testing = Mage::getStoreConfig('upslabel/testmode/testing');

                    $lbl->deleteLabel($collection->getShipmentidentificationnumber());
                    $models = Mage::getModel('upslabel/upslabel')->getCollection()->addFieldToFilter('order_id', $order_id)->addFieldToFilter('shipmentidentificationnumber', $collection->getShipmentidentificationnumber());
                    foreach ($models AS $model) {
                        if(file_exists(Mage::getBaseDir('media') . '/upslabel/label/' . $model->getLabelname())) {
                            unlink(Mage::getBaseDir('media') . '/upslabel/label/' . $model->getLabelname());
                        }
                        if(file_exists(Mage::getBaseDir('media') . '/upslabel/label/' . $model->getTrackingnumber() . '.html')) {
                            unlink(Mage::getBaseDir('media') . '/upslabel/label/' . $model->getTrackingnumber() . '.html');
                        }
                        if(file_exists(Mage::getBaseDir('media') . '/upslabel/label/' . "HVR" . $model->getTrackingnumber() . ".html")) {
                            unlink(Mage::getBaseDir('media') . '/upslabel/label/' . "HVR" . $model->getTrackingnumber() . ".html");
                        }
                        $model->delete();
                    }
                } else {
                    $collection->delete();
                }

                /*$collection->delete();*/

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
        $upslabelIds = $this->getRequest()->getParam('upslabel');
        if (!is_array($upslabelIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                $lbl = Mage::getModel('upslabel/ups');
                foreach ($upslabelIds as $upslabelId) {
                    $upslabel = Mage::getModel('upslabel/upslabel')->load($upslabelId);
                    if ($upslabel && $upslabel->getOrderId() > 0) {
                        $order_id = $upslabel->getOrderId();
                        $order = Mage::getModel('sales/order')->load($order_id);
                        $storeId = NULL;
                        

                        $AccessLicenseNumber = Mage::getStoreConfig('upslabel/credentials/accesslicensenumber');
                        $UserId = Mage::getStoreConfig('upslabel/credentials/userid');
                        $Password = Mage::getStoreConfig('upslabel/credentials/password');
                        $shipperNumber = Mage::getStoreConfig('upslabel/credentials/shippernumber');

                        $lbl->setCredentials($AccessLicenseNumber, $UserId, $Password, $shipperNumber);

                        $lbl->deleteLabel($upslabel->getShipmentidentificationnumber());
                        $models = Mage::getModel('upslabel/upslabel')->getCollection()->addFieldToFilter('shipmentidentificationnumber', $upslabel->getShipmentidentificationnumber());
                        foreach ($models AS $model) {
                            if(file_exists(Mage::getBaseDir('media') . '/upslabel/label/' . $model->getLabelname())) {
                                unlink(Mage::getBaseDir('media') . '/upslabel/label/' . $model->getLabelname());
                            }
                            if(file_exists(Mage::getBaseDir('media') . '/upslabel/label/' . $model->getTrackingnumber() . '.html')) {
                                unlink(Mage::getBaseDir('media') . '/upslabel/label/' . $model->getTrackingnumber() . '.html');
                            }
                            if(file_exists(Mage::getBaseDir('media') . '/upslabel/label/' . "HVR" . $model->getTrackingnumber() . ".html")) {
                                unlink(Mage::getBaseDir('media') . '/upslabel/label/' . "HVR" . $model->getTrackingnumber() . ".html");
                            }
                            $model->delete();
                        }
                    }
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($upslabelIds)
                    )
                );
            } catch
            (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    public function exportCsvAction()
    {
        $fileName = 'ups_labels.csv';
        $content = $this->getLayout()->createBlock('upslabel/adminhtml_lists_grid')
            ->getCsv();

        $this->_sendUploadResponse($fileName, $content);
    }

    public function exportXmlAction()
    {
        $fileName = 'ups_labels.xml';
        $content = $this->getLayout()->createBlock('upslabel/adminhtml_lists_grid')
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
        return;
    }
}