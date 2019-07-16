<?php

$installer = $this;

$installer->startSetup();
$installer->run("

INSERT INTO `{$this->getTable('advancedpdfprocessor/variable')}` (`category_id`, `title`, `code`, `sort_order`) VALUES
	(9, 'Position', 'position', 1),
	(9, 'Product Name', 'name', 2),
	(9, 'Product SKU', 'sku', 3),
	(9, 'Quantity', 'qty', 4),
	(9, 'Price', 'price', 5),
	(9, 'Price with Discount', 'price_incl_discount', 6),
	(9, 'Price with Discount Excluding Tax', 'price_incl_discount_excl_tax', 7),
	(9, 'Price with Tax', 'price_incl_tax', 8),
	(9, 'Discount', 'discount', 9),
	(9, 'Discount Amount', 'discount_amount', 10),
	(9, 'Discount Excluding Tax', 'discount_excl_tax', 11),
	(9, 'Discount Rate', 'discount_rate', 12),
	(9, 'Tax Amount', 'tax_amount', 13),
	(9, 'Tax Rate', 'tax_rates', 14),
	(9, 'Fixed Product Tax Amount', 'weee_tax_applied_row_amount', 15),
	(9, 'Subtotal', 'row_total', 16),
	(9, 'Subtotal with Discount', 'row_total_incl_discount', 17),
	(9, 'Subtotal with Discount Excluding Tax', 'row_total_incl_discount_excl_tax', 18),
	(9, 'Subtotal with Discount and Tax', 'row_total_incl_discount_and_tax', 19),
	
	(10, 'Position', 'position', 1),
	(10, 'Product Name', 'name', 2),
	(10, 'Product SKU', 'sku', 3),
	(10, 'Quantity', 'qty', 4),
	(10, 'Price', 'price', 5),
	(10, 'Price with Discount', 'price_incl_discount', 6),
	(10, 'Price with Discount Excluding Tax', 'price_incl_discount_excl_tax', 7),
	(10, 'Price with Tax', 'price_incl_tax', 8),
	(10, 'Discount', 'discount', 9),
	(10, 'Discount Amount', 'discount_amount', 10),
	(10, 'Discount Excluding Tax', 'discount_excl_tax', 11),
	(10, 'Discount Rate', 'discount_rate', 12),
	(10, 'Tax Amount', 'tax_amount', 13),
	(10, 'Tax Rate', 'tax_rates', 14),
	(10, 'Fixed Product Tax Amount', 'weee_tax_applied_row_amount', 15),
	(10, 'Subtotal', 'row_total', 16),
	(10, 'Subtotal with Discount', 'row_total_incl_discount', 17),
	(10, 'Subtotal with Discount Excluding Tax', 'row_total_incl_discount_excl_tax', 18),
	(10, 'Subtotal with Discount and Tax', 'row_total_incl_discount_and_tax', 19),
	
	(11, 'Position', 'position', 1),
	(11, 'Product Name', 'name', 2),
	(11, 'Product SKU', 'sku', 3),
	(11, 'Quantity', 'qty', 4),
	(11, 'Price', 'price', 6),
	(11, 'Weight', 'weight', 5),
	
	(12, 'Position', 'position', 1),
	(12, 'Product Name', 'name', 2),
	(12, 'Product SKU', 'sku', 3),
	(12, 'Quantity', 'qty', 4),
	(12, 'Price', 'price', 5),
	(12, 'Price with taxes', 'price_incl_tax', 6),
	(12, 'Discount Amount', 'discount_amount', 7),
	(12, 'Discount Rate', 'discount_rate', 8),
	(12, 'Tax Amount', 'tax_amount', 9),
	(12, 'Tax Rate', 'tax_rates', 10),
	(12, 'Fixed Product Tax Amount', 'weee_tax_applied_row_amount', 11),
	(12, 'Total', 'row_total', 12),
	(12, 'Total Including Tax with Discount', 'row_total_inc', 13),
	(12, 'Subtotal with Discount and Tax', 'row_total_incl_discount_and_tax', 14);
");


$installer->endSetup();