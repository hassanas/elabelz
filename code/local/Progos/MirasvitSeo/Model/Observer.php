<?php
/**
 * Progos
 * @author  Saroop
 * @Date    31-03-2017
 */

/*
 * Modified:
 * Hassan Ali Shahzad : Date: 29-09-2017 13:29 PM
 *
 * */

class Progos_MirasvitSeo_Model_Observer extends Mirasvit_Seo_Model_Observer
{
    /*
     * This function is extended because we want trailing slash into the end of store url.
     * start on line 40 to 45
     *
     * */
    public function checkUrl($e)
    {
        $action  = $e->getControllerAction();
        $url     = $action->getRequest()->getRequestString();
        $fullUrl = $_SERVER['REQUEST_URI'];

        if (Mage::app()->getStore()->isAdmin()) {
            return;
        }
        //Disable trailing slash redirect for hsapi and restmob
        if(strstr($fullUrl,'/restmob/') || strstr($fullUrl,'/restmobv2/') || strstr($fullUrl,'/emapi/')){
            return;
            }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        }

        if ($this->isAjax()){
            return;
        }

        $this->_redirectFromRedirecManagerUrlList();

        $this->_redirectFromOldLayeredNavigationUrl();

        $urlToRedirect = $this->getUrlWithCorrectEndSlash($url);

        if ($url != $urlToRedirect) {
            $this->redirect(rtrim(Mage::getBaseUrl(), '/') . $urlToRedirect);
        }

        if (substr($fullUrl, -4, 4) == '?p=1') {
            $this->redirect(substr($fullUrl, 0, -4));
        }

        if (in_array(trim($fullUrl,'/'), array('home', 'index.php', 'index.php/home'))) {
            $this->redirect('/');
        }
    }
    
    /*
     * This function is extended because we want trailing slash into the end of store url.
     * start on line 85 to 89
     *
     * */
    
    protected function redirect($url, $redirectType = '301')
    {
        //additional check to avoid empty rediret type value
        if (!$redirectType) {
            $redirectType = '301';
        }
        //return false for URL Tracking
        if(preg_match("/fs_.*/", $url) || preg_match("/utm_.*/", $url) || preg_match("/gclid=.*/", $url)) {
            return false;
        }
        if (strpos(Mage::helper('core/url')->getCurrentUrl(), 'customer/account')) {
            return false;
        }
        //return false if redirect exist
        foreach (Mage::app()->getResponse()->getHeaders() as $header) {
            if ($header['name'] == 'Location') {
                return false;
            }
        }
        $redirectCode = (int)$redirectType;
        Mage::app()->getFrontController()->getResponse()
            ->setRedirect($url, $redirectCode)
            ->sendResponse();
        die;
    }
}