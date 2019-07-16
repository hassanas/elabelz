<?php
/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

class MageWorx_OrdersSurcharge_Model_Resource_Surcharge_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('mageworx_orderssurcharge/surcharge');
    }

    public function addTotalSurchargePaidForParentOrder()
    {
        $this->addExpressionFieldToSelect(
            'surcharge_total_paid',
            new Zend_Db_Expr('(SUM(`base_total`) - SUM(`base_total_due`))'),
            '*'
            );

        return $this;
    }
}