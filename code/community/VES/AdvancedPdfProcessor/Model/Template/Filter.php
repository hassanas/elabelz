<?php

class VES_AdvancedPdfProcessor_Model_Template_Filter extends Mage_Core_Model_Email_Template_Filter
{
	const CONSTRUCTION_ADVANCED_IF_PATTERN 	= '/{{if \(\s*(.*?)\s*\)\s*}}(.*?)({{else}}(.*?))?{{\\/if\s*}}/si';
	const CONSTRUCTION_ADVANCED_IF1_PATTERN = '/{{if1 \(\s*(.*?)\s*\)\s*}}(.*?)({{else1}}(.*?))?{{\\/if1\s*}}/si';
	const CONSTRUCTION_ADVANCED_IF2_PATTERN = '/{{if2 \(\s*(.*?)\s*\)\s*}}(.*?)({{else2}}(.*?))?{{\\/if2\s*}}/si';
	const CONSTRUCTION_ADVANCED_IF3_PATTERN = '/{{if3 \(\s*(.*?)\s*\)\s*}}(.*?)({{else3}}(.*?))?{{\\/if3\s*}}/si';
	const CONSTRUCTION_IF1_PATTERN 			= '/{{if1 (.*?)}}(.*?)({{else1}}(.*?))?{{\\/if1\s*}}/si';
	const CONSTRUCTION_IF2_PATTERN 			= '/{{if2 (.*?)}}(.*?)({{else2}}(.*?))?{{\\/if2\s*}}/si';
	const CONSTRUCTION_IF3_PATTERN 			= '/{{if3 (.*?)}}(.*?)({{else3}}(.*?))?{{\\/if3\s*}}/si';
	const CONSTRUCTION_FOREACH_PATTERN 		= '/{{foreach\s*(.*?)\s*as\s*(.*?)\s*}}(.*?){{\\/foreach\s*}}/si';
    const CONSTRUCTION_FOREACH_KEY_PATTERN 	= '/{{foreach\s*(.*?)\s*as\s*(.*?)\s*=>\s*(.*?)\s*}}(.*?){{\\/foreach\s*}}/si';
    const CONSTRUCTION_FOREACH1_PATTERN 	= '/{{foreach1\s*(.*?)\s*as\s*(.*?)\s*}}(.*?){{\\/foreach1\s*}}/si';
    const CONSTRUCTION_FOREACH1_KEY_PATTERN = '/{{foreach1\s*(.*?)\s*as\s*(.*?)\s*=>\s*(.*?)\s*}}(.*?){{\\/foreach1\s*}}/si';
    const CONSTRUCTION_VARDUMP_PATTERN 		= '/{{var_dump\s*(.*?)\s*}}/si';
    const CONSTRUCTION_INCREMENTING_PATTERN = '/{{inc (.*?) (.*?)}}/si';
    const CONSTRUCTION_EXPRESSION_PATTERN 	= '/{{expression\s*(.*?)}}/si';
    const CONSTRUCTION_BARCODE_PATTERN 		= '/{{barcode\s*(.*?)}}/si';
    const CONSTRUCTION_QRCODE_PATTERN 		= '/{{qrcode\s*(.*?)}}/si';
    const CONSTRUCTION_SET_VALUE_PATTERN 	= '/{{set \((.*?),(.*?)\)}}/si';
    const CONSTRUCTION_WIDGET_PATTERN		= '/{{widget  (.*?)}}/si';
    
