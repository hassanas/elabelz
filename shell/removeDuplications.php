<?php

chdir(dirname(__FILE__));

ini_set('memory_limit', '1024M');
ini_set('display_errors', 1);

require '../app/bootstrap.php';
require '../app/Mage.php';

Mage::app('admin')->setUseSessionInUrl(false);

umask(0);

/*
* Run until the color_duplicate_attribute & size_duplicate_attribute log files stop adding duplicate options logs 
* to the file, confirm from database or from the Magento admin panel if any duplicate option is remained, 
* check several product ids in the log files to confirm if they have been assigned to the correct primary option 
*/
class removeDuplications
{
	private $_resource;
	private $_readConnection;
	private $_writeConnection;
	private $_isDryRun;
	private $_logFile;

	function __construct()
	{
		$this->_resource = Mage::getSingleton('core/resource');
		$this->_readConnection = $this->_resource->getConnection('core_read');
		$this->_writeConnection = $this->_resource->getConnection('core_write');

		global $argv;
		if(isset($argv[1]) && $argv[1] === "dryrun") {
			$this->_isDryRun = 1;
			$this->_logFile = '_Dryrun__duplicate_attribute'.date("Y-m-d").'.log';
		} else {
			$this->_isDryRun = 0;
			$this->_logFile = '_duplicate_attribute'.date("Y-m-d").'.log';
		}
	}

	public function startProcessing($attributes) 
	{
		/* get all the attributes duplicate options and there related products */
		$data = $this->getDuplicatedProducts($attributes);

		/* assign all the duplicated option products to the orignal(primary) option id */
		$this->assignDuplicateOptionProductsToOriginalId($data);

		/* delete the duplicate option but only if no related product is assigned to it anymore*/
		$this->deleteDuplicateOptions($data);

		/* To run recursivley, uncomment this line */
		if(count($data) && $this->_isDryRun == 0) {		
			$this->startProcessing($attributes);
		}
	}

	public function getDuplicatedProducts($attributes)
	{
		$data = array();
		$count = 0;

		foreach ($attributes as $key => $attribute) {
			echo '|';
			$query = 
				"SELECT MIN(eapv.option_id) AS original_id, MAX(eapv.option_id) AS duplicate_id, 
					LOWER(REPLACE(REPLACE(VALUE, ' ', ''), '-', '')) AS val,
					COUNT(LOWER(REPLACE(REPLACE(VALUE, ' ', ''), '-', ''))) AS c
				FROM eav_attribute_option_value eapv
				LEFT JOIN `eav_attribute_option` AS eap ON eap.option_id = eapv.option_id
				WHERE eap.attribute_id = $key AND eapv.store_id = 0
				GROUP BY val
				HAVING c >1";
			/* get all the options with the duplicate values, wheather it be color or size or else*/
			$allOptions = $this->_readConnection->fetchAll($query);

			foreach ($allOptions as $option) {
				echo 'o';
				/* just to dobule check in php if the value is really duplicated, mysql also returns the different case strings in php we can compare for that as well */
				$query = "SELECT * FROM eav_attribute_option_value WHERE option_id IN(".$option['original_id'].",".$option['duplicate_id'].") AND store_id = 0";
				$results = $this->_readConnection->fetchAll($query);

				if(strtolower(str_replace(" ", "", str_replace("-", "", $results[0]['value']))) === 
					strtolower(str_replace(" ", "", str_replace("-", "", $results[1]['value']))) ) { 
					// exact same values (if strtolower removed)
					$parentId = $option['original_id'];
					$duplicateId = $option['duplicate_id'];

					$data[$count]['original_id'] = $parentId;
					$data[$count]['duplicate_id'] = $duplicateId;
					$data[$count]['attribute_type'] = $attribute;
					$data[$count]['attribute_id'] = $key;
					$data[$count][$attribute.'_value'] = $option['val'];

					// print all products name with the original option id
					$attributeOptionId = $parentId;
					$originalIdProducts = Mage::getModel('catalog/product')
					                    ->getCollection()
					                    ->addAttributeToFilter($attribute, $attributeOptionId);
					if($originalIdProducts->count())  {
						$data[$count]['parent_id_products'] = implode($originalIdProducts->getAllIds(), ",");
					}

					// print all products name with the duplicate option id
					$attributeOptionId = $duplicateId; 
					$duplicateIdProducts = Mage::getModel('catalog/product')
					                    ->getCollection()
					                    ->addAttributeToFilter($attribute, $attributeOptionId);
					if($duplicateIdProducts->count())  {
						$data[$count]['duplicate_id_products'] = implode($duplicateIdProducts->getAllIds(), ",");
					}

				}
				$count++;
			}
			Mage::log("Memory Usage : ".(memory_get_peak_usage(true)/1024/1024)." MB", null, $attribute.$this->_logFile);
		}
		return $data;
	}

