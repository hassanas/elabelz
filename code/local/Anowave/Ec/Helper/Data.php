<?php
/**
 * Anowave Google Tag Manager Enhanced Ecommerce (UA) Tracking
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Anowave license that is
 * available through the world-wide-web at this URL:
 * http://www.anowave.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category 	Anowave
 * @package 	Anowave_Ec
 * @copyright 	Copyright (c) 2017 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */

class Anowave_Ec_Helper_Data extends Anowave_Package_Helper_Data
{
	const DEFAULT_CUSTOM_OPTION_FIELD = 'sku';
	
	/**
	 * Package Stock Keeping Unit
	 * 
	 * @var string
	 */
	protected $package = 'MAGE-GTM';
	
	/**
	 * License config key 
	 * 
	 * @var string
	 */
	protected $config = 'ec/config/license';
	
	/**
	 * Orders
	 * 
	 * @var mixed
	 */
	protected $orders = null;
	
	/**
	 * Check if Facebook Pixel Tracking is enabled
	 * 
	 * @return boolean
	 */
	public function facebook()
	{
		return (bool) Mage::getStoreConfig('ec/facebook/enable');
	}
	
	/**
	 * Get visitor
	 * 
	 * @return number
	 */
	public function getVisitorId()
	{
		if (Mage::getSingleton("customer/session")->isLoggedIn())
		{
			return (int) Mage::getSingleton("customer/session")->getCustomerId();
		}
		
		return 0;
	}
	
	/**
	 * Check if module is active
	 */
	public function isActive()
	{
		return $this->filter((int) Mage::getStoreConfig('ec/config/active'));
	}
	
	/**
	 * Get visitor login state 
	 * 
	 * @return string
	 */
	public function getVisitorLoginState()
	{
		return Mage::getSingleton("customer/session")->isLoggedIn() ? 'Logged in':'Logged out';
	}
	
	/**
	 * Get visitor type
	 * 
	 * @return string
	 */
	public function getVisitorType()
	{
		return (string) Mage::getModel('customer/group')->load(Mage::getSingleton("customer/session")->getCustomerGroupId())->getCode();
	}
	
	/**
	 * Get visitor lifetime value
	 * 
	 * @return float
	 */
	public function getVisitorLifetimeValue()
	{
		$value = 0;
		
		foreach ($this->getOrders() as $order) 
		{
			$value += $order->getGrandTotal();
		}
		
		if (Mage::getSingleton("customer/session")->isLoggedIn()) 
		{
			return round($value,2);
		} 
		
		return 0;
	}
	
	/**
	 * Retrieve visitor's avarage purchase amount
	 * 
	 * @return float
	 */
	public function getVisitorAvgTransValue()
	{
		$value = 0;
		$count = 0;
	
		foreach ($this->getOrders() as $order)
		{
			$value += $order->getGrandTotal();
				
			$count++;
		}
		
		if ($value && $count)
		{
			return round($value/$count,2);
		}
		
		return 0;
	}
	
	/**
	 * Get visitor existing customer
	 * 
	 * @return string
	 */
	public function getVisitorExistingCustomer()
	{
		return $this->getVisitorLifetimeValue() ? 'Yes' : 'No';
	}
	
	/**
	 * Get standard custom dimensions
	 * 
	 * @param void
	 * @return string JSON
	 */
	public function getCustomDimensions()
	{
		$dimensions = array
		(
			'pageType' => $this->getPageType()
		);
		
		/**
		 * Array of callbacks adding dimensions
		 */
		foreach (array
		(
			function ($dimensions)
			{
				$dimensions['pageName'] = Mage::helper('ec')->getSanitized
				(
					Mage::app()->getLayout()->getBlock('head')->getTitle()
				);
				
				return $dimensions;
			},
			function ($dimensions)
			{
				if(Mage::app()->getRequest()->getControllerName() == 'result' || Mage::app()->getRequest()->getControllerName() == 'advanced')
				{
					if (Mage::app()->getLayout()->getBlock('search_result_list'))
					{
						$dimensions['resultsCount'] = Mage::app()->getLayout()->getBlock('search_result_list')->getLoadedProductCollection()->getSize();
					}
				}
				
				return $dimensions;
			},
			function ($dimensions)
			{
				/**
				 * Check if category page
				 */
				if('catalog' == Mage::app()->getRequest()->getModuleName() && 'category' == Mage::app()->getRequest()->getControllerName())
				{
					/**
					 * Get applied layer filter(s)
					 */
					$filters = array();
					
					foreach ((array) Mage::getSingleton('catalog/layer')->getState()->getFilters() as $filter)
					{
						$filters[] = array
						(
							'label' => Mage::helper('ec')->getSanitized($filter->getName()),
							'value' => Mage::helper('ec')->getSanitized($filter->getLabel())
						);
					}
					
					$dimensions['filters'] = $filters;
					
					/**
					 * Count visible products
					 */
					if (Mage::app()->getLayout()->getBlock('product_list') && $filters)
					{
						$dimensions['resultsCount'] = Mage::helper('ec/datalayer')->getLoadedProductCollection()->getSize();
					}
				}
				
				return $dimensions;	
			}, 
			function ($dimensions)
			{
				if (Mage::getSingleton("customer/session")->isLoggedIn())
				{
					$dimensions['avgTransVal'] = Mage::helper('ec')->getVisitorAvgTransValue();
				}
				
				return $dimensions;
			}
		) as $dimension)
		{
			$dimensions = (array) call_user_func($dimension, $dimensions);
		}

		return json_encode($dimensions);
	}
	