    protected $_offset = 0;
    protected function _process($construction,$value){
    	$vesHelper = Mage::helper('ves_core');
    	switch ($construction[1]){
    		case "var_dump":
    			$replacedValue = $this->vardumpDirective($construction);
    			break;
    		case "var":
    			$replacedValue = $this->varDirective($construction);
    			break;
    		case "barcode":
    			preg_match(self::CONSTRUCTION_BARCODE_PATTERN, $value, $construction);
				$replacedValue = $this->barcodeDirective($construction);
				break;
			case "qrcode":
				preg_match(self::CONSTRUCTION_QRCODE_PATTERN, $value, $construction);
				$replacedValue = $this->qrcodeDirective($construction);
				break;
    		case "if":
    			if(preg_match('/{{if \(\s*(.*?)\s*\)\s*}}/si', $construction[0])){
    				/* Advanced if */
    				preg_match(self::CONSTRUCTION_ADVANCED_IF_PATTERN, $value, $construction);
    				$replacedValue = $this->advancedIfDirective($construction);
    			}else{
					preg_match(self::CONSTRUCTION_IF_PATTERN, $value, $construction);
    				$replacedValue = $this->ifDirective($construction);
    			}
    			break;
    		case "if1":
    			if(preg_match('/{{if1 \(\s*(.*?)\s*\)\s*}}/si', $construction[0])){
    				/* Advanced if */
    				preg_match(self::CONSTRUCTION_ADVANCED_IF1_PATTERN, $value, $construction);
    				$replacedValue = $this->advancedIfDirective($construction);
    			}else{
    				preg_match(self::CONSTRUCTION_IF1_PATTERN, $value, $construction);
    				$replacedValue = $this->ifDirective($construction);
    			}
    			break;
    		case "if2":
    			if(preg_match('/{{if2 \(\s*(.*?)\s*\)\s*}}/si', $construction[0])){
    				/* Advanced if */
    				preg_match(self::CONSTRUCTION_ADVANCED_IF2_PATTERN, $value, $construction);
    				$replacedValue = $this->advancedIfDirective($construction);
    			}else{
					preg_match(self::CONSTRUCTION_IF2_PATTERN, $value, $construction);
    				$replacedValue = $this->ifDirective($construction);
    			}
    			break;
    		case "if3":
    			if(preg_match('/{{if3 \(\s*(.*?)\s*\)\s*}}/si', $construction[0])){
    				/* Advanced if */
    				preg_match(self::CONSTRUCTION_ADVANCED_IF3_PATTERN, $value, $construction);
    				$replacedValue = $this->advancedIfDirective($construction);
    			}else{
					preg_match(self::CONSTRUCTION_IF3_PATTERN, $value, $construction);
    				$replacedValue = $this->ifDirective($construction);
    			}
    			break;
    		case "foreach":
    			if(preg_match('/{{foreach\s*(.*?)\s*as\s*(.*?)\s*}}/si', $construction[0])){
    				preg_match(self::CONSTRUCTION_FOREACH_PATTERN, $value, $construction);
    				$replacedValue = $this->foreachDirective($construction);
    			}else{
    				preg_match(self::CONSTRUCTION_FOREACH_KEY_PATTERN, $value, $construction);
    				$replacedValue = $this->foreachDirective($construction);
    			}
    			break;
			case "foreach1":
    			if(preg_match('/{{foreach1\s*(.*?)\s*as\s*(.*?)\s*}}/si', $construction[0])){
    				preg_match(self::CONSTRUCTION_FOREACH1_PATTERN, $value, $construction);
    				$replacedValue = $this->foreachDirective($construction);
    			}else{
    				preg_match(self::CONSTRUCTION_FOREACH1_KEY_PATTERN, $value, $construction);
    				$replacedValue = $this->foreachDirective($construction);
    			}
    			break;
			case "inc":
				preg_match(self::CONSTRUCTION_INCREMENTING_PATTERN, $value, $construction);
				$replacedValue = $this->incDirective($construction);
				break;
			case "set":
				preg_match(self::CONSTRUCTION_SET_VALUE_PATTERN, $value, $construction);
				$replacedValue = $this->setDirective($construction);
				break;
			case "widget":
				preg_match(self::CONSTRUCTION_WIDGET_PATTERN, $value, $construction);
				$replacedValue = $this->widgetDirective($construction);
				break;
    	}
    	$value = str_replace($construction[0], $replacedValue, $value);
    	return $value;
    }    
    public function advancedFilter($value){
    	$this->_offset = 0;
    	$i = 0;
    	$vesCoreHelper = Mage::helper('ves_core');
    	while(true){
    		if($i++ > 10) break;
	    	if(preg_match('/{{(.*?) (.*?)\s*}}/si', $value, $construction)){
	    		$value = $this->_process($construction,$value);
	    	}else{
	    		
	    	}
    	}
    	return $value;
    }
    
