<?php
/**
 * MageWorx
 * MageWorx Order Surcharge Extension
 *
 * @category   MageWorx
 * @package    MageWorx_OrdersSurcharge
 * @copyright  Copyright (c) 2016 MageWorx (http://www.mageworx.com/)
 */

/**
 * Class MageWorx_OrdersSurcharge_Model_Surcharge
 *
 * @method int getStoreId()
 * @method MageWorx_OrdersSurcharge_Model_Surcharge setStoreId(int $id)
 * @method int getCustomerId()
 * @method MageWorx_OrdersSurcharge_Model_Surcharge setCustomerId(int $id)
 * @method string getCustomerEmail()
 * @method MageWorx_OrdersSurcharge_Model_Surcharge setCustomerEmail(string $email)
 * @method int getParentOrderId()
 * @method MageWorx_OrdersSurcharge_Model_Surcharge setParentOrderId(int $id)
 * @method int getOrderId()
 * @method MageWorx_OrdersSurcharge_Model_Surcharge setOrderId(int $id)
 * @method int getCreatedAt()
 * @method MageWorx_OrdersSurcharge_Model_Surcharge setCreatedAt(int $timestamp)
 * @method int getUpdatedAt()
 * @method MageWorx_OrdersSurcharge_Model_Surcharge setUpdatedAt(int $timestamp)
 * @method int|float getBaseTotal()
 * @method MageWorx_OrdersSurcharge_Model_Surcharge setBaseTotal(number $total)
 * @method int|float getBaseTotalDue()
 * @method MageWorx_OrdersSurcharge_Model_Surcharge setBaseTotalDue(number $total)
 * Statuses: @see MageWorx_OrdersSurcharge_Model_System_Config_Source_Surcharge_Status
 * @method int getStatus()
 * @method MageWorx_OrdersSurcharge_Model_Surcharge setStatus(int $status)
 * @method int|string getOrderIncrementId()
 * @method MageWorx_OrdersSurcharge_Model_Surcharge setOrderIncrementId($incrementId)
 */
class MageWorx_OrdersSurcharge_Model_Surcharge extends Mage_Core_Model_Abstract
{

    const STATUS_DELETED = 0;
    const STATUS_PENDING = 1;
    const STATUS_PROCESSING = 2;
    const STATUS_PAID = 3;
    const STATUS_COMPLETE = 4;

    public function _construct()
    {
        $this->_init('mageworx_orderssurcharge/surcharge');
    }

    /**
     * Load surcharge by parent order
     *
     * @param Mage_Sales_Model_Order $order
     * @return MageWorx_OrdersSurcharge_Model_Resource_Surcharge_Collection
     */
    public function loadByParentOrder(Mage_Sales_Model_Order $order)
    {
        return $this->loadByParentOrderId($order->getId());
    }

    /**
     * Load surcharge by parent order id
     *
     * @param int $id
     * @return MageWorx_OrdersSurcharge_Model_Resource_Surcharge_Collection
     */
    public function loadByParentOrderId($id)
    {
        $collection = $this->getCollection()
            ->addFilter('parent_order_id', $id)
            ->addOrder('base_total_due', Varien_Data_Collection::SORT_ORDER_DESC);

        return $collection;
    }

    /**
     * Collect sum base total paid (paid surcharges) for order
     *
     * @param $order
     * @return float
     */
    public function getTotalSurchargePaidForOrder($order)
    {
        if ($order instanceof Mage_Sales_Model_Order) {
            $orderId = $order->getId();
        } else {
            $orderId = $order;
        }

        $collection = $this->getCollection()
            ->addTotalSurchargePaidForParentOrder()
            ->addFilter('parent_order_id', $orderId)
            ->load();

        $item = $collection->getFirstItem();
        $totalPaid = $item->getSurchargeTotalPaid();
        $totalPaid = floatval($totalPaid);

        return $totalPaid;
    }