	/**
	 * Get products in quote
	 * 
	 * @return []
	 */
	public function getCheckoutProducts()
	{
		$products = array();
		
		foreach (Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems() as $item)
		{
			$args = $this->getDefaultProductIdentifiers($item);
			
			$variant = array();
				
			/**
			 * Handle configurable products
			 */
			if ($item->getProduct()->isConfigurable())
			{
				$parent = Mage::getModel('catalog/product')->load
				(
					$item->getProductId()
				);
			
				/**
				 * Swap configurable data
				 * 
				 * @var stdClass
				 */
				$args = $this->getConfigurableProductIdentifiers($args, $parent);
				
				if ($item instanceof Mage_Sales_Model_Quote_Item)
				{
					$request = new Varien_Object(unserialize($item->getOptionByCode('info_buyRequest')->getValue()));
				}
				else if ($item instanceof Mage_Sales_Model_Order_Item)
				{
					$request = new Varien_Object($item->getProductOptions());
				}
			
				$options = $request->getData();
			
				if (isset($options['super_attribute']) && is_array($options['super_attribute']))
				{
					foreach ($options['super_attribute'] as $id => $option)
					{
						$attribute = Mage::getModel('catalog/resource_eav_attribute')->load($id);
			
						if ($attribute->usesSource())
						{
							$variant[] = join(':', array
							(
								$this->jsQuoteEscape($attribute->getFrontendLabel()),
								$this->jsQuoteEscape($attribute->getSource()->getOptionText($option))
							));
						}
					}
				}
			}
				
			/**
			 * Handle products with custom options
			 */
			if (1 === (int) $item->getProduct()->getHasOptions())
			{
				if ($item instanceof Mage_Sales_Model_Quote_Item)
				{
					$request = new Varien_Object(unserialize($item->getOptionByCode('info_buyRequest')->getValue()));
				}
				else if ($item instanceof Mage_Sales_Model_Order_Item)
				{
					$request = new Varien_Object($item->getProductOptions());
				}
			
				if ((int) $request->getProduct() > 0)
				{
					$parent = Mage::getModel('catalog/product')->load
					(
						$request->getProduct()
					);
						
					if ($this->useConfigurableParent())
					{
						$args->id 	= $parent->getSku();					
						$args->name = $parent->getName();
					}
						
					/**
					 * Get field to use for variants
					 *
					 * @var string
					*/
					$field = Mage::helper('ec')->getOptionUseField();
						
					foreach ($parent->getProductOptionsCollection() as $option)
					{
						$data = $parent->getOptionById($option['option_id']);
			
						switch($data->getType())
						{
							case 'drop_down':
								foreach ($data->getValues() as $value)
								{
									$options[] = array
									(
										'id' 	=> $value->getOptionTypeId(),
										'value' => $value->getData($field),
										'title' => $data->getTitle()
									);
										
								}
								break;
							case 'field':
								$options[] = array
								(
									'value' => (string) $data->getData($field)
								);
								break;
						}
					}
						
					if ($request->getOptions() && is_array($request->getOptions()))
					{
						foreach ($options as $option)
						{
							foreach ($request->getOptions() as $current)
							{
								if (is_array($option) && isset($option['id']) && (int) $current === (int) $option['id'])
								{
									$variant[] = join(':',array
									(
										$this->jsQuoteEscape($option['title']),
										$this->jsQuoteEscape($option['value'])
									));
								}
							}
						}
					}
				}
			}
			
			$category = $this->getCategory
			(
				Mage::helper('ec/session')->getTrace()->get($item->getProduct())
			);
			
			$data = (object) array
			(
				'id' 		=> $this->jsQuoteEscape($args->id),
				'name' 		=> $this->jsQuoteEscape($args->name),
				'category' 	=> $this->jsQuoteEscape($category),
				'brand' 	=> $this->jsQuoteEscape($this->getBrandBySku($args->id)),
				'price' 	=> Mage::helper('ec/price')->getPrice($item->getProduct()),
				'quantity' 	=> $item->getQty(),
				'variant' 	=> join('-', $variant),
				'coupon'	=> ''
			);
			
			$products[] = $data;
		}
		
		return $products;
	}
	