	public function filter($value){
    	// "foreach" operand should be first
        foreach (array(
			self::CONSTRUCTION_FOREACH_KEY_PATTERN     	=> 'foreachDirectiveWithKey',
            self::CONSTRUCTION_FOREACH_PATTERN     		=> 'foreachDirective',
            self::CONSTRUCTION_FOREACH1_KEY_PATTERN     => 'foreachDirectiveWithKey',
            self::CONSTRUCTION_FOREACH1_PATTERN     	=> 'foreachDirective',
            self::CONSTRUCTION_WIDGET_PATTERN			=> 'widgetDirective',
            self::CONSTRUCTION_INCREMENTING_PATTERN		=> 'incrementDirective',
			self::CONSTRUCTION_SET_VALUE_PATTERN		=> 'setDirective',
            self::CONSTRUCTION_ADVANCED_IF_PATTERN		=> 'advancedIfDirective',
            self::CONSTRUCTION_ADVANCED_IF1_PATTERN		=> 'advancedIfDirective',
            self::CONSTRUCTION_ADVANCED_IF2_PATTERN		=> 'advancedIfDirective',
            self::CONSTRUCTION_IF1_PATTERN				=> 'ifDirective',
            self::CONSTRUCTION_IF2_PATTERN				=> 'ifDirective',
            self::CONSTRUCTION_IF3_PATTERN				=> 'ifDirective',
            self::CONSTRUCTION_VARDUMP_PATTERN			=> 'vardumpDirective',
			self::CONSTRUCTION_BARCODE_PATTERN			=> 'barcodeDirective',
			self::CONSTRUCTION_QRCODE_PATTERN			=> 'qrcodeDirective',
            ) as $pattern => $directive) {
            if (preg_match_all($pattern, $value, $constructions, PREG_SET_ORDER)) {
                foreach($constructions as $index => $construction) {
                    $replacedValue = '';
                    $callback = array($this, $directive);
                    if(!is_callable($callback)) {
                        continue;
                    }
                    try {
                        $replacedValue = call_user_func($callback, $construction);
                    } catch (Exception $e) {
                        throw $e;
                    }
                    $value = str_replace($construction[0], $replacedValue, $value);
                }
            }
        }
        $vesHelper = Mage::helper('ves_core');
        return parent::filter($value);
    }
	public function ifDirective($construction)
    {
    	$vesHelper = Mage::helper('ves_core');
        if (count($this->_templateVars) == 0) {
            return $construction[0];
        }

        if(!$this->_getVariable($construction[1], '')) {
            if (isset($construction[3]) && isset($construction[4])) {
                return $this->advancedFilter($construction[4]);
            }
            return '';
        } else {
            return $this->advancedFilter($construction[2]);
        }
    }
    public function foreachDirective($construction){
    	if (count($this->_templateVars) == 0) {
            return $construction[0];
        }
        $vesCoreHelper = Mage::helper('ves_core');
		$arr = $this->_getVariable($construction[1], '');
		$replacedValue = '';
        if(is_array($arr)){
        	$templateVar = $this->_templateVars;
        	foreach($arr as $value){
        		$this->_templateVars[$construction[2]] = $value;
        		$replacedValue .= $this->advancedFilter($construction[3]);
        	}
        	//unset($this->_templateVars[$construction[2]]);
        	return $replacedValue;
        }else{
        	return $construction[0];
        }
    }
    
