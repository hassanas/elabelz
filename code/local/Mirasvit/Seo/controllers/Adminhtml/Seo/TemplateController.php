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


class Mirasvit_Seo_Adminhtml_Seo_TemplateController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('seo');
    }

    protected function _initAction ()
    {
        $this->loadLayout()->_setActiveMenu('seo');
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        return $this;
    }

    public function indexAction ()
    {
        $this->_title($this->__('Template Manager'));
        $this->_initAction();
        $this->_addContent($this->getLayout()
            ->createBlock('seo/adminhtml_template'));
        $this->renderLayout();
    }

    public function addAction ()
    {
        $this->_title($this->__('New Template'));

        $this->_initModel();

        $this->_initAction();
        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Template  Manager'),
                Mage::helper('adminhtml')->__('Template Manager'), $this->getUrl('*/*/'));
        $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Add Template '), Mage::helper('adminhtml')->__('Add Template'));
        $this->_addContent($this->getLayout()->createBlock('seo/adminhtml_template_edit'))
            ->_addLeft($this->getLayout()->createBlock('seo/adminhtml_template_edit_tabs'));

        $this->renderLayout();
    }

    public function editAction ()
    {
        $model = $this->_initModel();

        if ($model->getId()) {
            $this->_initAction();
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Template Manager'),
                    Mage::helper('adminhtml')->__('Template Manager'), $this->getUrl('*/*/'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Edit Template '),
                    Mage::helper('adminhtml')->__('Edit Template '));
            $this->_addContent($this->getLayout()->createBlock('seo/adminhtml_template_edit'))
                ->_addLeft($this->getLayout()->createBlock('seo/adminhtml_template_edit_tabs'));
            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('The item does not exist.'));
            $this->_redirect('*/*/');
        }
    }

    public function saveAction ()
    {
        if ($data = $this->getRequest()->getPost()) {
            $data['sort_order'] = (isset($data['sort_order']) && trim($data['sort_order']) != "") ? (int) trim($data['sort_order']) : 10;
            $data['store_ids'] = isset($data['store_ids']) ? array_unique($data['store_ids']) : array(); // to avoid frontend "id already exists" exception for some stores;
            $model = $this->_initModel();
            $model->setData($data);

            try {
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Item was successfully saved'));
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
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Unable to find item to save'));
        $this->_redirect('*/*/');
    }

    public function deleteAction ()
    {
        if (($ruleId = $this->getRequest()->getParam('id')) && $ruleId > 0) {
            try {
                $model = Mage::getModel('seo/template');
                $model->setId($ruleId)->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('adminhtml')->__('Item was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()
                    ->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam('template_id');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($ids as $id) {
                    $model = Mage::getModel('seo/template')
                        ->setIsMassDelete(true)
                        ->load($id);
                    $model->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($ids)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massEnableAction()
    {
        $ids = $this->getRequest()->getParam('template_id');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            $isDisabled = array();
            $isEnabled  = array();
            try {
                foreach ($ids as $id) {
                    $model = Mage::getModel('seo/template')
                        ->load($id)
                        ->setIsActive(true);
                    if ($model->getName()) {
                        $model->save();
                        $isEnabled[] = $id;
                    } else {
                        $isDisabled[] = $id;
                    }
                }
                if (!$isDisabled) {
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('adminhtml')->__(
                            'Total of %d record(s) were successfully enabled', count($ids)
                        )
                    );
                } else {
                    if ($isEnabled) {
                        Mage::getSingleton('adminhtml/session')->addSuccess(
                            Mage::helper('adminhtml')->__(
                                'Total of %d record(s) were successfully enabled', count($isEnabled)
                            )
                        );
                    }
                    if ($isDisabled) {
                        Mage::getSingleton('adminhtml/session')->addError(
                            Mage::helper('adminhtml')->__(
                                'Total of %d record(s) were not enabled. Please fill all required fields.', count($isDisabled)
                            )
                        );
                    }
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massDisableAction()
    {
        $ids = $this->getRequest()->getParam('template_id');
        if (!is_array($ids)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($ids as $id) {
                    $model = Mage::getModel('seo/template')
                        ->load($id)
                        ->setIsActive(false);
                    $model->save();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully enabled', count($ids)
                    )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    protected function _initModel()
    {
        $model = Mage::getModel('seo/template');

        if ($this->getRequest()->getParam('id')) {
            $model->load($this->getRequest()->getParam('id'));
        }

        Mage::register('current_template_model', $model);
        return $model;
    }

    public function newConditionHtmlAction()
    {
        $id      = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type    = $typeArr[0];

        $model = Mage::getModel($type)
            ->setId($id)
            ->setType($type)
            ->setRule(Mage::getModel('seo/template'))
            ->setPrefix('conditions');

        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }

}