<?php


class Progos_Api_Base_Model_Api2_Resource extends Mage_Api2_Model_Resource 
{
    /**
     *
     * @var type 
     */
    protected $_storeCode = 'en_ae';
    /**
     *
     * @var int 
     */
    protected $_defaultStoreId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID;
    /**
     *
     * @var int 
     */
    protected $_currentStoreId;
    
    /**
     *
     * @var Varien_Db_Adapter_Interface 
     */
    protected $_readConnection;
    
    /**
     *
     * @var string 
     */
    protected $_cuurencyCode;
    
    /**
     * @var string
     */
    protected $_websiteId;

    /**
     * 
     */
    public function __construct() 
    {
        $this->setStoreCode(Mage::app()->getRequest()->getParam('store'));
        Mage::app()->setCurrentStore($this->getStoreCode());
        $this->_currentStoreId = Mage::app()->getStore()->getId();
        $this->_websiteId = Mage::app()->getStore()->getWebsiteId();
        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->setCurrencyCode(Mage::app()->getStore()->getCurrentCurrencyCode());
        Mage::app()->getTranslator()->init('frontend', true);
    }
    
    /**
     * 
     * @param string $storeCode
     */
    public function setStoreCode(string $storeCode = null)
    {
        if(!is_null($storeCode))
        {
            $this->_storeCode = $storeCode;
        }
    }
    
    /**
     * 
     * @return type
     */
    public function getStoreCode()
    {
        return $this->_storeCode;
    }

   
    /**
     * 
     * @param string $currencyCode
     */
    public function setCurrencyCode(string $currencyCode)
    {
        $this->_cuurencyCode = $currencyCode;
    }
    /**
     * 
     * @return string
     */
    public function getCuurencyCode()
    {
        return $this->_cuurencyCode;
    }
}