    /**
     * Change surcharge status and invoice parent order when surcharge order was invoiced
     *
     * @param Mage_Sales_Model_Order $order
     * @return $this
     * @throws Exception
     */
    public function applySurcharge(Mage_Sales_Model_Order $order)
    {
        $baseTotalDue = $this->getBaseTotalDue();
        $baseTotalPaid = $order->getBaseTotalPaid();

        // If surcharge order was not paid fully do nothing
        if ($baseTotalDue > $baseTotalPaid) {
            return $this;
        }

        // If surcharge was already paid do nothing
        if ($this->getBaseTotalDue() <= 0) {
            return $this;
        }

        $parentOrder = Mage::getModel('sales/order')->load($this->getParentOrderId());
        $origParentOrder = clone $parentOrder;

        // If parent order already surcharged do nothing
        $availableLinkedAmount = $parentOrder->getBaseLinkedAmount() - $parentOrder->getBaseLinkedInvoiced() - $parentOrder->getBaseLinkedRefunded();
        if ($availableLinkedAmount >= 0) {
            return $this;
        }

        // Update surcharge
        $this->setOrderId($order->getEntityId());
        $this->setOrderIncrementId($order->getIncrementId());
        $this->setBaseTotalDue(0);
        $this->setStatus(self::STATUS_PAID);
        $this->save();

        // Invoice parent order changes without capture
        /** @var Mage_Sales_Model_Resource_Order_Invoice_Collection $invoices */
        $invoices = $parentOrder->getInvoiceCollection();
        if (count($invoices)) {
            $parentOrder->setBaseLinkedAmountToBeInvoiced($this->getBaseTotal() - $this->getBaseTotalDue());
            Mage::getSingleton('mageworx_ordersedit/edit_invoice')
                ->invoiceChanges($origParentOrder, $parentOrder, Mage_Sales_Model_Order_Invoice::NOT_CAPTURE, true);
        }

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Order $order [surcharge order]
     * @return $this
     * @throws Exception
     */
    public function refund(Mage_Sales_Model_Order $order = null)
    {
        /** @var MageWorx_OrdersSurcharge_Helper_Data $helper */
        $helper = Mage::helper('mageworx_orderssurcharge');

        if (!$order || $order->getId() != $this->getOrderId()) {
            $order = Mage::getModel('sales/order')->load($this->getOrderId());
        }

        if (!$order->getId()) {
            $error = $helper->__('Unable to load order #%s', $this->getOrderId());
            throw new Exception($error);
        }

        $baseTotalDue = $this->getBaseTotalDue();
        $baseTotalPaid = $order->getBaseTotalPaid();

        if ($baseTotalDue > $baseTotalPaid) {
            return $this;
        }

        $this->setBaseTotalDue($this->getBaseTotal());
        $this->setStatus(self::STATUS_PROCESSING);
        $this->save();
        $this->returnAmountToParentOrder($order->getBaseTotalRefunded());

        return $this;
    }

    /**
     * Restore parent order amount after cancel or delete surcharge
     *
     * @param null $amount
     * @return $this
     */
    public function returnAmountToParentOrder($amount = null)
    {
        if (!$amount) {
            $amount = $this->getBaseTotal();
        }
        /** @var MageWorx_OrdersSurcharge_Helper_Data $helper */
        $helper = Mage::helper('mageworx_orderssurcharge');
        /** @var Mage_Sales_Model_Order $parentOrder */
        $parentOrder = Mage::getModel('sales/order')->load($this->getParentOrderId());
        if (Mage::registry('ordersedit_order')) {
            Mage::unregister('ordersedit_order');
        }
        Mage::register('ordersedit_order', $parentOrder);


        /** @var Mage_Sales_Model_Quote $parentOrderQuote */
        $parentOrderQuote = Mage::getModel('mageworx_ordersedit/edit')->getQuoteByOrder($parentOrder);
        $newBaseLinkedAmount = $parentOrderQuote->getBaseLinkedAmount() + $amount;
        $newLinkedAmount = $helper->convertBaseToOrderRate($newBaseLinkedAmount, $parentOrder);
        $parentOrderQuote->setBaseLinkedAmount($newBaseLinkedAmount);
        $parentOrderQuote->setLinkedAmount($newLinkedAmount);
        $parentOrderQuote->setTotalsCollectedFlag(false)->collectTotals();

        Mage::getSingleton('mageworx_ordersedit/edit')
            ->setOrder($parentOrder)
            ->setQuote($parentOrderQuote)
            ->saveOrder();

        return $this;
    }

    /**
     * Validate surcharge by customer id or email
     * If customer is not set validate by current customer (from session)
     *
     * @param Mage_Customer_Model_Customer|null $customer
     * @return bool
     */
    public function validateByCustomer(Mage_Customer_Model_Customer $customer = null)
    {
        if (!$this->getCustomerId() && !$this->getCustomerEmail()) {
            return false;
        }

        if (!$customer) {
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = $this->getCurrentCustomer();
        }

        $customerId = $customer->getId();
        $customerEmail = $customer->getEmail();

        if (!$customerId && !$customerEmail) {
            return false;
        }

        if ($this->getCustomerId() == $customerId) {
            return true;
        }

        if ($this->getCustomerEmail() == $customerEmail) {
            return true;
        }

        return false;
    }

    /**
     * Set "deleted" status to surcharge
     * Real delete available only for admin
     *
     * @return $this
     * @throws Exception
     */
    public function deleteByCustomer()
    {
        $helper = Mage::helper('mageworx_orderssurcharge');

        $this->setStatus(self::STATUS_DELETED);
        $this->save();

        $logger = $helper->getLogger();
        $text = $helper->__('The payment link with ID #%s was canceled by the customer.', $this->getId());
        $order = Mage::getModel('sales/order')->load($this->getParentOrderId());
        $logger->log($text, $order, 1);

        return $this;
    }

    /**
     * Call restore method for each object in collection (filtered by ids)
     *
     * @param $surchargeIds
     * @return $this
     */
    public function restoreByIds($surchargeIds)
    {
        $collection = $this->getCollection()->addFieldToFilter('entity_id', array('in' => $surchargeIds));
        $items = $collection->getItems();

        /** @var MageWorx_OrdersSurcharge_Model_Surcharge $item */
        foreach ($items as $item) {
            $item->restore();
        }

        return $this;
    }

    /**
     * Call delete method for each object in collection (filtered by ids)
     *
     * @param $surchargeIds
     * @return $this
     * @throws Exception
     */
    public function deleteByIds($surchargeIds)
    {
        $collection = $this->getCollection()->addFieldToFilter('entity_id', array('in' => $surchargeIds));
        $items = $collection->getItems();

        /** @var MageWorx_OrdersSurcharge_Model_Surcharge $item */
        foreach ($items as $item) {
            $item->delete();
        }

        return $this;
    }

    /**
     * Call updateStatus method for each object in collection
     * (filtered by ids and statuses, not equals to new status)
     *
     * @param $surchargeIds
     * @param $status
     * @return $this
     */
    public function updateStatusByIds($surchargeIds, $status)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $surchargeIds))
            ->addFieldToFilter('status', array('nin' => array($status)));
        $items = $collection->getItems();

        /** @var MageWorx_OrdersSurcharge_Model_Surcharge $item */
        foreach ($items as $item) {
            $item->updateStatus($status);
        }

        return $this;
    }

    public function updateStatus($newStatus)
    {
        /** @var MageWorx_OrdersSurcharge_Helper_Data $helper */
        $helper = Mage::helper('mageworx_orderssurcharge');
        $prevStatus = $this->getOrigData('status');

        switch ($newStatus) {
            case self::STATUS_DELETED:
            case self::STATUS_PENDING:
            case self::STATUS_PROCESSING:
                if (in_array($prevStatus, array(self::STATUS_COMPLETE, self::STATUS_PAID))) {
                    $this->setBaseTotalDue($this->getBaseTotal());
                }
                $this->setStatus($newStatus);
                break;
            case self::STATUS_PAID:
            case self::STATUS_COMPLETE:
                if (in_array($prevStatus, array(self::STATUS_DELETED, self::STATUS_PENDING, self::STATUS_PROCESSING))) {
                    $this->setBaseTotalDue(0);
                }
                $this->setStatus($newStatus);
                break;
            default:
                throw new Exception($helper->__('Unknown status: %s', $newStatus));
        }

        $this->save();
    }

    /**
     * Update surcharge order id & order increment id
     *
     * @param Mage_Sales_Model_Order $order
     * @return $this
     * @throws Exception
     */
    public function updateOrderIdAndIncrementId($order)
    {
        $needSave = false;

        if (!$this->getOrderId()) {
            $this->setOrderId($order->getId());
            $needSave = true;
        }

        if (!$this->getOrderIncrementId()) {
            $this->setOrderIncrementId($order->getIncrementId());
            $needSave = true;
        }

        if ($needSave) {
            $this->save();
        }

        return $this;
    }

    /**
     * Set status to new for deleted surcharge
     * Set status to new and restore total due for surcharge with complete status
     *
     * @return $this
     * @throws Exception
     */
    public function restore()
    {
        $saveNeeded = false;
        $currentStatus = $this->getStatus();
        if ($currentStatus == self::STATUS_DELETED) {
            $this->setStatus(self::STATUS_PENDING);
            $saveNeeded = true;
        } elseif ($currentStatus == self::STATUS_COMPLETE) {
            $this->setStatus(self::STATUS_PENDING);
            $this->setBaseTotalDue($this->getBaseTotal());
            $saveNeeded = true;
        }

        if ($saveNeeded) {
            $this->save();
        }

        return $this;
    }

    /**
     *
     * @return bool
     */
    public function canPay()
    {
        if ($this->getBaseTotalDue() > 0 && $this->getStatus() == self::STATUS_PENDING) {
            return true;
        }
        
        return false;
    }

    /**
     * Check is surcharge unpaid
     * For paid surcharges only cancellation process available
     *
     * @return bool
     */
    public function canDelete()
    {
        $deletableStatus = !in_array(
            $this->getStatus(),
            array(
                self::STATUS_DELETED,
                self::STATUS_COMPLETE,
                self::STATUS_PAID
            )
        );
        $deletableTotals = $this->getBaseTotalDue() == $this->getBaseTotal();
        $result = $deletableTotals && $deletableStatus;

        return $result;
    }

    /**
     * Check is the surcharge already paid
     * @return bool
     */
    public function isAlreadyPaid()
    {
        if ($this->getBaseTotalDue() > 0) {
            return false;
        }

        return true;
    }

    /**
     * @return MageWorx_OrdersSurcharge_Model_Surcharge
     */
    protected function _beforeDelete()
    {
        parent::_beforeDelete();

        $refunded = false;

        if ($this->getOrderId()) {
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->load($this->getOrderId());

            if ($order->getBaseTotalPaid() <= 0) {
                $order->cancel();
            }

            if ($order->getBaseTotalPaid() > 0) {
                $this->refund($order);
                $refunded = true;
            }
        }

        if ($this->getParentOrderId()) {
            if (!$refunded) {
                $this->returnAmountToParentOrder();
            }
        }

        return $this;
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();
        $this->setUpdatedAt(Mage::app()->getLocale()->date()->getTimestamp());
    }

    /**
     * Get the current customer from a session
     * @return Mage_Customer_Model_Customer
     */
    public function getCurrentCustomer()
    {
        return Mage::getSingleton('customer/session')->getCustomer();
    }

    /**
     * Get status label for current surcharge
     *
     * @return string|bool
     */
    public function getStatusLabel()
    {
        $statuses = $this->getStatusesArray();
        $status = $this->getStatus();

        return isset($statuses[$status]) ? $statuses[$status] : false;
    }

    /**
     * Get all surcharge statuses as array
     * key: status id, value: status label
     *
     * @return array
     */
    public function getStatusesArray()
    {
        return Mage::getModel('mageworx_orderssurcharge/system_config_source_surcharge_status')->toArray();
    }
}
