<?php

class VES_AdvancedPdfProcessor_Helper_Data extends VES_Core_Helper_Data
{
	const IS_TYPE_VARIABLE 			= 1;
	
	const DISPLAY_SUBTOTAL_INCL_TAX = 2;
	const DISPLAY_SUBTOTAL_EXCL_TAX = 1;
	const DISPLAY_SUBTOTAL_BOTH 	= 3;
	
	const DISPLAY_SHIPPING_EXCL_TAX = 1;
	const DISPLAY_SHIPPING_INCL_TAX = 2;
	const DISPLAY_SHIPPING_BOTH 	= 3;
	
	const DISPLAY_PRICE_EXCL_TAX 	= 1;
	const DISPLAY_PRICE_INCL_TAX 	= 2;
	const DISPLAY_PRICE_BOTH 		= 3;
	
	const DISPLAY_GRANDTOTAL 		= 1;
	
	/**
     * Gets the detailed PDF Pro version information
     *
     * @return array
     */
    public static function getVersionInfo()
    {
    	return array(
    			'major'     => '1',
    			'minor'     => '0',
    			'revision'  => '0',
    			'patch'     => '13',
    			'stability' => '',
    			'number'    => '',
    	);
    }
    
    /**
     * Gets the current PDF Pro version string
     *
     * @return string
     */
    public static function getVersion()
    {
    	$i = self::getVersionInfo();
    	return trim("{$i['major']}.{$i['minor']}.{$i['revision']}" . ($i['patch'] != '' ? ".{$i['patch']}" : "")
    	. "-{$i['stability']}{$i['number']}", '.-');
    }
    
    
    
	public function isTypeVariable() {
		return self::IS_TYPE_VARIABLE;
	}
	
	
	public function processConstruction($construction) {
		$value 		= isset($construction[2])?$construction[2]:false;
		//remove whitespace
		$value		= trim($value);
		$data1		= explode(' ', $value);
		foreach($data1 as $_element) {
			$data2 = explode('=', $_element);
			$data2[1] = trim($data2[1],'"');
			$result[$data2[0]] = $data2[1];
		}
		$column = $result['column'];
		$column = unserialize(base64_decode($column));
		
		//sort by sort order in column
		usort($column, "vesUsortHandle");
		
		$result['column'] = $column;
		return $result;
	}
	
	public function getTaxDisplayConfig() {
		$data = Mage::getStoreConfig('tax/sales_display');
		$vesHelper = Mage::helper('ves_core');
		$result = array();
		//for shipping
		if ($data['shipping'] == self::DISPLAY_SHIPPING_EXCL_TAX) {$result['display_shipping_excl_tax'] = true;}
		else if ($data['shipping'] == self::DISPLAY_SHIPPING_INCL_TAX) {$result['display_shipping_incl_tax'] = true;}
		else if ($data['shipping'] == self::DISPLAY_SHIPPING_BOTH) {$result['display_shipping_both'] = true;}
		
		//for subtotal
		if ($data['subtotal'] == self::DISPLAY_SUBTOTAL_EXCL_TAX) {$result['display_subtotal_excl_tax'] = true;}
		else if ($data['subtotal'] == self::DISPLAY_SUBTOTAL_INCL_TAX) {$result['display_subtotal_incl_tax'] = true;}
		else if ($data['subtotal'] == self::DISPLAY_SHIPPING_BOTH) {$result['display_subtotal_both'] = true;}
		
		//for grandtotal
		if ($data['grandtotal'] == self::DISPLAY_GRANDTOTAL) {$result['display_tax_in_grandtotal'] = true;}
		return $result;
	}
	
	/**
	 * get custom images file name for widget
	 * @return string
	 */
	public function getCustomImageFileName() {
		return 'widget.png';
	}
	
	/**
	 * Return Custom Widget placeholders images URL
	 * @return string
	 */
	
	public function getCustomPlaceholderImagesBaseUrl()
	{
		return Mage::getDesign()->getSkinUrl('ves_advancedpdfprocessor/images/');
	}
	
	/**
	 * Return Custom Widget placeholders images dir
	 *
	 * @return string
	 */
	public function getCustomPlaceholderImagesBaseDir()
	{
		return Mage::getDesign()->getSkinBaseDir(array('_theme'=>'default')) . DS . 'ves_advancedpdfprocessor' . DS . 'images';
	}
	
	public function getCustomAvailablePlaceholderFilenames()
	{
		$result = array();
		$vesCoreHelper = Mage::helper('ves_core');
		$targetDir = $this->getCustomPlaceholderImagesBaseDir();
		if (is_dir($targetDir) && is_readable($targetDir)) {
			$collection = new Varien_Data_Collection_Filesystem();
			$collection->addTargetDir($targetDir)
			->setCollectDirs(false)
			->setCollectFiles(true)
			->setCollectRecursively(false);
			foreach ($collection as $file) {
				$result[] = $file->getBasename();
			}
		}
	
		return $result;
	}
}
function vesUsortHandle($a, $b) {
	return $a['sortorder'] - $b['sortorder'];
}