	public function assignDuplicateOptionProductsToOriginalId($data)
	{
		$tableName = $this->_resource->getTableName('catalog_product_entity_int');

		foreach ($data as $key => $value) {
			echo '.';
			if(array_key_exists('duplicate_id_products', $value) && count($value['duplicate_id_products'])) {	
				echo ',';
				Mage::log("Original Option Id: ".$value['original_id'], null, $value['attribute_type'].$this->_logFile);
				Mage::log("Duplicate Option Id:".$value['duplicate_id'], null, $value['attribute_type'].$this->_logFile);
				Mage::log("Option Value:".$value[$value['attribute_type'].'_value'], null, $value['attribute_type'].$this->_logFile);
				Mage::log("Duplicate Option related Products which should now be related to Original Option Id:".
					$value['duplicate_id_products'], null, $value['attribute_type'].$this->_logFile);
				$query = 
					"UPDATE {$tableName} SET value = {$value['original_id']} 
						WHERE entity_id IN({$value['duplicate_id_products']}) 
						AND attribute_id = {$value['attribute_id']}";
				if($this->_isDryRun == 0) {
					$this->_writeConnection->query($query);
				}
				Mage::log("Update Query: ".$query.PHP_EOL.PHP_EOL, null, $value['attribute_type'].$this->_logFile);
			}
		}
	}

	public function deleteDuplicateOptions($data)
	{
		$tableName = $this->_resource->getTableName('eav_attribute_option');

		foreach ($data as $option) {
			echo 'd';
			$products = Mage::getModel('catalog/product')
			                    ->getCollection()
			                    ->addAttributeToFilter($option['attribute_type'], $option['duplicate_id']);
			if($products->count() == 0) {
				Mage::log(PHP_EOL."We can safely delete Option ".$option[$option['attribute_type'].'_value']." Id: ".$option['duplicate_id'].PHP_EOL, null, $option['attribute_type'].$this->_logFile);
				$query = "DELETE FROM {$tableName} WHERE option_id = {$option['duplicate_id']};";
				if($this->_isDryRun == 0) {
					$this->_writeConnection->query($query);
				}
			} else {
				if($this->_isDryRun == 1) {
					Mage::log(PHP_EOL."We can safely delete Option ".$option[$option['attribute_type'].'_value']." Id: ".$option['duplicate_id'].PHP_EOL, null, $option['attribute_type'].$this->_logFile);
				} else {
					Mage::log(PHP_EOL."Cannot delete Option ".$option[$option['attribute_type'].'_value']." Id: ".$option['duplicate_id'].' there are products attatched'.PHP_EOL, null, $option['attribute_type'].$this->_logFile);
				}
				Mage::log(PHP_EOL."Products: ".implode($products->getAllIds(), ",").PHP_EOL, null, $option['attribute_type'].$this->_logFile);	
			}
		}
	}
}

$colorId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product', 'color');
$sizeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product', 'size');

$attributes = 
			array( 
				$colorId => 'color', 
				$sizeId => 'size'
			);
$rm = new removeDuplications();

/* get colors from csv and replace them in database */
$rm->getCsvToReplaceColors();
$rm->startProcessing($attributes);