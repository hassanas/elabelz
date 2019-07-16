<?php
/**
 * @package   Lesti_Fpc
 * @author    Gul Muhamamd <gul.muhammad@progos.org>
 */

/**
 * Class Progos_FpcCache_Model_Rewrite_Observer
 */
class Progos_FpcCache_Model_Rewrite_Observer extends Lesti_Fpc_Model_Observer
{
    
    /**
     * @param $observer
     */
    public function httpResponseSendBefore($observer)
    {
        $response = $observer->getEvent()->getResponse();
        if ($this->_getFpc()->isActive() &&
            !$this->_cached &&
            Mage::helper('fpc')->canCacheRequest() &&
            $response->getHttpResponseCode() == 200) {
            $fullActionName = Mage::helper('fpc')->getFullActionName();
            $cacheableActions = Mage::helper('fpc')->getCacheableActions();
            if (in_array($fullActionName, $cacheableActions)) {
                $key = Mage::helper('fpc')->getKey();
                $body = $observer->getEvent()->getResponse()->getBody();
                $session = Mage::getSingleton('core/session');
                $formKey = $session->getFormKey();
                if ($formKey) {
                    $body = str_replace(
                        $formKey,
                        self::FORM_KEY_PLACEHOLDER,
                        $body
                    );
                    $this->_placeholder[] = self::FORM_KEY_PLACEHOLDER;
                    $this->_html[] = $formKey;
                }
                $sid = $session->getEncryptedSessionId();
                if ($sid) {
                    $body = str_replace(
                        $sid,
                        self::SESSION_ID_PLACEHOLDER,
                        $body
                    );
                    $this->_placeholder[] = self::SESSION_ID_PLACEHOLDER;
                    $this->_html[] = $sid;
                }
                // edit cacheTags via event
                $cacheTags = new Varien_Object();
                $cacheTags->setValue($this->_cacheTags);
                Mage::dispatchEvent(
                    'fpc_observer_collect_cache_tags',
                    array('cache_tags' => $cacheTags)
                );
                $this->_cacheTags = $cacheTags->getValue();
                $this->_getFpc()->save(
                    new Lesti_Fpc_Model_Fpc_CacheItem($body, time(), Mage::helper('fpc')->getContentType($response)),
                    $key,
                    $this->_cacheTags
                );
                $this->_cached = true;
                $body = str_replace($this->_placeholder, $this->_html, $body);
                $observer->getEvent()->getResponse()->setBody($body);
            }
        } else if($this->_getFpc()->isActive() && Mage::getStoreConfigFlag('bubble_queryfier/suffix_js_css/enable') && !Mage::getStoreConfigFlag('bubble_queryfier/suffix_js_css/auto') && Mage::getStoreConfig('bubble_queryfier/suffix_js_css/dynamic') && Mage::getStoreConfig('bubble_queryfier/suffix_js_css/oldsuffix') > 0 && Mage::getStoreConfig('bubble_queryfier/suffix_js_css/suffix') > 0) {
            $body = $observer->getEvent()->getResponse()->getBody();
            $modifyBody = str_replace($this->getOldQuerySuffix(), '?q='.Mage::getStoreConfig('bubble_queryfier/suffix_js_css/suffix'), $body);
            $observer->getEvent()->getResponse()->setBody($modifyBody);
        }
    } 
    
    /**
     * 
     * @return array
     */
    public function getOldQuerySuffix()
    {
        $configs = trim(Mage::getStoreConfig('bubble_queryfier/suffix_js_css/oldsuffix'));
        if ($configs) {
            $values = array_unique(array_map('trim', explode(',', $configs)));
            array_walk($values, function(&$item) { $item = '?q='. $item; });
            return $values;
        }

        return array();
    }
}