	/**
	 * Prevent XSS attacks 
	 * 
	 * @param string $content
	 */
	public function getSanitized($content)
	{
		return strip_tags($content);
	}

	/**
	 * Determine page type
	 * 
	 * @return string
	 */
	public function getPageType()
	{
		if (Mage::getBlockSingleton('page/html_header')->getIsHomePage())
		{
			return 'home';
		}
		else if('catalog' == Mage::app()->getRequest()->getModuleName() && 'category' == Mage::app()->getRequest()->getControllerName())
		{
			return 'category';
		}
		else if ('catalog' == Mage::app()->getRequest()->getModuleName() && 'product' == Mage::app()->getRequest()->getControllerName())
		{
			return 'product';
		}
		else if('checkout' == Mage::app()->getRequest()->getModuleName() && 'cart' == Mage::app()->getRequest()->getControllerName() && 'index' == Mage::app()->getRequest()->getActionName())
		{
			return 'cart';
		}
		else if('checkout' == Mage::app()->getRequest()->getModuleName() && 'onepage' == Mage::app()->getRequest()->getControllerName() && 'index' == Mage::app()->getRequest()->getActionName())
		{
			return 'checkout';
		}
		else if(Mage::app()->getRequest()->getControllerName() == 'result' || Mage::app()->getRequest()->getControllerName() == 'advanced')
		{
			return 'searchresults';
		}
		else 
		{
			return 'other';
		}
	}
	
	/**
	 * Load customer orders
	 */
	protected function getOrders()
	{
		if (!$this->orders)
		{
			$this->orders = Mage::getResourceModel('sales/order_collection')->addFieldToSelect('*')->addFieldToFilter('customer_id',Mage::getSingleton("customer/session")->getId());
		}

		return $this->orders;
	}
	
	/**
	 * Check if GTM snippet is located after <body> opening tag
	 * 
	 * @return boolean
	 */
	public function isAfterBody()
	{
		return true;
	}
	
	/**
	 * Check if GTM install snippet is located before </body> closing tag
	 * 
	 * @return boolean
	 */
	public function isBeforeBodyClose()
	{
		return false;
	}
	
	/**
	 * Check if GTM install snippet is located inside <head></head> tag
	 *
	 * @return boolean
	 */
	public function isInsideHead()
	{
		return Anowave_Ec_Model_System_Config_Position::GTM_LOCATION_HEAD == (int) Mage::getStoreConfig('ec/config/code_position');
	}
	
	/**
	 * Escape string for JSON 
	 * 
	 * @see Mage_Core_Helper_Abstract::jsQuoteEscape()
	 */
	public function jsQuoteEscape($data, $quote='\'')
	{
		return Mage::helper('core')->jsQuoteEscape($data);
	}
	
	/**
	 * Escape quotes used in attribute(s) 
	 * 
	 * @param unknown $data
	 */
	public function jsQuoteEscapeDataAttribute($data)
	{
		return str_replace(array(chr(34), chr(39)),array('&quot;','&apos;'),$data);
	}
	
	/**
	 * Prepare GTM install snippet for <head> insertion
	 * 
	 * @return string
	 */
	public function getHeadSnippet()
	{
		return Mage::getStoreConfig('ec/config/code_head');
	}
	
	public function getBodySnippet()
	{
		return Mage::getStoreConfig('ec/config/code_body');
	}
	
	/**
	 * Get list name
	 * 
	 * @param Mage_Catalog_Model_Category $category
	 */
	public function getCategoryList(Mage_Catalog_Model_Category $category = null)
	{
		if(Mage::app()->getRequest()->getControllerName() == 'result' || Mage::app()->getRequest()->getControllerName() == 'advanced')
		{
			return __('Search Results');
		}
		
		if ($category)
		{
			return $category->getName();
		}
		
		return __('');
	}
	
