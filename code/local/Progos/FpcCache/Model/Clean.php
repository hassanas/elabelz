<?php
/**
* @category Progos
* @package Progos_FpcCache
* @author Gul Muhammad <gul.muhamamd@progos.org>
*/

class Progos_FpcCache_Model_Clean 
{
    /*
     * @var array
     */
    private $_pages = array('category', 'product', 'cms', 'brand_index', 'brand_view', 'apiproducts', 'apifilters', 'apiautocomplete', 'apiindex','menu','apitablerate','productdetailsimpleajax');

    /*
     * observer 
     */
    public function controllerActionPredispatchAdminhtmlCacheMassRefresh()
    {
        $types = Mage::app()->getRequest()->getParam('types');
        $isActive = $this->isActive();
        if($isActive) {
            if(is_array($types)) {
                foreach ($types as $type) {
                    $this->_clean($type);
                }
            } else {
                $this->_clean($types);
            }
        }
        
    }
    
    /*
     * @get fpc model
     */
    public function getFpc()
    {
        return Mage::getSingleton('fpc/fpc');
    }
    
    /*
     * check fpc is active
     */
    public function isActive()
    {
        return $this->getFpc()->isActive();
    }
    
    /*
     * check cahe type is enabled 
     */
    protected function _isActive($key)
    {
        return Mage::app()->useCache($key);
    }
    
    /*
     * clear cache of respective page
     */
    protected function _clean($type) 
    {
        if($this->_isActive($type) && in_array($type, $this->_pages)) {
            $this->cleanCache($type);
        }
    }
    
    /*
     * clean cache tag
     */
    function cleanCache($key) {
        $this->getFpc()->clean(sha1($key));
    }
    
}
