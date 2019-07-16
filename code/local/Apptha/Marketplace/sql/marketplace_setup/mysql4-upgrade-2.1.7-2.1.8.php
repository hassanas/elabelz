<?php
$installer = $this;
$installer->startSetup();
$write = Mage::getSingleton('core/resource')->getConnection('core_write');

$installer->run("
    DROP FUNCTION IF EXISTS `get_color`;
");
$sql = "
CREATE FUNCTION `get_color`(p_id INTEGER) RETURNS varchar(80) CHARSET latin1
BEGIN
DECLARE __output varchar(80);
select concat(',Color:', cped.value ) into __output
	FROM catalog_product_entity_int as cped 
	where  
	cped.entity_id = p_id
	and cped.entity_type_id = 4 
	and store_id = 0
	and cped.attribute_id = (select attribute_id from eav_attribute where eav_attribute.entity_type_id =4 and attribute_code = 'color');
RETURN __output;
END
";
$write->exec($sql);


//
$installer->run("
    DROP FUNCTION IF EXISTS `get_configurable_product_id`;
");
$sql = "
CREATE FUNCTION `get_configurable_product_id`(p_id INTEGER) RETURNS int(11)
BEGIN
DECLARE __output INTEGER;
SELECT concat(cpsl.parent_id) into __output  FROM catalog_product_super_link as cpsl where product_id=p_id limit 1;
RETURN __output;
END
";
$write->exec($sql);

//
$installer->run("
    DROP FUNCTION IF EXISTS `get_name`;
");
$sql = "
CREATE FUNCTION `get_name`(p_id INTEGER) RETURNS varchar(800) CHARSET latin1
BEGIN
DECLARE __output varchar(800);
select cped.value  into __output
	FROM catalog_product_entity_varchar as cped 
	where  
	cped.entity_id = p_id
	and cped.entity_type_id = 4 
	and store_id = 0
	and cped.attribute_id = (select attribute_id from eav_attribute where eav_attribute.entity_type_id =4 and attribute_code = 'name');
RETURN __output;
END
";
$write->exec($sql);

//
$installer->run("
    DROP FUNCTION IF EXISTS `get_size`;
");
$sql = "
CREATE FUNCTION `get_size`(p_id INTEGER) RETURNS varchar(80) CHARSET latin1
BEGIN
DECLARE __output varchar(80);
select concat(',Size:', cped.value ) into __output
	FROM catalog_product_entity_int as cped 
	where  
	cped.entity_id = p_id
	and cped.entity_type_id = 4 
	and store_id = 0
	and cped.attribute_id = (select attribute_id from eav_attribute where eav_attribute.entity_type_id =4 and attribute_code = 'size');
RETURN __output;
END
";
$write->exec($sql);

//
$installer->run("
    DROP FUNCTION IF EXISTS `get_sku`;
");
$sql = "
CREATE FUNCTION `get_sku`(p_id INTEGER) RETURNS varchar(500) CHARSET latin1
BEGIN
DECLARE __output varchar(500);
select concat(',Sku:', c.sku ) into __output
	FROM catalog_product_entity as c
	where  
	c.entity_id = p_id;
RETURN __output;
END
";
$write->exec($sql);

//
$installer->run("
    DROP FUNCTION IF EXISTS `item_status`;
");
$sql = "
CREATE FUNCTION `item_status`(order_status varchar(40)) RETURNS varchar(40) CHARSET latin1
BEGIN
DECLARE __output varchar(40);
CASE order_status
    WHEN 'pending' THEN set __output = 'Pending Customer Confirmation';
    WHEN 'pending_seller' THEN set __output = 'Pending Seller Confirmation';
    WHEN 'rejected_customer' THEN set __output = 'Customer Rejected';
    WHEN 'rejected_seller' THEN set __output = 'Seller Rejected';
    WHEN 'ready' THEN set __output = 'Ready for Processing';
    WHEN 'processing' THEN set __output = 'Processing';
    WHEN 'shipped_from_elabelz' THEN set __output = 'Shipped from Elabelz';
	WHEN 'failed_delivery' THEN set __output = 'Failed Delivery';
	WHEN 'successful_delivery' THEN set __output = 'Successful Delivery';
	WHEN 'complete' THEN set __output = 'Completed Non Refundable';
	WHEN 'refunded' THEN set __output = 'Refunded';
	WHEN 'canceled' THEN set __output = 'Canceled';
    ELSE
        BEGIN
    END;
END CASE;
RETURN __output;
END
";
$write->exec($sql);

//
$installer->run("
    DROP FUNCTION IF EXISTS `order_status`;
");
$sql = "
CREATE FUNCTION `order_status`(order_status varchar(40)) RETURNS varchar(40) CHARSET latin1
BEGIN
DECLARE __output varchar(40);
CASE order_status
    WHEN 'pending' THEN set __output = 'Pending Confirmation';
    WHEN 'confirmed' THEN set __output = 'Confirmed';
    WHEN 'pending_customer_confirmation' THEN set __output = 'Pending Customer Confirmation';
    WHEN 'pending_seller_confirmation' THEN set __output = 'Pending Seller Confirmation';
    WHEN 'processing' THEN set __output = 'Processing';
    WHEN 'shipped_from_elabelz' THEN set __output = 'Shipped from Elabelz';
    WHEN 'successful_delivery_partially' THEN set __output = 'Successful Delivery Partially';
	WHEN 'failed_delivery' THEN set __output = 'Failed Delivery';
	WHEN 'successful_delivery' THEN set __output = 'Successful Delivery';
	WHEN 'complete' THEN set __output = 'Completed Non Refundable';
	WHEN 'refunded' THEN set __output = 'Refunded';
	WHEN 'closed' THEN set __output = 'Closed';
    WHEN 'canceled' THEN set __output = 'Canceled';
    ELSE
        BEGIN
    END;
END CASE;
RETURN __output;
END
";
$write->exec($sql);


//
$installer->run("
    DROP FUNCTION IF EXISTS `seller_detail`;
");
$sql = "
CREATE FUNCTION `seller_detail`(c_id int) RETURNS varchar(800) CHARSET latin1
BEGIN
DECLARE __output varchar(800);
select  
	CONCAT(
		IF(msp.store_title IS NOT NULL, CONCAT('Store Title:', msp.store_title, ','), 'Store Title:Untitled,' ), 
		'Email:',c.email, 
        IF(cev.value IS NOT NULL, CONCAT(',Name:', cev.value),',Name:' ),  
        IF(cev.value IS NOT NULL, CONCAT(' ', cev1.value),',' ),   
		IF(msp.contact IS NOT NULL, CONCAT(',Contact:', msp.contact),',Contact:' ),  
		IF(msp.state IS NOT NULL, CONCAT(',State:', msp.state),',State:' ),
		IF(msp.country IS NOT NULL,CONCAT(' ,', get_country_name(msp.country)) ,' ,')
	)  into __output
from customer_entity as c
left join marketplace_sellerprofile as msp on c.entity_id = msp.seller_id  
left join customer_entity_varchar as cev on c.entity_id = cev.entity_id and  cev.entity_type_id = 1 and cev.attribute_id = (select attribute_id from eav_attribute where eav_attribute.entity_type_id =1 and attribute_code = 'firstname')
left join customer_entity_varchar as cev1 on c.entity_id = cev1.entity_id and  cev1.entity_type_id = 1 and cev1.attribute_id = (select attribute_id from eav_attribute where eav_attribute.entity_type_id =1 and attribute_code = 'lastname')
where c.entity_id = c_id limit 1;
RETURN __output;
END
";
$write->exec($sql);


//
$installer->run("
    DROP FUNCTION IF EXISTS `sms_status`;
");
$sql = "
CREATE FUNCTION `sms_status`(cid varchar(4)) RETURNS varchar(11) CHARSET latin1
BEGIN
DECLARE v VARCHAR(4);
if cid = 'yes' THEN set v = 'Yes';
else if cid = 'no'  THEN set v = 'No';
else set v = 'N/A';
END IF;
END IF;
    RETURN v;
END
";
$write->exec($sql);



//
$installer->run("
    DROP FUNCTION IF EXISTS `special_price`;
");
$sql = "
CREATE FUNCTION `special_price`(p_id INTEGER, currency varchar(10)) RETURNS varchar(90) CHARSET latin1
BEGIN
DECLARE __output VARCHAR(90);
DECLARE cp_id INTEGER;

set cp_id = get_configurable_product_id(p_id);
set __output = 'N/A';
	select  round(cped.value,0)  into __output
	FROM catalog_product_entity_decimal as cped 
	where  
	cped.entity_id = IF(cp_id  IS NOT NULL, cp_id,  p_id )
	and cped.entity_type_id = 4 
	and store_id = 0
	and cped.attribute_id = (select attribute_id from eav_attribute where eav_attribute.entity_type_id =4 and attribute_code = 'special_price');
	if __output > 0 then set __output = concat(currency, ' ', __output) ;
    END IF;
    RETURN __output;
END
";
$write->exec($sql);



//
$installer->run("
    DROP FUNCTION IF EXISTS `order_detail`;
");
$sql = "
CREATE FUNCTION `order_detail`(cid varchar(255)) RETURNS varchar(600) CHARSET latin1
BEGIN
DECLARE order_detail VARCHAR(600);
select 
CONCAT(
	'Email:',o.customer_email, 
	',Name:', oa.firstname, ' ', oa.lastname,
	',Billing:Name:', oa.firstname, ' ', oa.lastname,
	'Contact:',IF(oa.telephone IS NOT NULL, oa.telephone,'NA' ), 
	'Address:',
	IF(oa.street IS NOT NULL, oa.street,'NA' ), ',', 
	IF(oa.region IS NOT NULL, oa.region,'NA' ) , ',',
	IF(oa.city IS NOT NULL, oa.city,'NA' ) , ',',
	get_country_name(oa.country_id)
) into order_detail

from sales_flat_order as o
inner join sales_flat_order_address as oa on o.entity_id = oa.parent_id
where o.entity_id = cid and oa.address_type = 'billing' ;
RETURN order_detail;
END
";
$write->exec($sql);

//
$installer->run("
    DROP FUNCTION IF EXISTS `get_base_currency_code`;
");
$sql = "
CREATE FUNCTION `get_base_currency_code`() RETURNS varchar(6) CHARSET latin1
BEGIN
	DECLARE __output varchar(6);
	SELECT concat(value) into __output FROM core_config_data 
	where path ='currency/options/base' and scope ='default' and scope_id=0 
	limit 1;
RETURN __output;
END
";
$write->exec($sql);


//
$installer->run("
    DROP FUNCTION IF EXISTS `get_country_name`;
");
$sql = "
CREATE FUNCTION `get_country_name`(country_code varchar(6)) RETURNS varchar(50) CHARSET latin1
BEGIN
declare __output varchar(50);
set __output = country_code;
CASE country_code
    WHEN 'AE' THEN set __output = 'United Arab Emirates';
    WHEN 'SA' THEN set __output = 'Saudi Arabia';
    WHEN 'QA' THEN set __output = 'Qatar';
    WHEN 'OM' THEN set __output = 'Oman';
    WHEN 'KW' THEN set __output = 'Kuwait';
    WHEN 'IQ' THEN set __output = 'Iraq';
    WHEN 'BH' THEN set __output = 'Bahrain';
    ELSE
        BEGIN
    END;
END CASE;
RETURN __output;
END
";
$write->exec($sql);

$installer->endSetup();