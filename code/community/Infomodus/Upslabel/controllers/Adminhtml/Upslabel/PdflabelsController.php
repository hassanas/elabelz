<?php

/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */

class Infomodus_Upslabel_Adminhtml_Upslabel_PdflabelsController extends Mage_Core_Controller_Front_Action
{

    protected function _isAllowed()
    {
        return true;
    }

    public function indexAction()
    {
        $ptype = $this->getRequest()->getParam('type');
        if ($ptype != 'lists') {
            $type = 'shipment';
            $order_ids = $this->getRequest()->getParam($ptype . '_ids');
            if ($ptype == 'creditmemo') {
                $ptype = 'shipment';
                $type = 'refund';
            }
            $resp = $this->create($order_ids, $type, $ptype);
        } else {
            $order_ids = $this->getRequest()->getParam('upslabel');
            $resp = $this->createFromLists($order_ids);
        }

        if (!$resp) {
            $this->_redirectReferer();
        }
    }

    public function linktopdfAction(){
        $path = Mage::getBaseDir('media') . DS . 'upslabel' . DS . "label" . DS;
        $collections = Mage::getModel('upslabel/upslabel');
        $labels = $collections->getCollection()->addFieldToFilter('type_print', 'link')->addFieldToFilter('status', 0);
        if(count($labels) > 0) {
            foreach ($labels AS $label) {
                $order = Mage::getModel('sales/order')->load($label->getOrderId());
                $testing = Mage::getStoreConfig('upslabel/testmode/testing', $order->getStoreId());
                $cie = 'wwwcie';
                if (0 == $testing) {
                    $cie = 'www';
                }
                $curl = Mage::helper('upslabel/help');
                $htmlUrlUPS = 'https://' . $cie . '.ups.com';
                $curl->testing = !$testing;
                $c = $curl->curlSend($label->getLabelname());

                if (!$curl->error && strlen($c) > 100) {
                    $imgName = preg_replace('/.*?FOLD\sHERE.*?<img\s*?src="(.+?)".*/is', '$1', $c);
                    if (strlen($imgName) != strlen($c)) {
                        $c = preg_replace('/<img\s*?src="/is', '<img src="' . $htmlUrlUPS, $c);
                        $htmlImage = $c;
                        file_put_contents($path . $label->getTrackingnumber() . ".html", $htmlImage);
                        $curl->testing = !$testing;
                        $c = $curl->curlSend("https://" . $cie . ".ups.com" . $imgName);
                        if (!$curl->error) {
                            $file = fopen($path . 'label' . $label->getTrackingnumber() . '.gif', 'w');
                            if($file !== FALSE) {
                                fwrite($file, $c);
                                fclose($file);
                                $label->setTypePrint("GIF");
                                $label->setLabelname('label' . $label->getTrackingnumber() . '.gif');
                                $label->save();
                            } else {
                                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('upslabel')->__('Error creating file'));
                            }
                            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('upslabel')->__('Convert to PDF label')." ".$label->getTrackingnumber());
                        }
                    } else {
                        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('upslabel')->__('Could not convert to PDF label')." ".$label->getTrackingnumber());
                    }
                } else {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('upslabel')->__('Could not convert to PDF label')." ".$label->getTrackingnumber());
                }
            }
        }
        $this->_redirectReferer();
    }

    public function onepdfAction()
    {
        $order_id = $this->getRequest()->getParam('order_id', NULL);
        $shipment_id = $this->getRequest()->getParam('shipment_id', NULL);
        $label_id = $this->getRequest()->getParam('label_id', NULL);
        $type = $this->getRequest()->getParam('type');
        $img_path = Mage::getBaseDir('media') . '/upslabel/label/';
        $pdf = new Zend_Pdf();
        /*$pdf->setMetadata('<?xml version="1.0" ?><Title>UPS shipping labels</Title>');*/
        $pdf->properties['Title'] = 'UPS shipping labels';
        $pdf->properties['Author'] = 'Infomodus';
        $pdf->properties['Producer'] = 'Zend PDF';
        $pdf->properties['Creator'] = 'Zend PDF';
        $pdf->properties['CreationDate'] = 'D:' . date('YmdHis', time());
        $pdf->properties['ModDate'] = 'D:' . date('YmdHis', time());
        $i = 0;
        $collections = Mage::getModel('upslabel/upslabel');
        $collsFirst = NULL;
        if($label_id === NULL) {
            $colls = $collections->getCollection()->addFieldToFilter('order_id', $order_id);
            if ($shipment_id !== NULL) {
                $colls->addFieldToFilter('shipment_id', $shipment_id);
            }
            $colls->addFieldToFilter('type', $type)->addFieldToFilter('status', 0);
            $collsFirst = $colls->getFirstItem();
        }
        else {
            $colls[0] = $collections->load($label_id);
            $collsFirst = $colls[0];
        }
        $storeId = null;
        

        if ($collsFirst->getOrderId() == $order_id || $order_id === NULL) {
            foreach ($colls AS $collection) {
                if (file_exists($img_path . $collection->getLabelname()) && filesize($img_path . $collection->getLabelname()) > 512) {
                    if ($collection->getTypePrint() == "GIF") {
                        $pdf->pages[] = $this->_setLabelToPage($img_path . $collection->getLabelname());
                        $collection->setRvaPrinted(1);
                        $collection->save();
                        $i++;
                    }
                }
            }
        }
        if ($i > 0) {
            $pdfData = $pdf->render();
            return $this->_prepareDownloadResponse('ups-labels'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').
                '.pdf', $pdfData, 'application/pdf');
        }
    }

    public function create($order_ids, $type, $ptype)
    {
        $img_path = Mage::getBaseDir('media') . '/upslabel/label/';
        $path_invoice = Mage::getBaseDir('media') . DS . 'upslabel' . DS . 'inter_pdf' . DS;
        $pdf = new Zend_Pdf();
        $pdf->properties['Title'] = 'UPS shipping labels';
        $pdf->properties['Author'] = 'Infomodus';
        $pdf->properties['Producer'] = 'Zend PDF';
        $pdf->properties['Creator'] = 'Zend PDF';
        $pdf->properties['CreationDate'] = 'D:' . date('YmdHis', time());
        $pdf->properties['ModDate'] = 'D:' . date('YmdHis', time());
        $i = 0;
        //$pdf->pages = array_reverse($pdf->pages);
        if (!is_array($order_ids)) {
            $order_ids = explode(',', $order_ids);
        }

        $arrZPL = array();
        foreach ($order_ids as $order_id) {
            $storeId = NULL;
            

            $collections = Mage::getModel('upslabel/upslabel');
            $colls = $collections->getCollection()->addFieldToFilter($ptype . '_id', $order_id)->addFieldToFilter('type', $type)->addFieldToFilter('status', 0)->addFieldToFilter('type_print', array('neq' => 'virtual'));
            if (Mage::getStoreConfig('upslabel/printing/bulk_printing_all') == 1) {
                $colls->addFieldToFilter('rva_printed', 0);
            }
            if ($colls) {
                foreach ($colls AS $k => $v) {
                    $coll = $v['upslabel_id'];
                    $collection = Mage::getModel('upslabel/upslabel')->load($coll);
                    if (($collection->getOrderId() == $order_id && $ptype == "order") || ($collection->getShipmentId() == $order_id && $ptype != "order")) {
                        if (file_exists($img_path . $collection->getLabelname()) && filesize($img_path . $collection->getLabelname()) > 1024) {
                            if ($collection->getTypePrint() == "GIF") {
                                $pdf->pages[] = $this->_setLabelToPage($img_path . $collection->getLabelname());
                                $i++;
                            } else if($collection->getTypePrint() != "link"){
                                $ip = trim(Mage::getStoreConfig('upslabel/printing/automatic_printing_ip'));
                                $port = trim(Mage::getStoreConfig('upslabel/printing/automatic_printing_port'));
                                if (strlen($ip) > 0 && strlen($port) > 0 && Mage::getStoreConfig('upslabel/printing/printer') != 'GIF') {
                                    $data = file_get_contents($img_path . $collection->getLabelname());
                                    Mage::helper('upslabel/help')->sendPrint($data);
                                } else {
                                    $arrZPL[] = array('shipidnumber' => $collection->getShipmentidentificationnumber(), 'localname' => $collection->getLabelname(), 'name' => $img_path . $collection->getLabelname(), 'invoice_name' => ($collection->getInternationalInvoice() == 1 ? $collection->getShipmentidentificationnumber() . ".pdf" : NULL));
                                }
                            } else {
                                $path = Mage::getBaseDir('media') . DS . 'upslabel' . DS . "label" . DS;
                                $path_xml = Mage::getBaseDir('media') . DS . 'upslabel' . DS . "test_xml" . DS;
                                $cie = 'wwwcie';
                                if (0 == Mage::getStoreConfig('upslabel/testmode/testing')) {
                                    $cie = 'onlinetools';
                                }
                                $curl = Mage::helper('upslabel/help');
                                $htmlUrlUPS = 'https://' . $cie . '.ups.com';

                                $c = $curl->curlSend($collection->getLabelname());

                                if (!$curl->error && strlen($c) > 100) {
                                    $imgName = preg_replace('/.*?FOLD\sHERE.*?<img\s*?src="(.+?)".*/is', '$1', $c);
                                    if (strlen($imgName) != strlen($c)) {
                                        $c = preg_replace('/<img\s*?src="/is', '<img src="' . $htmlUrlUPS, $c);
                                        file_put_contents($path . $collection->getTrackingnumber() . ".html", $c);
                                        file_put_contents($path_xml . "HTML_image.html", $c);
                                        $c = $curl->curlSend("https://" . $cie . ".ups.com" . $imgName);
                                        if (!$curl->error) {
                                            $file = fopen($path . 'label' . $collection->getTrackingnumber() . '.gif', 'w');
                                            fwrite($file, $c);
                                            fclose($file);
                                            $collection->setTypePrint('GIF');
                                            $collection->setLabelname('label' . $collection->getTrackingnumber() . '.gif');
                                            $collection->save();
                                            $pdf->pages[] = $this->_setLabelToPage($img_path . $collection->getLabelname());
                                        }
                                    }
                                }
                            }
                            $collection->setRvaPrinted(1);
                            $collection->save();
                        }
                    }
                }
                if(Mage::getStoreConfig('upslabel/paperless/print_with') == 1) {
                    $collsOne = $colls->getFirstItem();
                    if ($collsOne->getInternationalInvoice() == 1) {
                        if (file_exists($path_invoice . $collsOne->getShipmentidentificationnumber() . ".pdf")) {
                            $pdf2 = Zend_Pdf::load($path_invoice . $collsOne->getShipmentidentificationnumber() . ".pdf");
                            foreach ($pdf2->pages AS $k => $page) {
                                $template2 = clone $pdf2->pages[$k];
                                $page2 = new Zend_Pdf_Page($template2);
                                $pdf->pages[] = $page2;
                            }
                        };
                    }
                }
            }
        }
        //$pdf->save();
        if (count($arrZPL) > 0) {
            $zip = new ZipArchive();
            $zip_name = sys_get_temp_dir() . DS . 'labels' . time() . uniqid() . '.zip';
            if ($zip->open($zip_name, ZipArchive::CREATE) !== TRUE) {
            }
            foreach ($arrZPL AS $coll) {
                if (file_exists($coll['name'])) {

                    if (isset($coll['invoice_name']) && $coll['invoice_name'] !== NULL) {
                        $zip->addFile($coll['shipidnumber'] . '/' . $coll['name'], $coll['localname']);
                        $zip->addFile($coll['shipidnumber'] . '/invoice_' . $coll['invoice_name'], $path_invoice . $coll['invoice_name']);
                    } else {
                        $zip->addFile($coll['name'], $coll['localname']);
                    }
                }
            }
            if ($i > 0) {
                $pdfData = $pdf->render();
                $zip->addFromString('pdf_labels_only.pdf', $pdfData);
            }
            $zip->close();
            if (file_exists($zip_name)) {
                $pdfData = file_get_contents($zip_name);
                unlink($zip_name);
                return $this->_prepareDownloadResponse('ups_shipping_labels.zip', $pdfData, 'application/zip');
            }
        } else if ($i > 0) {
            $pdfData = $pdf->render();
            return $this->_prepareDownloadResponse('ups-labels'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').
                '.pdf', $pdfData, 'application/pdf');
        }
        return false;
    }

    public function createFromLists($order_ids)
    {
        $img_path = Mage::getBaseDir('media') . '/upslabel/label/';
        $pdf = new Zend_Pdf();
        $pdf->properties['Title'] = 'UPS shipping labels';
        $pdf->properties['Author'] = 'Infomodus';
        $pdf->properties['Producer'] = 'Zend PDF';
        $pdf->properties['Creator'] = 'Zend PDF';
        $pdf->properties['CreationDate'] = 'D:' . date('YmdHis', time());
        $pdf->properties['ModDate'] = 'D:' . date('YmdHis', time());
        $i = 0;
        if (!is_array($order_ids)) {
            $order_ids = explode(',', $order_ids);
        }
        $arrZPL = array();
        foreach ($order_ids as $order_id) {
            $storeId = NULL;
            

            $collection = Mage::getModel('upslabel/upslabel')->load($order_id);
            if ($collection && $collection->getStatus() == 0 && $collection->getTypePrint() != 'virtual') {
                if (file_exists($img_path . $collection->getLabelname()) && filesize($img_path . $collection->getLabelname()) > 1024) {
                    if ($collection->getTypePrint() == "GIF") {
                        $pdf->pages[] = $this->_setLabelToPage($img_path . $collection->getLabelname());
                        $i++;
                    } else if($collection->getTypePrint() != "link"){
                        $ip = trim(Mage::getStoreConfig('upslabel/printing/automatic_printing_ip'));
                        $port = trim(Mage::getStoreConfig('upslabel/printing/automatic_printing_port'));
                        if (strlen($ip) > 0 && strlen($port) > 0 && Mage::getStoreConfig('upslabel/printing/printer') != 'GIF') {
                            $data = file_get_contents($img_path . $collection->getLabelname());
                            Mage::helper('upslabel/help')->sendPrint($data);
                        } else {
                            $arrZPL[] = array('localname' => $collection->getLabelname(), 'name' => $img_path . $collection->getLabelname());
                        }
                    } else {
                        $path = Mage::getBaseDir('media') . DS . 'upslabel' . DS . "label" . DS;
                        $path_xml = Mage::getBaseDir('media') . DS . 'upslabel' . DS . "test_xml" . DS;
                        $cie = 'wwwcie';
                        if (0 == Mage::getStoreConfig('upslabel/testmode/testing')) {
                            $cie = 'onlinetools';
                        }
                        $curl = Mage::helper('upslabel/help');
                        $htmlUrlUPS = 'https://' . $cie . '.ups.com';

                        $c = $curl->curlSend($collection->getLabelname());

                        if (!$curl->error && strlen($c) > 100) {
                            $imgName = preg_replace('/.*?FOLD\sHERE.*?<img\s*?src="(.+?)".*/is', '$1', $c);
                            if (strlen($imgName) != strlen($c)) {
                                $c = preg_replace('/<img\s*?src="/is', '<img src="' . $htmlUrlUPS, $c);
                                file_put_contents($path . $collection->getTrackingnumber() . ".html", $c);
                                file_put_contents($path_xml . "HTML_image.html", $c);
                                $c = $curl->curlSend("https://" . $cie . ".ups.com" . $imgName);
                                if (!$curl->error) {
                                    $file = fopen($path . 'label' . $collection->getTrackingnumber() . '.gif', 'w');
                                    fwrite($file, $c);
                                    fclose($file);
                                    $collection->setTypePrint('GIF');
                                    $collection->setLabelname('label' . $collection->getTrackingnumber() . '.gif');
                                    $collection->save();
                                    $pdf->pages[] = $this->_setLabelToPage($img_path . $collection->getLabelname());
                                }
                            }
                        }
                    }
                    $collection->setRvaPrinted(1);
                    $collection->save();
                }
            }
        }
        //$pdf->save();
        if (count($arrZPL) > 0) {
            $zip = new ZipArchive();
            $zip_name = sys_get_temp_dir() . DS . 'labels' . time() . uniqid() . '.zip';
            if ($zip->open($zip_name, ZipArchive::CREATE) !== TRUE) {
            }
            foreach ($arrZPL AS $coll) {
                if (file_exists($coll['name'])) {
                    $zip->addFile($coll['name'], $coll['localname']);
                }
            }
            if ($i > 0 && count($pdf->pages) > 0) {
                $zip->addFromString('pdf_labels_only.pdf', $pdf->render());
            }
            $zip->close();
            if (file_exists($zip_name)) {
                $pdfData = file_get_contents($zip_name);
                unlink($zip_name);
                return $this->_prepareDownloadResponse('ups_shipping_labels.zip', $pdfData, 'application/zip');
            }
        } else if ($i > 0) {
            $pdfData = $pdf->render();
            return $this->_prepareDownloadResponse('ups-labels'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').
                '.pdf', $pdfData, 'application/pdf');
        }
        return false;
    }

    private function _setLabelToPage($label, $storeId = NULL)
    {
        $image = imagecreatefromstring(file_get_contents($label));

        if (!$image) {
            return false;
        }
        $xSize = imagesx($image);
        $ySize = imagesy($image);
        /*if (Mage::getStoreConfig('upslabel/printing/printer') == "GIF") {*/
        if (Mage::getStoreConfig('upslabel/printing/papersize') != "AC") {
            if (Mage::getStoreConfig('upslabel/printing/papersize') == "A4") {
                if ($xSize > 595) {
                    $ySize = $ySize * (595 / $xSize);
                    $xSize = 595;
                }
                $page = new Zend_Pdf_Page(Zend_Pdf_Page::SIZE_A4);
            } else {
                $page = new Zend_Pdf_Page($xSize, $ySize);
            }
        } else {
            $ySize = Mage::getStoreConfig('upslabel/printing/custom_width') * ($ySize / $xSize);
            $xSize = Mage::getStoreConfig('upslabel/printing/custom_width');
            $page = new Zend_Pdf_Page($xSize, $ySize);
        }

        imageinterlace($image, 0);
        $tmpFileName = sys_get_temp_dir() . DS . 'lbl' . rand(10000, 999999) . '.png';
        imagepng($image, $tmpFileName);
        $image = Zend_Pdf_Image::imageWithPath($tmpFileName);
        $page->drawImage($image, 0, 0, $xSize, $ySize);
        unlink($tmpFileName);
        /*}*/
        return ($page);
    }
}
