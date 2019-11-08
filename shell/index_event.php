<?php 
require_once('app/Mage.php');
Mage::app();
ini_set('display_errors', 1);
umask(0);
Mage::app();
$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
//database write adapter 
$write = Mage::getSingleton('core/resource')->getConnection('core_write');
//$sql        = "SELECT event_id,type,entity,entity_pk,created_at,LENGTH(old_data),LENGTH(new_data) FROM index_event WHERE LENGTH(new_data) > 331";
//$rows       = $write->fetchAll($sql); //fetchRow($sql), fetchOne($sql),...

$where = array($write ->quoteInto('LENGTH(new_data) >?', '1000000'));
$write->delete('index_event',$where);

//Zend_Debug::dump($rows);