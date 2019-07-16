<?php

$installer = $this;

$installer->startSetup();
$installer->run("


INSERT INTO `{$this->getTable('advancedpdfprocessor/variable')}` (`category_id`, `title`, `code`, `sort_order`) VALUES
	(9, 'Weight', 'weight', 4),
	(9, 'Row weight', 'row_weight', 4),
	(9, 'Is virtual', 'is_virtual', 4),
	(9, 'Description', 'description', 4),
	
	(9, 'Tax percent', 'tax_percent', 14),
	(9, 'Discount percent', 'discount_percent', 12),
	
	(9, 'Price Excl Tax', 'price_excl_tax', 8),
	(9, 'Subtotal Incl Tax', 'row_total_incl_tax', 20),
	(9, 'Base cost', 'base_cost', 25),
	(9, 'Base price', 'base_price', 26),
	(9, 'Base original price', 'base_original_price', 27),
	(9, 'Base Tax amount', 'base_tax_amount', 28),
	(9, 'Base Discount amount', 'base_discount_amount', 29),
	(9, 'Base Subtotal', 'base_row_total', 30),
	(9, 'Base Price Incl Tax', 'base_price_incl_tax', 31),
	(9, 'Base Subtotal Incl Tax', 'base_row_total_incl_tax', 32),
	(9, 'Base Discount Amount', 'base_discount_amount', 33),

	
	(10, 'Weight', 'weight', 4),
	(10, 'Row weight', 'row_weight', 4),
	(10, 'Is virtual', 'is_virtual', 4),
	(10, 'Description', 'description', 4),
	
	(10, 'Tax percent', 'tax_percent', 14),
	(10, 'Discount Percent', 'discount_percent', 12),
	
	(10, 'Price Excl Tax', 'price_excl_tax', 8),
	(10, 'Subtotal Incl Tax', 'row_total_incl_tax', 20),
	(10, 'Base Cost', 'base_cost', 25),
	(10, 'Base Price', 'base_price', 26),
	(10, 'Base Original Price', 'base_original_price', 27),
	(10, 'Base Tax Amount', 'base_tax_amount', 28),
	(10, 'Base Discount amount', 'base_discount_amount', 29),
	(10, 'Base Subtotal', 'base_row_total', 30),
	(10, 'Base Price Incl Tax', 'base_price_incl_tax', 31),
	(10, 'Base Subtotal Incl Tax', 'base_row_total_incl_tax', 32),
	(10, 'Base Discount Amount', 'base_discount_amount', 33),
	
	(11, 'Row Weight', 'row_weight', 5),
	(11, 'Price With Tax', 'price_incl_tax', 7),
	(11, 'Price Excl Tax', 'price_excl_tax', 8),
	(11, 'Subtotal Incl Tax', 'row_total_incl_tax', 9),
	(11, 'Subtotal Excl Tax', 'row_total_excl_tax', 10),
	(11, 'Discount Amount', 'discount_amount', 11),
	(11, 'Discount Percent', 'discount_percent', 12),
	(11, 'Tax Percent', 'tax_percent', 13),
	(11, 'Tax Amount', 'tax_amount', 14),
	(11, 'Is Virtual', 'is_virtual', 15),
	(11, 'Description', 'description', 16),
	(11, 'Base Cost', 'base_cost', 17),
	(11, 'Base Price', 'base_price', 18),
	(11, 'Base Original Price', 'base_original_price', 19),
	(11, 'Base Tax Amount', 'base_tax_amount', 20),
	(11, 'Base Discount Amount', 'base_discount_amount', 21),
	(11, 'Base Subtotal', 'base_row_total', 22),
	(11, 'Base Price Incl Tax', 'base_price_incl_tax', 23),
	(11, 'Base Subtotal Incl Tax', 'base_row_total_incl_tax', 24),
	(11, 'Base discount Amount', 'base_discount_amount', 25),
	
	
	(12, 'Weight', 'weight', 4),
	(12, 'Row Weight', 'row_weight', 4),
	(12, 'Is Virtual', 'is_virtual', 4),
	(12, 'Description', 'description', 4),
	(12, 'Price Excl tax', 'price_excl_tax', 6),
	(12, 'Discount Percent', 'discount_percent', 8),
	
	(12, 'Tax Percent', 'tax_percent', 10),
	(12, 'Row Total Incl Tax', 'row_total_incl_tax', 12),
	(12, 'Row Total Excl tax', 'row_total_excl_tax', 12),
	
	(12, 'Base Cost', 'base_cost', 17),
	(12, 'Base Price', 'base_price', 18),
	(12, 'Base Original Price', 'base_original_price', 19),
	(12, 'Base Tax Amount', 'base_tax_amount', 20),
	(12, 'Base Discount Amount', 'base_discount_amount', 21),
	(12, 'Base Subtotal', 'base_row_total', 22),
	(12, 'Base Price Incl Tax', 'base_price_incl_tax', 23),
	(12, 'Base Subtotal Incl Tax', 'base_row_total_incl_tax', 24),
	(12, 'Base Discount Amount', 'base_discount_amount', 25);
	
");


$installer->endSetup();