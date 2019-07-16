<?php
class Progos_Ecoprocessor_Model_Quote_Index extends Mage_Core_Model_Abstract
{
	
	/**
     * Construct
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('ecoprocessor/quote_index');
    }
    public function getIdByQuoteId($qid)
    {
        return $this->_getResource()->getIdByQuoteId($qid);
    }

    public function getIdByReserveId($rid)
    {
        return $this->_getResource()->getIdByReserveId($rid);
    }

    public function getIdByRealOrderId($rid)
    {
        return $this->_getResource()->getIdByRealOrderId($rid);
    }
}