	public function foreachDirectiveWithKey($construction){
    	if (count($this->_templateVars) == 0) {
            return $construction[0];
        }
        $vesHelper = Mage::helper('ves_core');
		$arr = $this->_getVariable($construction[1], '');
		$replacedValue = '';
        if(is_array($arr)){
        	$templateVar = $this->_templateVars;
        	foreach($arr as $key=>$value){
        		$this->_templateVars[$construction[2]] = $key;
        		$this->_templateVars[$construction[3]] = $value;
        		$replacedValue .= $this->advancedFilter($construction[4]);
        	}
        	//unset($this->_templateVars[$construction[2]]);
        	//unset($this->_templateVars[$construction[3]]);
        	return $replacedValue;
        }else{
        	return $construction[0];
        }
    }
    
    
    public function advancedIfDirective($construction){
    	$operators = array('==', '===', '!=', '<>', '!==', '>', '<', '>=', '<=','+','-','*','/','%');
    	$condition = str_replace(" ", "", $construction[1]);
    	$usedOperator = false;
    	foreach($operators as $operator){
    		if(strpos($condition, $operator) !==FALSE){
    			$condition		= explode($operator, $condition);
    			$usedOperator	= $operator;
    			break;
    		}
    	}
    	$vesHelper = Mage::helper('ves_core');
    	if(!$usedOperator || (sizeof($condition) != 2)) return $construction[0];
    	
    	foreach($condition as $key=>$cond){
    		if(!is_numeric($cond)){
				$value = $this->filter("{{var $cond}}");
				$condition[$key]	= $value?$value:0;
			}
    	}
    	if(Mage::getModel('advancedpdfprocessor/math')->compare($condition[0],$condition[1],$usedOperator)) return $construction[2];
    	else return isset($construction[4])?$construction[4]:'';
    }
    
    public function vardumpDirective($construction){
    	if (count($this->_templateVars) == 0) {
            return $construction[0];
        }
        $vesCoreHelper = Mage::helper('ves_core');
        $variable = $this->_getVariable($construction[1])?$this->_getVariable($construction[1]):null;
        return var_export($variable,true);
    }
    
    public function incDirective($construction){
    	if (count($this->_templateVars) == 0) {
            return $construction[0];
        }
        $vesHelper = Mage::helper('ves_core');
        $increment = $construction[2];
        if(!is_numeric($increment)){
        	if(!isset($this->_templateVars[$increment])) return $construction[0];
        	$increment = $this->_templateVars[$increment];
        }
        $this->_templateVars[$construction[1]] = isset($this->_templateVars[$construction[1]])?($this->_templateVars[$construction[1]]+=$increment):$increment;
    }
    
	public function expressionDirective($construction){
		$expression = str_replace(" ", '', $construction[1]);
		/*remove all operators*/
		$variables = preg_split('/[-,+,*,\/,%,.,),(,=,+=,-=,*=,\/=,%=,.=,++,--,==,===,!=,<>,!==,>,<,>=,<=,&&,||,!]+/', $expression);
		foreach($variables as $key=>$value){
			if(!$value) unset($variables[$key]);
		}
		$vesHelper = Mage::helper('ves_core');
		foreach($variables as $variable){
			if(!is_numeric($variable)){
				$expression = str_replace($variable, "\$this->_templateVars[$variable]", $expression);
			}
			try{
				eval($expression.";");
			}catch (Exception $e){
				return $construction[0];
			}
		}
		/* replace the variable by its value */
	}
	public function setDirective($construction){
		$variable 	= isset($construction[1])?$construction[1]:false;
		$value 		= isset($construction[2])?$construction[2]:false;
		$vesHelper = Mage::helper('ves_core');
		if(!$variable || !$value) return $construction[0];
		$this->_templateVars[$variable] = $value;
	}
	
