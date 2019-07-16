<?php
/**
 * Progos_OrdersStatus
 *
 * @category    Progos
 * @package     Progos_OrdersStatus
 * @author      Sergejs Plisko <sergejs.plisko@redboxdigital.com>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progostech.com)
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

// Required tables
$statusTable = $installer->getTable('sales/order_status');
$statusStateTable = $installer->getTable('sales/order_status_state');

// Insert statuses
$installer->getConnection()->insertArray(
    $statusTable,
    array(
        'status',
        'label'
    ),
    array(
        array('status' => 'canceled_by_customer', 'label' => 'Canceled by Customer'),
        array('status' => 'canceled_oos', 'label' => 'Canceled - Out of Stock'),
        array('status' => 'canceled_no_response', 'label' => 'Canceled - No Response'),
        array('status' => 'canceled_reorder', 'label' => 'Canceled - Re Ordered'),
        array('status' => 'canceled_test', 'label' => 'Canceled - Test'),

        array('status' => 'payment_declined', 'label' => 'Payment Declined'),

        array('status' => 'pending_supplier', 'label' => 'Pending Supplier Confirmation'),

        array('status' => 'return_full', 'label' => 'Full Return'),
        array('status' => 'return_partial', 'label' => 'Partial Return'),

        array('status' => 'wh_delivery_failed', 'label' => 'Arrived to WH - Failed on Delivery'),
        array('status' => 'wh_return_partial', 'label' => 'Arrived to WH - Partial Return'),
        array('status' => 'wh_return_full', 'label' => 'Arrived to WH - Full Return'),

        array('status' => 'closed_non_refund', 'label' => 'Closed - None Refundable')
    )
);

// Insert states and mapping of statuses to states
$installer->getConnection()->insertArray(
    $statusStateTable,
    array(
        'status',
        'state',
        'is_default'
    ),
    array(
        array('status' => 'canceled_by_customer', 'state' => 'canceled', 'is_default' => 0),
        array('status' => 'canceled_oos', 'state' => 'canceled', 'is_default' => 0),
        array('status' => 'canceled_no_response', 'state' => 'canceled', 'is_default' => 0),
        array('status' => 'canceled_reorder', 'state' => 'canceled', 'is_default' => 0),
        array('status' => 'canceled_test', 'state' => 'canceled', 'is_default' => 0),

        array('status' => 'payment_declined', 'state' => 'canceled', 'is_default' => 0),

        array('status' => 'pending_supplier', 'state' => 'processing', 'is_default' => 0),

        array('status' => 'return_full', 'state' => 'complete', 'is_default' => 0),
        array('status' => 'return_partial', 'state' => 'complete', 'is_default' => 0),

        array('status' => 'wh_delivery_failed', 'state' => 'complete', 'is_default' => 0),
        array('status' => 'wh_return_partial', 'state' => 'complete', 'is_default' => 0),
        array('status' => 'wh_return_full', 'state' => 'complete', 'is_default' => 0),

        array('status' => 'closed_non_refund', 'state' => 'closed', 'is_default' => 0)
    )
);