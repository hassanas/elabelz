<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Audit
 */


class Amasty_Audit_Model_Visitobserver
{
    //listen controller_action_layout_render_before
    public function onPageLoad($observer)
    {
        $blockHead = Mage::app()->getLayout()->getBlock('head');
        if (!$blockHead) {
            return;
        }

        $sessionId = Mage::getSingleton('admin/session')->getSessionId();
        $visitEntityData = Mage::getModel('amaudit/visit')->getVisitEntity($sessionId);

        /*
         * check active admin and admin in table
         * if session active admin not equally session in table
         * redirect to login menu
         * */
        $this->_autoLogout($visitEntityData, $sessionId);
        
        /*
         * check cookie
         * if exist 
         * show notice message
         * delete cookie
         * */
        $this->_showMessage();

        $this->_saveDetailDataVisit($blockHead, $sessionId, $visitEntityData);
    }

    /**
     * @param Amasty_Audit_Model_Visit $visitEntityData
     * @param $sessionId
     */
    protected function _autoLogout($visitEntityData, $sessionId) {
        if (Mage::getStoreConfig('amaudit/log/logout') && Mage::getStoreConfig('amaudit/log/enableVisitHistory')) {
            $adminModel = Mage::getModel('amaudit/active');
            $username = $visitEntityData->getUsername();
            $admin = $adminModel->getCollection()
                                ->addFieldToFilter('username', $username)
                                ->getLastItem();
            $adminSessionId = $admin->getSessionId();
            $actionName = Mage::app()->getRequest()->getActionName();

            if ($adminSessionId != $sessionId) {
                if ($actionName != 'login') {
                    $adminSession = Mage::getSingleton('admin/session');
                    $adminSession->unsetAll();
                    $adminSession->getCookie()
                                 ->delete($adminSession->getSessionName());

                    Mage::getSingleton('core/cookie')->set(
                        'amasty_autologout_check', 1,
                        Mage::getSingleton('core/date')->date()+86400
                    );

                    $url = Mage::getUrl('adminhtml/index');
                    $response = Mage::app()->getFrontController()->getResponse();
                    $response->setRedirect($url);
                    $response->sendResponse();
                }
            }
        }
    }

    protected function _showMessage()
    {
        if (Mage::getStoreConfig('amaudit/log/logout') && Mage::getStoreConfig('amaudit/log/enableVisitHistory')) {
            $controllerName = Mage::app()->getRequest()->getControllerName();
            $actionName = Mage::app()->getRequest()->getActionName();

            if ($controllerName == 'index'
                && $actionName == 'login'
            ) {
                $cookie = Mage::getSingleton('core/cookie')->get('amasty_autologout_check');

                if ($cookie == 1) {
                    $msg = Mage::helper('adminhtml')->__(
                        'Someone logged into this account from another device or browser. 
                                        Your current session is terminated.'
                    );
                    $block = Mage::app()->getLayout()->getMessagesBlock();
                    $block->addWarning($msg);
                }

                Mage::getSingleton('core/cookie')->delete('amasty_autologout_check');
            }
        }
    }

    /**
     * @param $blockHead
     * @param $sessionId
     * @param Amasty_Audit_Model_Visit $visitEntityData
     * @throws Exception
     */
    protected function _saveDetailDataVisit($blockHead, $sessionId, $visitEntityData) {
        $detailData = array();

        $visitEntityData = $visitEntityData->getData();
        if (!empty($visitEntityData)) {
            $detailData['page_name'] = str_replace(" / Magento Admin", "", $blockHead->getTitle());;
            $detailData['page_url'] = Mage::helper('core/url')->getCurrentUrl();
            $detailData['session_id'] = $sessionId;
            $detailModel = Mage::getModel('amaudit/visit_detail');
            $detailModel->saveLastPageDuration($sessionId);
            Mage::getSingleton('core/session')->setLastPageTime(time());

            $detailModel->setData($detailData);
            $detailModel->save();
        }
    }
}
