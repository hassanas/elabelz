<?php
class Progos_DuplicateOptions_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getOptionReplaceFile() 
	{	
		$filePath = Mage::getBaseDir('media').DIRECTORY_SEPARATOR.'admin-config-uploads'.DIRECTORY_SEPARATOR.
		Mage::getStoreConfig('duplicateoptions/duplicateoptions/upload_file');

		$filePath = str_replace("//", "/", $filePath);

		/* To check if file exists and is not empty */
		$FileContents = file_get_contents($filePath);
		
		/* To check if file type is csv and valid comma seperated file*/
		$delimeter = $this->getFileDelimiter($filePath);

		if (strlen($FileContents) && $delimeter == ",") {
		  	return $filePath;
		} else {
			return false;
		}
	}

	public function getOptionsfromConfig() 
	{
		$attributes = Mage::getStoreConfig('duplicateoptions/duplicateoptions/attribute_codes');

		if($attributes) {
			$attributes = explode(",", $attributes);
		}

		if(count($attributes)) {
			$allAttributes = array();

			foreach ($attributes as $key => $value) {
			 	$attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product', $value);
			 	$allAttributes[$attributeId] = $value;
			}
		}
		
		if(count($allAttributes)) {
			return $allAttributes;
		} else {
			return false;
		}
	}

	public function getDuplicateOptionsLogs()
	{
		$attributes = $this->getOptionsfromConfig();

		$logList = array();

		foreach ($attributes as $key => $value) {
			$logList[] = $value."_duplicate_attribute";
			$logList[] = $value."_Dryrun__duplicate_attribute";	
		}

		$logList[] = "csv_duplicate_colors__Dryrun__duplicate_attribute";

		foreach ($logList as $key => $log) {
	        $path = glob(Mage::getBaseDir('var').'/log/'.$log.'*');
	        if(count($path)) {
	            $p = str_replace(Mage::getBaseDir(), Mage::getBaseUrl(), $path[0]);

	            $logs[] = str_replace("//var", "/var", $p);
	        }	        
	    }

		return $logs;
	}

	public function getFileDelimiter($file, $checkLines = 5){
        $file = new SplFileObject($file);
        $delimiters = array(
          ',',
          '\t',
          ';',
          '|',
          ':'
        );
        $results = array();
        $i = 0;
         while($file->valid() && $i <= $checkLines){
            $line = $file->fgets();
            foreach ($delimiters as $delimiter){
                $regExp = '/['.$delimiter.']/';
                $fields = preg_split($regExp, $line);
                if(count($fields) > 1){
                    if(!empty($results[$delimiter])){
                        $results[$delimiter]++;
                    } else {
                        $results[$delimiter] = 1;
                    }   
                }
            }
           $i++;
        }
        $results = array_keys($results, max($results));
        return $results[0];
    }

}
	 