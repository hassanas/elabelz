<?php
class Progos_Partialindex_Model_Indexer extends Mage_Index_Model_Indexer_Abstract
{
    /**
     * @var bool
     */
    protected $_registered = false;

    /**
     * @var bool
     */
    protected $_processed = false;
 
    /**
     * @var array
     */
    protected $_matchedEntities = array( Mage_Catalog_Model_Product::ENTITY => array( Mage_Index_Model_Event::TYPE_SAVE));
        
    /**
     * Construct
    */
    public function _construct()
    {
        parent::_construct();
        $this->_init('partialindex/product_index');
    }

    /**
    * Retrieve Indexer name
    * @return string
    */
    public function getName()
    {
        return Mage::helper('partialindex')->__('Partial Index');
    }
 
    /**
     * Retrieve Indexer description
     * @return string
     */
    public function getDescription()
    {
        return Mage::helper('partialindex')->__('Reindex only modified products');
    }

    /**
     * Register indexer required data inside event object
     *
     * @param Mage_Index_Model_Event $event
     *
     * @return Progos_Partialindex_Model_Indexer
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        if($this->_registered) return $this;
        $this->_registered = true;
        return $this;
    }

    /**
     * Process event based on event state data
     *
     * @param Mage_Index_Model_Event $event
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        if(!$this->_processed) {
            $this->_processed = true;
        }
    }
}