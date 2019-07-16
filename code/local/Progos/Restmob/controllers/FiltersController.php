<?php
class Progos_Restmob_FiltersController extends Mage_Core_Controller_Front_Action{
	public function getAttribute($attributeCode) {
		$attributeId = Mage::getResourceModel('eav/entity_attribute_collection')
		->setCodeFilter($attributeCode)->getFirstItem()->getAttributeId();
		
		$attributeOptions = Mage::getResourceModel('eav/entity_attribute_option_collection')
		->setAttributeFilter($attributeId)
		->setStoreFilter(0)
		->setPositionOrder()
		->load()
		->toOptionArray();
		
		return $attributeOptions;

		$attrs = array();
		foreach ($attributeOptions AS $attributeOption) {
			$attrs[] = array('code'=>$attributeOption['value'],'label'=>$attributeOption['label']);
		}
		
		header("Content-Type: application/json");
		print_r(json_encode($attrs));
		die;
	}

	public function layerednavAction(){
		if(isset($_GET["s"]) && !empty($this->getRequest()->getParam('s'))){
			//hassan: Search Filters from klevu start
			$page = ($this->getRequest()->getParam('page')) ? (integer)$this->getRequest()->getParam('page') : 1;
			$limit = ($this->getRequest()->getParam('limit')) ? (integer)$this->getRequest()->getParam('limit') : 20;
			$search = $this->getRequest()->getParam('s');
			$filters = array();
			if(!empty($this->getRequest()->getParam('cid'))) $filters['category'] = $this->getRequest()->getParam('cid');// $categoryid is actually string
			if(!empty($this->getRequest()->getParam('manufacturer'))) $filters['manufacturer'] = $this->getRequest()->getParam('manufacturer');
			if(!empty($this->getRequest()->getParam('size'))) $filters['size'] = $this->getRequest()->getParam('size');
			if(!empty($this->getRequest()->getParam('color'))) $filters['color'] = $this->getRequest()->getParam('color');
			if(!empty($this->getRequest()->getParam('price'))) $filters['klevu_price'] = $this->getRequest()->getParam('price');
			$sort = $this->getRequest()->getParam('sort');
			if($sort==3)
				$sort = 'lth';
			elseif($sort==4)
				$sort = 'htl';
			else
				$sort = 'rel';

			$page = ($page - 1) * $limit;
			$args = ['term'=>$search,'page'=>$page,'limit'=>$limit,'sort'=>$sort];
			if(!empty($filters)) $args['filters'] = $filters;
			$data =  $this->_getHelper('klevusearch')->filterKlevuLayeredNavKeysAsPerRestMobStructure(Mage::getModel('klevusearch/productsearchfilters')->getFilters($args));

			header("Content-Type: application/json");
			echo json_encode($data);exit;

			//hassan: Search Filters from klevu End
		}

		$fpcModel = Mage::getModel('fpccache/fpc');
		$fpcModel->setControllerObject($this);
		$cacheData = $fpcModel->getData();
		if(!empty($cacheData))
		{
			header("Content-Type: application/json");
			echo $cacheData;
			die;
		}
		$categoryid = (int)$this->getRequest()->getParam('cid');
		if($categoryid == "" || $categoryid == null){
			$categoryid = Mage::app()->getStore()->getRootCategoryId();
		}
		$designid = $this->getRequest()->getParam('manufacturer');
		$sizeid = $this->getRequest()->getParam('size');
		$colorid = $this->getRequest()->getParam('color');
		$pricerange = $this->getRequest()->getParam('price');
        if (Mage::getStoreConfig('api/emapi/filterLogs')) {
            Mage::log('filter design = ' . $designid, Null, 'filters_debug.log');
            Mage::log('filter size = ' . $sizeid, Null, 'filters_debug.log');
            Mage::log('filter color = ' . $colorid, Null, 'filters_debug.log');
        }
		$attrs["design"]['label'] = __("Brands");
		$storeId = Mage::app()->getStore()->getId();
		$attributeCode = Mage::getStoreConfig('shopbybrand/general/attribute_code', $storeId);        
		$attrCode = $attributeCode ? $attributeCode : 'manufacturer';
		$attrs["design"]['code'] = $attrCode;
		$attrs["design"]['sort'] = 1;
		$attrs["design"]['options'] = array();
		
		$attrs["color"]['label'] = __("Color");
		$attrs["color"]['code'] = __("color");
		$attrs["color"]['sort'] = 2;
		$attrs["color"]['options'] = array();
		
		$attrs["size"]['label'] = __("Size");
		$attrs["size"]['code'] = __("size");
		$attrs["size"]['sort'] = 3;
		$attrs["size"]['options'] = array();
		
		$attrs["price"]['label'] = __("Price");
		$attrs["price"]['code'] = __("price");
		$attrs["price"]['sort'] = 4;
		$attrs["price"]['options'] = array();
		
		
		$layer = Mage::getModel("catalog/layer");
		$layer->setCurrentCategory($categoryid);
		$attributes = $layer->getFilterableAttributes();
		
		$i=0;
		$attributeCollection = array();
		foreach ($attributes as $attribute) {
			if($attribute->getAttributeCode() == 'price') {
				$filterBlockName = 'catalog/layer_filter_price';
			}elseif($attribute->getBackendType() == 'decimal'){
				$filterBlockName = 'catalog/layer_filter_decimal';
			}else{
				$filterBlockName = 'catalog/layer_filter_attribute';
			}
			$result = Mage::app()->getLayout()->createBlock($filterBlockName)->setLayer($layer)->setAttributeModel($attribute)->init();
			$attributeCollection[$i]['Code'] = $attribute->getAttributeCode();
			$attributeCollection[$i]['Label'] = $attribute->getStoreLabel();
			$j=0;
			foreach($result->getItems() as $option) {
				$val = $option->getValue();
				if($attribute->getAttributeCode()=='price'){
					$attrs["price"]['options'][$j]['label'] = strip_tags($option->getLabel());
					$attrs["price"]['options'][$j]['count'] = $option->getCount();
					if(trim($val) == ""){
						$val = $pricerange;
					}
					$val_arr = explode(',',$val);
					$pricerange_arr = explode(',',$pricerange);
					if(strstr($pricerange,',') || strstr($val,',')){
						$val = array_merge(array_diff($val_arr, $pricerange_arr), array_diff($pricerange_arr, $val_arr));
						$val = $val[0];
					}	
					$attrs["price"]['options'][$j]['value'] = $val;
				}elseif($attribute->getAttributeCode()=='color'){
					$attrs["color"]['options'][$j]['label'] = $option->getLabel();
					$attrs["color"]['options'][$j]['count'] = $option->getCount();
					if(trim($val) == ""){
						$val = $colorid;
					}
					$val_arr = explode(',',$val);
					$colorid_arr = explode(',',$colorid);
					if(strstr($colorid,',') || strstr($val,',')){
						$val = array_merge(array_diff($val_arr, $colorid_arr), array_diff($colorid_arr, $val_arr));
						$val = $val[0];
					}
					$attrs["color"]['options'][$j]['value'] = $val;
				}elseif($attribute->getAttributeCode()=='size'){
					$attrs["size"]['options'][$j]['label'] = $option->getLabel();
					$attrs["size"]['options'][$j]['count'] = $option->getCount();
					if(trim($val) == ""){
						$val = $sizeid;
					}
					$val_arr = explode(',',$val);
					$sizeid_arr = explode(',',$sizeid);
					if(strstr($sizeid,',') || strstr($val,',')){
						$val = array_merge(array_diff($val_arr, $sizeid_arr), array_diff($sizeid_arr, $val_arr));
						$val = $val[0];
					}
					$attrs["size"]['options'][$j]['value'] = $val;
				}elseif($attribute->getAttributeCode()=='manufacturer'){
					$attrs["design"]['options'][$j]['label'] = $option->getLabel();
					$attrs["design"]['options'][$j]['count'] = $option->getCount();
					if(trim($val) == ""){
						$val = $designid;
					}
					$val_arr = explode(',',$val);
					$designid_arr = explode(',',$designid);
					if(strstr($designid,',') || strstr($val,',')){
						$val = array_merge(array_diff($val_arr, $designid_arr), array_diff($designid_arr, $val_arr));
						$val = $val[0];
					}
					$attrs["design"]['options'][$j]['value'] = $val;
				}
				$j++;
			}
			$i++;
		}
		$fpcModel->setData($attrs);
		$attrs['design']['options'] = $this->array_orderby($attrs['design']['options'], 'label', SORT_ASC);
		header("Content-Type: application/json");
		print_r(json_encode($attrs));
		die;	
	}
	protected function array_orderby()
	{
		$args = func_get_args();
		$data = array_shift($args);
		foreach ($args as $n => $field) {
			if (is_string($field)) {
				$tmp = array();
				foreach ($data as $key => $row)
					$tmp[$key] = $row[$field];
				$args[$n] = $tmp;
			}
		}
		$args[] = &$data;
		call_user_func_array('array_multisort', $args);
		return array_pop($args);
	}

	/**
	 * @return Mage_Core_Helper_Abstract
	 */
	protected function _getHelper($helper=null)
	{
		return ($helper==null) ? Mage::helper('restmob') : Mage::helper($helper);
	}
	
}