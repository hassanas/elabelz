<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category	Telr
 * @package	Telr_TelrPayments
 * @copyright	Copyright (c) 2015 Telr (https://telr.com/)
 */

class Telr_TelrPayments_Helper_Data extends Mage_Payment_Helper_Data
{
    public function getPendingPaymentStatus()
    {
        return Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
    }
}
