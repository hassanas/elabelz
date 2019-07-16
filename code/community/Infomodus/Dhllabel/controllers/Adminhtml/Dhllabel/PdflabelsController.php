<?php

/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */

class Infomodus_Dhllabel_Adminhtml_Dhllabel_PdflabelsController extends Mage_Core_Controller_Front_Action
{

    public function __construct($op1 = null, $op2 = null, $op3 = array())
    {
        if ($op1 != null) {
            return parent::__construct($op1, $op2, $op3);
        } else {
            return $this;
        }
    }

    protected function _isAllowed()
    {
        return true;
    }

    public function indexAction()
    {
        $ptype = $this->getRequest()->getParam('type');
        $type = 'shipment';
        $orderIds = $this->getRequest()->getParam($ptype . '_ids');
        if ($ptype == 'creditmemo') {
            $ptype = 'shipment';
            $type = 'refund';
        }

        if ($ptype == 'lists') {
            $ptype = null;
            $orderIds = $this->getRequest()->getParam('dhllabel');
        }

        $orderIds = is_array($orderIds)?$orderIds:explode(",", $orderIds);
        $this->createPdf($orderIds, $type, $ptype);
    }

    public function createPdf($orderIds, $type, $ptype, $isPrint = false){
        $resp = self::bulk($orderIds, $type, $ptype);

        $path = Mage::getBaseDir('media') . '/dhllabel/label/';

        if (!empty($resp)) {
            $pdf2show = new Zend_Pdf();
            foreach ($resp AS $link) {
                if ($link!="" && file_exists($path . $link)) {
                    $pdf2 = Zend_Pdf::load($path . $link);
                    foreach ($pdf2->pages AS $k => $page) {
                        $template2 = clone $pdf2->pages[$k];
                        $page2 = new Zend_Pdf_Page($template2);
                        $pdf2show->pages[] = $page2;
                    }
                }

                $invoiceName = str_replace('label_', 'invoice_', $link);
                if($invoiceName!="" && file_exists($path . $invoiceName)) {
                    $pdf2 = Zend_Pdf::load($path . $invoiceName);
                    foreach ($pdf2->pages AS $k => $page) {
                        $template2 = clone $pdf2->pages[$k];
                        $page2 = new Zend_Pdf_Page($template2);
                        $pdf2show->pages[] = $page2;
                    }
                }
            }

            $pdfData = $pdf2show->render();

            if($isPrint === true) {
                return $pdfData;
            }

            return $this->_prepareDownloadResponse('dhl-labels-'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').
                '.pdf', $pdfData, 'application/pdf');
        } else {
            if($isPrint === true) {
                return false;
            }
            Mage::getSingleton('adminhtml/session')->addError($this->__("No element selected for display"));
            $this->_redirectReferer();
        }
    }

    static public function bulk($orderIds, $type, $ptype)
    {
        $items = array();
        foreach ($orderIds AS $orderId) {
            if ($ptype == NULL) {
                $collection = Mage::getModel('dhllabel/dhllabel')->load($orderId);
                if($collection->getTypePrint() == 'pdf') {
                    $items[] = $collection->getLabelname();
                }
            } else {
                $collections = Mage::getModel('dhllabel/dhllabel');
                $colls = $collections->getCollection()
                    ->addFieldToFilter($ptype . '_id', $orderId)
                    ->addFieldToFilter('type', $type)
                    ->addFieldToFilter('status', 0)
                    ->addFieldToFilter('type_print', 'pdf');
                foreach ($colls AS $v) {
                    $items[] = $v->getLabelname();
                }
            }
        }

        return $items;
    }
}