	/**
	 * Get category name
	 * 
	 * @param Mage_Catalog_Model_Category $category
	 */
	public function getCategory(Mage_Catalog_Model_Category $category)
	{
		if (Mage::getStoreConfig('ec/preferences/use_category_segments'))
		{
			return $this->getCategorySegments($category);
		}
		else 
		{
			return trim
			(
				$category->getName()
			);
		}
	}
	
	/**
	 * Retrieve category and it's parents separated by chr(47)
	 * 
	 * @param Mage_Catalog_Model_Category $category
	 * @return string
	 */
	public function getCategorySegments(Mage_Catalog_Model_Category $category)
	{
		$segments = array();
		
		foreach ($category->getParentCategories() as $parent) 
		{
		    $segments[] = $parent->getName();
		}
		
		if (!$segments)
		{
			$segments[] = $category->getName();
		}
		
		return trim(join(chr(47), $segments));
	}
	
	/**
	 * Get product manufacturer
	 * 
	 * @param Mage_Catalog_Model_Product $product
	 */
	public function getBrand(Mage_Catalog_Model_Product $product)
	{
		$attributes = array
		(
			'manufacturer','brand'
		);
		
		foreach (array('manufacturer','brand') as $code)
		{
			$attribute = Mage::getResourceModel('catalog/eav_attribute')->loadByCode(\Mage_Catalog_Model_Product::ENTITY,$code);
			
			if ($attribute->getId() && $attribute->usesSource())
			{
				return (string) $product->getAttributeText($code);
			}
		}
		
		return '';
	}
	
	/**
	 * Load product by SKU and get its brand.
	 * 
	 * @param string $sku
	 */
	public function getBrandBySku($identifier)
	{
		if (strlen($identifier))
		{
			$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $identifier);
			
			if ($product && $product instanceof Mage_Catalog_Model_Product)
			{
				return $product->getAttributeText('manufacturer');
			}
		}
		
		return '';
	}
	
	/**
	 * Get option use field
	 * 
	 * @return string
	 */
	public function getOptionUseField()
	{
		$field = (string) Mage::getStoreConfig('ec/preferences/use_custom_option_field');
		
		if ('' === $field)
		{
			$field = self::DEFAULT_CUSTOM_OPTION_FIELD;
		}
		
		return $field;
	}

	/**
	 * Get eventTimeout config value
	 * 
	 * @return int
	 */
	public function getTimeoutValue() 
	{
		$timeout = (int) Mage::getStoreConfig('ec/blocker/eventTimeout');
		
		if (!$timeout)
		{
			$timeout = 2000;
		}
		
		return $timeout;
	}
	
	/**
	 * Check if module should send child SKU instead of configurable parent SKU
	 * 
	 * @return bool
	 */
	public function useConfigurableParent()
	{
		if (1 === (int ) Mage::getStoreConfig('ec/preferences/use_child'))
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Get default identifiers 
	 * 
	 * @param unknown $item
	 */
	public function getDefaultProductIdentifiers(\Mage_Core_Model_Abstract $item)
	{
		$args = new \stdClass();
		
		if ($item->getProduct()->isConfigurable())
		{
			$options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());

			/**
			 * Test order item
			 */
			if (!isset($options['simple_sku']) || !isset($options['simple_name']))
			{
				$options = $item->getProductOptions();
			}
			
			if (isset($options['simple_sku']) && isset($options['simple_name']))
			{
				$args->id 	= $options['simple_sku'];
				$args->name = $options['simple_name'];
			
				return $args;
			}
		}
		
		/**
		 * Default data
		 */
		$args->id 	= $item->getSku();
		$args->name = $item->getName();
		
		return $args;
	}
	
	/**
	 * Swap child product with it's parent name and SKU
	 * 
	 * @param \stdClass $args
	 */
	public function getConfigurableProductIdentifiers(\stdClass $args, \Mage_Catalog_Model_Product $configurable)
	{
		if ($this->useConfigurableParent())
		{
			$args->id		= $configurable->getSku();
			$args->idParent = $configurable->getSku();
			$args->name 	= $configurable->getName();
		}

		return $args;
	}
	
	/**
	 * Check for AMP support
	 */
	public function supportsAmp()
	{
		return 1 === (int) Mage::getStoreConfig('ec/amp/enable');
	}
}