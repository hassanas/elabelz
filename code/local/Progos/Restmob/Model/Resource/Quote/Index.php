<?php
class Progos_Restmob_Model_Resource_Quote_Index extends Mage_Core_Model_Mysql4_Abstract
{

    /**
     * Construct
     */
    public function _construct()
    {
        $this->_init('restmob/quote_index','id');
    }

    /**
     * Get product identifier by sku
     *
     * @param string $sku
     * @return int|false
     */
    public function getIdByReserveId($rid)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from('sales_quote_restmob', 'id')
            ->where('reserved_order_id = :reserved_order_id');

        $bind = array(':reserved_order_id' => $rid);

        return $adapter->fetchOne($select, $bind);
    }

    /**
     * Get product identifier by sku
     *
     * @param string $sku
     * @return int|false
     */
    public function getIdByRealOrderId($rid)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from('sales_quote_restmob', 'id')
            ->where('real_order_id = :real_order_id');

        $bind = array(':real_order_id' => $rid);

        return $adapter->fetchOne($select, $bind);
    }

    /**
     * Get identifier by qid where storecredit is 1
     *
     * @param int $qid
     * @return int|false
     */
    public function getIdByQuoteId($qid)
    {
        $adapter = $this->_getReadAdapter();

        $select = $adapter->select()
            ->from('sales_quote_restmob', 'id')
            ->where('qid = :qid');

        $bind = array(':qid' => $qid);

        return $adapter->fetchOne($select, $bind);
    }

}