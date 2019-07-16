<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at http://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   Advanced SEO Suite
 * @version   1.3.10
 * @build     1323
 * @copyright Copyright (C) 2016 Mirasvit (http://mirasvit.com/)
 */


class Mirasvit_Seo_Adminhtml_Seo_RedirectimportController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('seo');
    }

    protected function _initAction ()
    {
        $this->loadLayout()->_setActiveMenu('seo');

        return $this;
    }

    public function indexAction ()
    {
        $this->_title($this->__('Import Urls for Redirect'));
        $this->_initAction();
        $this->_addContent($this->getLayout()
            ->createBlock('seo/adminhtml_redirectimport_edit'));
        $this->renderLayout();
    }


   public function saveAction()
   {
        $data = $this->getRequest()->getParams();

        $uploader = new Mage_Core_Model_File_Uploader('file');
        $uploader->setAllowedExtensions(array('csv'));
        $uploader->setAllowRenameFiles(true);
        $path = Mage::getBaseDir('var').DS.'import';
        if (!file_exists($path)) {
            mkdir($path, 0777);
        }

        try {
            $result = $uploader->save($path);
            $fullPath = $result['path'].DS.$result['file'];

            $csv = new Varien_File_Csv();
            $data = $csv->getData($fullPath);

            $items = array();
            if (count($data) > 1) {
                for ($i = 1; $i < count($data); $i++ ) {
                    $item = array();
                    for ($j = 0; $j < count($data[0]); $j++) {
                        if (isset($data[$i][$j]) && trim($data[$i][$j]) != '') {
                            $item[strtolower($data[0][$j])] = $data[$i][$j];
                        }
                    }
                    $items[] = $item;
                }
            }

            $resource = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');
            $table = $resource->getTableName('seo/redirect');
            $table2 = $resource->getTableName('seo/redirect_store');
            $i = 0;
            foreach ($items as $item) {
                // pr($item);
                // print_r($item); die;
                if (!isset($item['url_from']) || !isset($item['url_to'])) {
                    continue;
                }
                $item = new Varien_Object($item);
                $query = "REPLACE {$table} SET
                    url_from = '".addslashes($item->getUrlFrom())."',
                    url_to = '".addslashes($item->getUrlTo())."',
                    is_redirect_only_error_page = '".(int)($item->getIsRedirectOnlyErrorPage())."',
                    redirect_type = '".(int)($item->getRedirectType())."',
                    comments = '".addslashes($item->getComments())."',
                    is_active = '".(int)($item->getIsActive())."';
                    REPLACE {$table2} SET
                        store_id = '".(int)($item->getStoreId())."',
                        redirect_id = LAST_INSERT_ID();
                     ";

                $writeConnection->query($query);
                $i ++;
            }

            Mage::getSingleton('adminhtml/session')->addSuccess(''.$i.' records were inserted or updated');
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        $this->_redirect('*/*/');
    }
}