	public function barcodeDirective($construction){
		if (count($this->_templateVars) == 0) {
            return $construction[0];
        }
        $vesHelper = Mage::helper('ves_core');
        $barcodeConfig = Mage::getStoreConfig('pdfpro/barcode');
        $fileType 	= 'PNG'; /* PNG, JPG, GIF */
        $dpi		= $barcodeConfig['dpi'];
        $scale		= $barcodeConfig['scale'];
        $rotation	= $barcodeConfig['rotation'];
        $fontFamily	= $barcodeConfig['font_family'];
        $fontSize	= $barcodeConfig['font_size'];
        $thickness	= $barcodeConfig['thickness'];
        $checksum	= $barcodeConfig['checksum'];
        $code		= $barcodeConfig['symbology'];
        
        $variable = $this->_getVariable($construction[1],'')?$this->_getVariable($construction[1],''):null;
		$variable = urlencode($variable);
		
        $src		= str_replace('index.php/', '', Mage::getBaseUrl('web')).'barcode/image.php?filetype='.$fileType.'&dpi='.$dpi.'&scale='.$scale.'&rotation='.$rotation.'&font_family='.$fontFamily.'&font_size='.$fontSize.'&text='.$variable.'&thickness='.$thickness.'&checksum='.$checksum.'&code='.$code;
		$src		= base64_encode($this->getContent($src));
        return '<img src="data:image/png;base64,'.$src.'" />';
	}
	
	
	public function qrcodeDirective($construction){
		if (count($this->_templateVars) == 0) {
            return $construction[0];
        }
        $vesCoreHelper = Mage::helper('ves_core');
        $config = $this->_templateVars['config'];
        $qrConfig = Mage::getStoreConfig('pdfpro/qrcode');
        $size		= $qrConfig['size'];
        $level		= $qrConfig['level'];
        $variable = $this->_getVariable($construction[1],'')?$this->_getVariable($construction[1],''):null;
		$variable = urlencode($variable);
		
        $src		= trim(str_replace('index.php/', '', Mage::getBaseUrl('web')),'/').'/qrcode/qrcode.php?data='.urldecode($variable).'&size='.$size.'&level='.$level;
		$src		= base64_encode($this->getContent($src));
        return '<img src="data:image/png;base64,'.$src.'" />';
	}
	public function getContent($url){
		$agent= 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:19.0) Gecko/20100101 Firefox/19.0';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $agent);
		curl_setopt($ch, CURLOPT_URL,$url);
		$html		= curl_exec($ch);
		curl_close($ch);
		return $html;
	}
	
	public function widgetDirective($construction){
		$variable 	= isset($construction[1])?$construction[1]:false;
		$value 		= isset($construction[2])?Mage::helper('advancedpdfprocessor')->processConstruction($construction):false;
		
		$vesCoreHelper = Mage::helper('ves_core');
		$type 	= $this->_getVariable('type');				//type of invoice: order , invoice or shipment, creditmemo
		$obj 	= $this->_getVariable($type);				//get data of type
		
		$totals = $obj->getTotals();						//get totals body and total footer of invoice
		
		$column_setting 	= $value['column'];					//column settting
		$items 				= $obj->getItems();					//items data for invoice
		$type 				= $value['type'];
		
		foreach($column_setting as $_id=>$_column) {
			if(!isset($column['option_choose']) or $_column['option_choose'] == NULL) $_column['option_choose'] = VES_AdvancedPdfProcessor_Model_Source_Widget_Optiontype::OPTION_TEXT;
			if(!isset($column['option_width']) or $_column['option_width'] == NULL) $_column['option_width'] = '';
			if(!isset($column['option_height']) or $_column['option_height'] == NULL) $_column['option_height'] = '';
			if(!isset($column['custom']) or $_column['custom'] == NULL) $_column['custom'] = '';
			
			$column_setting[$_id] = $_column;
		}
		
		$block = new VES_AdvancedPdfProcessor_Block_Filter_Widget();
		$block->setData(array('object'=>$obj, 'value' => $value,'type'=>$type, 'column'=>$column_setting, 'items'=>$items, 'totals'=>$totals))->setArea('adminhtml')->setIsSecureMode(true);//->setTemplate('ves_advancedpdfprocessor/filter/widget.phtml');
		if(Mage::getStoreConfig('pdfpro/config/processor') == 'advancedpdfprocessor/processor') {
			$block->setTemplate('ves_advancedpdfprocessor/filter/widget_dom.phtml');
		}
		else if(Mage::getStoreConfig('pdfpro/config/processor') == 'advancedpdfprocessor/mpdf')
		{
			$block->setTemplate('ves_advancedpdfprocessor/filter/widget.phtml');
		}
		return $block->toHtml();
		//exit;
	}
}