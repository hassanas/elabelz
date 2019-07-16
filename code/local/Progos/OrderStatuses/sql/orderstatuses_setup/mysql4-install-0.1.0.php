<?php
/**
 * This Module will create custom order statuses
 *
 * @category       Progos
 * @package        Progos_OrderStatuses
 * @copyright      Progos Tech (c) 2017
 * @Author         Hassan Ali Shahzad
 * @date           12-10-2017 17:37
 */
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

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
        array('status' => 'pending_customer_confirmation', 'label' => 'Pending Customer Confirmation'),
        array('status' => 'pending_seller_confirmation', 'label' => 'Pending Seller Confirmation'),
        array('status' => 'confirmed', 'label' => 'Confirmed')
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
        array(
            'status' => 'pending_customer_confirmation',
            'state' => 'new',
            'is_default' => 1
        ),
        array(
            'status' => 'pending_seller_confirmation',
            'state' => 'new',
            'is_default' => 0
        ),
        array(
            'status' => 'confirmed',
            'state' => 'new',
            'is_default' => 0
        )
    )
);


$installer->endSetup();