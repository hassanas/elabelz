<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Model_Preview
{
	/**
	 * Construction regular expression
	 */
	const CONSTRUCTION_PATTERN = '/{{([a-z]{0,10})(.*?)}}/si';

	/**
	 * Construction logic regular expression
	 */
	const CONSTRUCTION_DEPEND_PATTERN = '/{{depend\s*(.*?)}}(.*?){{\\/depend\s*}}/si';
	const CONSTRUCTION_IF_PATTERN = '/{{if\s*(.*?)}}(.*?)({{else}}(.*?))?{{\\/if\s*}}/si';

	/**
	 * Default email subject
	 */
	const DEFAULT_SUBJECT = 'Email Preview';

	/**
	 * @var array
	 */
	protected $_ifDirectives = array(
		self::CONSTRUCTION_DEPEND_PATTERN => 'dependDirective',
		self::CONSTRUCTION_IF_PATTERN     => 'ifDirective',
	);

	/**
	 * @var Mage_Core_Model_Email_Template_Filter
	 */
	protected $_filter;

	/**
	 * @var string
	 */
	protected $_nonInlineCss = '';

	/**
	 * @var string
	 */
	protected $_title = '';

	/**
	 * @var array
	 */
	protected $_emulation = array();

	/**
	 * Get filter object for template processing
	 *
	 * @return Mage_Core_Model_Email_Template_Filter
	 */
	public function getFilter()
	{
		if (empty($this->_filter))
		{
			$template = Mage::getModel('core/email_template');

			$this->_filter = Mage::getModel('core/email_template_filter');


			if (method_exists($this->_filter, 'setTemplateProcessor')) {
				$this->_filter->setTemplateProcessor(array($template, 'getTemplateByConfigPath'));
			}

			if (method_exists($this->_filter, 'setIncludeProcessor')) {
				$this->_filter->setIncludeProcessor(array($template, 'getInclude'));
			}

			if ($storeId = Mage::app()->getRequest()->getPost('store_id'))
			{
				$this->_filter->setStoreId($storeId);
			}
		}

		return $this->_filter;
	}

	/**
	 * Set filter variables based on POST data
	 *
	 * @param array $post
	 */
	public function setFilterVariables($post = array())
	{
		$variables = array();
		$variables['entity'] = new Varien_Object;

		if (isset($post['store_id']))
		{
			$this->getFilter()->setStoreId($post['store_id']);
			$variables['store'] = Mage::app()->getStore($post['store_id']);
			$variables['logo_url'] = $this->_getLogoUrl($post['store_id']);
			$variables['logo_alt'] = $this->_getLogoAlt($post['store_id']);

			$variables['store_phone'] = Mage::getStoreConfig(
				Mage_Core_Model_Store::XML_PATH_STORE_STORE_PHONE,
				$post['store_id']
			);

			$variables['phone'] = Mage::getStoreConfig(
				Mage_Core_Model_Store::XML_PATH_STORE_STORE_PHONE,
				$post['store_id']
			);

			if (defined('Mage_Core_Model_Store::XML_PATH_STORE_STORE_HOURS')) {
				$variables['store_hours'] = Mage::getStoreConfig(
					Mage_Core_Model_Store::XML_PATH_STORE_STORE_HOURS,
					$post['store_id']
				);
			}

			if (defined('Mage_Customer_Helper_Data::XML_PATH_SUPPORT_EMAIL')) {
				$variables['store_email'] = Mage::getStoreConfig(
					Mage_Customer_Helper_Data::XML_PATH_SUPPORT_EMAIL,
					$post['store_id']
				);
			}
		}

		if (!empty($post['customer_id']))
		{
			$variables['customer'] = Mage::getModel('customer/customer')->load($post['customer_id']);
		}

		if (!empty($post['order_id']))
		{
			$variables['order'] = Mage::getModel('sales/order')->load($post['order_id']);

			if ($variables['order']->getId())
			{
				$variables['billing'] = $variables['order']->getBillingAddress();
			}
		}

		if (!empty($post['invoice_id']))
		{
			$variables['invoice'] = Mage::getModel('sales/order_invoice')->load($post['invoice_id']);
		}

		if (!empty($post['shipment_id']))
		{
			$variables['shipment'] = Mage::getModel('sales/order_shipment')->load($post['shipment_id']);
		}

		if (!empty($post['creditmemo_id']))
		{
			$variables['creditmemo'] = Mage::getModel('sales/order_creditmemo')->load($post['creditmemo_id']);
		}

		if (!empty($post['wishlist_id']))
		{
			$variables['wishlist'] = Mage::getModel('wishlist/wishlist')->load($post['wishlist_id']);
		}

		$variables['comment'] = Mage::helper('simpleweb_emailpreview')->getRandomText(20);
		$variables['non_inline_styles'] = $this->_nonInlineCss;

		// Pass variables as object to get back modified data
		$variables = new Varien_Object($variables);
		$eventData = array(
			'template_id' => $post['template_id'],
			'orig_template_code' => null,
			'variables' => $variables,
		);

		// Set original template code if exists
		if (is_numeric($post['template_id']))
		{
			$template = Mage::getModel('core/email_template')
				->load($post['template_id']);

			if ($template->getId() && $template->getOrigTemplateCode())
			{
				$eventData['orig_template_code'] = $template->getOrigTemplateCode();
			}
		}

		Mage::dispatchEvent('simpleweb_emailpreview_set_variables', $eventData);

		$this->getFilter()->setVariables($variables->getData());
	}

	/**
	 * Process and return template subject
	 *
	 * @param string $subject
	 * @return string
	 */
	protected function _processTemplateSubject($subject)
	{
		return $this->getFilter()->filter($subject);
	}

	/**
	 * Get template text by template code or template id
	 *
	 * @param string|int $templateId
	 * @return string
	 */
	public function loadTemplate($templateId)
	{
		$templateText = '';

		// Load from database
		if (is_numeric($templateId))
		{
			$template = Mage::getModel('core/email_template')->load($templateId);
			if ($template->getId())
			{
				// Prepend subject / styles because in database they are saved separately
				$templateText = sprintf(
					"<!--@subject %s @-->\n" .
					"<!--@styles\n %s \n@-->\n" .
					"%s",
					$template->getTemplateSubject(),
					$template->getTemplateStyles(),
					$template->getTemplateText()
				);
			}
		}
		// Load from file
		else
		{
			$defaultTemplates = Mage_Core_Model_Email_Template::getDefaultTemplates();
			if (isset($defaultTemplates[$templateId]))
			{
				$this->_startEmulation(Mage::app()->getRequest()->getPost('store_id'));

				$templateText = Mage::app()->getTranslator()->getTemplateFile(
					$defaultTemplates[$templateId]['file'], 'email'
				);

				$this->_stopEmulation();
			}
		}

		// Remove block comment
		if (preg_match('/\{\*.+\*\}/s', $templateText, $matches)) {
			$templateText = str_replace($matches[0], '', $templateText);
		}

		// Remove variable comments
		if (preg_match('/<!--@subject\s*(.*?)\s*@-->/u', $templateText, $matches))
		{
			$templateText = str_replace($matches[0], '', $templateText);

			$this->_title = $matches[1];
		}

		if (preg_match('/<!--@vars\s*((?:.)*?)\s*@-->/us', $templateText, $matches))
		{
			$templateText = str_replace($matches[0], '', $templateText);
		}

		if (preg_match('/<!--@styles\s*(.*?)\s*@-->/s', $templateText, $matches))
		{
			$templateText = str_replace($matches[0], '', $templateText);

			if (class_exists('Mage_Core_Model_Email_Template_Abstract')) {
				$css = '';
				$css .= $this->_getCssByConfig(
					Mage_Core_Model_Email_Template_Abstract::XML_PATH_CSS_NON_INLINE_FILES,
					$this->getFilter()->getStoreId()
				);
				$css .= $matches[1];

				$this->_nonInlineCss = sprintf("<style type=\"text/css\">\n%s\n</style>\n", $css);
			}
			else {
				$templateText = sprintf("<style type=\"text/css\">\n%s\n</style>\n%s", $matches[1], $templateText);
			}
		}

		return $templateText;
	}

	/**
	 * Retrieve template variable data
	 *
	 * @param $templateText
	 * @param $post
	 * @return array
	 */
	public function getTemplateVariableData($templateText, $post)
	{
		// Start store emulation process
		$this->_startEmulation($post['store_id']);

		$this->setFilterVariables($post);

		$result = $this->_processTemplateVariables($templateText);

		$templateId = $post['template_id'];
		if (is_numeric($post['template_id']))
		{
			$templateId = $post['template_id'];
		}
		else
		{
			$defaultTemplates = Mage_Core_Model_Email_Template::getDefaultTemplates();
			if (isset($defaultTemplates[$post['template_id']]))
			{
				$templateId = $this->_getFullFilenamePath(
					$defaultTemplates[$post['template_id']]['file'],
					'email_template'
				);
			}
		}

		$this->_stopEmulation();

		return array(
			'variables' => $result[1],
			'template_id' => $templateId,
			'title' => $this->_processTemplateSubject($this->_title),
		);
	}

	/**
	 * Process variables in given text
	 *
	 * @param string $text
	 * @param int $varIndex
	 * @return array
	 */
	protected function _processTemplateVariables($text, $varIndex = -1)
	{
		$variables = array();

		// Parse if directives first
		foreach ($this->_ifDirectives as $pattern => $directive)
		{
			if (preg_match_all($pattern, $text, $constructions, PREG_SET_ORDER))
			{
				foreach($constructions as $index => $construction)
				{
					$varIndex++;

					preg_match(self::CONSTRUCTION_PATTERN, $construction[0], $name);

					$callback = array($this->getFilter(), $directive);
					if (!is_callable($callback))
					{
						continue;
					}

					$results = $this->_processTemplateVariables($construction[2], $varIndex);

					$varIndex = $results[0];

					$variables[$varIndex] = array($name[0], array(
						(call_user_func($callback, $construction) == $construction[2]) ? 1 : 0,
						$results[1]
					));

					// Process else directive
					if (isset($construction[4]))
					{
						$results = $this->_processTemplateVariables($construction[4]);

						$variables[$varIndex][1][2] = $results[1];
					}

					$text = str_replace($construction[0], '', $text);
				}
			}
		}


		if (preg_match_all(self::CONSTRUCTION_PATTERN, $text, $constructions, PREG_SET_ORDER))
		{
			foreach ($constructions as $construction)
			{

				$callback = array($this->getFilter(), $construction[1] . 'Directive');
				if (!is_callable($callback))
				{
					continue;
				}

				$varIndex++;
				$variables[$varIndex] = array($construction[0], '');

				$filePath = false;

				try
				{
					$value = call_user_func($callback, $construction);

					if ($construction[1] == 'inlinecss')
					{
						$value = $this->_processInlinecssDirective($construction);
						$filePath = true;
					}
					else if ($construction[1] == 'template')
					{
						$value = $this->_processTemplateDirective($construction);
						$filePath = true;
					}
					else if ($construction[1] == 'layout')
					{
						$value = $this->_processLayoutDirective($construction);
						$filePath = true;
					}
					else if ($construction[1] == 'block')
					{
						$value = $this->_processBlockDirective($construction);
						$filePath = true;
					}

					if (is_string($value) || is_numeric($value) || is_bool($value))
					{
						$variables[$varIndex] = array($construction[0], $value, $filePath);
					}
				}
				catch (Exception $e)
				{
					Mage::logException($e->getMessage());
				}
			}
		}

		return array($varIndex, $variables);
	}

	/**
	 * Return processed template subject
	 *
	 * @return string
	 */
	public function getProcessedTemplateSubject()
	{
		return $this->_title;
	}

	/**
	 * Get template text with variables replaced with real data from post
	 *
	 * @param string $templateText
	 * @param array $post
	 * @return mixed
	 */
	public function getProcessedTemplateText($templateText, $post)
	{
		$this->_startEmulation($post['store_id']);

		$variables = isset($post['variable']) ? $post['variable'] : array();

		$this->setFilterVariables($post);

		// If/Depend logic
		foreach ($this->_ifDirectives as $pattern => $directive)
		{
			if (preg_match_all($pattern, $templateText, $constructions, PREG_SET_ORDER))
			{
				foreach($constructions as $construction)
				{
					preg_match(self::CONSTRUCTION_PATTERN, $construction[0], $name);

					// If can not determine name
					if (!isset($variables[$name[0]]))
					{
						continue;
					}

					// If true, replace with first part
					if (isset($variables[$name[0]]) && $variables[$name[0]])
					{
						$templateText = str_replace($construction[0], $construction[2], $templateText);
					}
					// If false
					else
					{
						// If else exists
						if (isset($construction[4]))
						{
							$templateText = str_replace($construction[0], $construction[4], $templateText);
						}
						else
						{
							$templateText = str_replace($construction[0], '', $templateText);
						}
					}
				}
			}
		}

		$inlineCss = array();

		// Replace all variables with real data
		if (preg_match_all(self::CONSTRUCTION_PATTERN, $templateText, $constructions, PREG_SET_ORDER))
		{
			foreach ($constructions as $construction)
			{
				if (isset($variables[$construction[0]]))
				{
					$callback = array($this->getFilter(), $construction[1] . 'Directive');

					if ($construction[1] == 'inlinecss')
					{
						if (!is_callable($callback))
						{
							continue;
						}
						call_user_func($callback, $construction);

						$inlineCss[] = array(
							$this->getFilter()->getInlineCssFile(),
							$construction[0],
							$post['store_id']
						);
					}
					else if ($construction[1] == 'template')
					{
						if (!is_callable($callback))
						{
							continue;
						}
						$value = call_user_func($callback, $construction);
						$templateText = str_replace($construction[0], $value, $templateText);
					}
					else if ($construction[1] == 'block')
					{
						if (!is_callable($callback))
						{
							continue;
						}
						$value = call_user_func($callback, $construction);
						$templateText = str_replace($construction[0], $value, $templateText);
					}
					else if ($construction[1] == 'layout')
					{
						if (!is_callable($callback))
						{
							continue;
						}
						$value = call_user_func($callback, $construction);
						$templateText = str_replace($construction[0], $value, $templateText);
					}
					else
					{
						$templateText = str_replace($construction[0], $variables[$construction[0]], $templateText);
					}
				}
			}
		}

		// Apply inline CSS after template is generated
		foreach ($inlineCss as $data)
		{
			$templateText = str_replace($data[1], '', $templateText);
			$css = $this->_getCssFileContent($data[0], $post['store_id']);
			$templateText = $this->_applyInlineCss($templateText, $css);
		}

		// Inject Subject
		$this->_title = $this->_processTemplateSubject($this->_title);

		if (strpos($templateText, '</head>') === false)
		{
			$templateText = sprintf("<title>%s</title>\n%s", $this->_title, $templateText);
		}
		else
		{
			$templateText = str_replace('</head>', sprintf("<title>%s</title>\n</head>", $this->_title), $templateText);
		}

		$this->_stopEmulation();

		return $templateText;
	}

	/**
	 * Send email
	 *
	 * @param string $email
	 * @param string $subject
	 * @param string $templateText
	 * @return bool
	 */
	public function sendEmail($email, $subject, $templateText)
	{
		ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
		ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));

		$mail = new Zend_Mail('utf-8');

		$mail->addTo($email);
		$mail->setBodyHTML($templateText);
		$mail->setSubject('=?utf-8?B?' . base64_encode($subject) . '?=');
		$mail->setFrom(
			Mage::getStoreConfig('trans_email/ident_general/email'),
			Mage::getStoreConfig('trans_email/ident_general/name')
		);

		$mail->send();

		return true;
	}

	/**
	 * Get default customer data as json
	 *
	 * @param int $customerId
	 * @return array
	 */
	public function getCustomerData($customerId)
	{
		$json = array(
			'orders' => array(),
			'wishlists' => array(),
		);

		$orders = Mage::getModel('sales/order')
			->getCollection()
			->addFieldToFilter('customer_id', $customerId);

		foreach ($orders as $order)
		{
			$json['orders'][$order->getId()] = '#' . $order->getIncrementId();
		}

		$wishlists = Mage::getModel('wishlist/wishlist')
			->getCollection()
			->addFieldToFilter('customer_id', $customerId);

		foreach ($wishlists as $wishlist)
		{
			$json['wishlists'][$wishlist->getId()] = '#' . $wishlist->getId();
		}

		return $json;
	}

	/**
	 * Get default order data as json
	 *
	 * @param int $orderId
	 * @return array
	 */
	public function getOrderData($orderId)
	{
		$json = array(
			'invoices' => array(),
			'shipments' => array(),
			'creditmemos' => array(),
		);

		$invoices = Mage::getModel('sales/order_invoice')
			->getCollection()
			->addFieldToFilter('order_id', $orderId);

		foreach ($invoices as $invoice)
		{
			$json['invoices'][$invoice->getId()] = '#' . $invoice->getIncrementId();
		}

		$shipments = Mage::getModel('sales/order_shipment')
			->getCollection()
			->addFieldToFilter('order_id', $orderId);

		foreach ($shipments as $shipment)
		{
			$json['shipments'][$shipment->getId()] = '#' . $shipment->getIncrementId();
		}

		$creditmemos = Mage::getModel('sales/order_creditmemo')
			->getCollection()
			->addFieldToFilter('order_id', $orderId);

		foreach ($creditmemos as $creditmemo)
		{
			$json['creditmemos'][$creditmemo->getId()] = '#' . $creditmemo->getIncrementId();
		}

		return $json;
	}

	/**
	 * Return logo URL for emails
	 * Take logo from skin if custom logo is undefined
	 *
	 * @param  Mage_Core_Model_Store|int|string $store
	 * @return string
	 */
	protected function _getLogoUrl($store)
	{
		$store = Mage::app()->getStore($store);
		if (defined('Mage_Core_Model_Email_Template::XML_PATH_DESIGN_EMAIL_LOGO')) {
			$fileName = $store->getConfig(Mage_Core_Model_Email_Template::XML_PATH_DESIGN_EMAIL_LOGO);

			if ($fileName)
			{
				$uploadDir = Mage_Adminhtml_Model_System_Config_Backend_Email_Logo::UPLOAD_DIR;
				$fullFileName = Mage::getBaseDir('media') . DS . $uploadDir . DS . $fileName;
				if (file_exists($fullFileName))
				{
					return Mage::getBaseUrl('media') . $uploadDir . '/' . $fileName;
				}
			}
		}

		return Mage::getDesign()->getSkinUrl('images/logo_email.gif');
	}

	/**
	 * Return logo alt for emails
	 *
	 * @param  Mage_Core_Model_Store|int|string $store
	 * @return string
	 */
	protected function _getLogoAlt($store)
	{
		$store = Mage::app()->getStore($store);

		if (defined('Mage_Core_Model_Email_Template::XML_PATH_DESIGN_EMAIL_LOGO_ALT')) {
			$alt = $store->getConfig(Mage_Core_Model_Email_Template::XML_PATH_DESIGN_EMAIL_LOGO_ALT);
			if ($alt)
			{
				return $alt;
			}
		}

		return $store->getFrontendName();
	}

	/**
	 * Load CSS content from filesystem
	 *
	 * @param string $filename
	 * @return string
	 */
	protected function _getCssFileContent($filename, $storeId)
	{
		$this->_startEmulation($storeId);

		// This method should always be called within the context of the email's store, so these values will be correct
		$package = Mage::getDesign()->getPackageName();
		$theme = Mage::getDesign()->getTheme('skin');

		$filePath = Mage::getDesign()->getFilename(
			'css' . DS . $filename,
			array(
				'_type' => 'skin',
				'_default' => false,
				'_store' => $storeId,
				'_area' => 'frontend',
				'_package' => $package,
				'_theme' => $theme,
			)
		);

		$this->_stopEmulation();

		if (is_readable($filePath))
		{
			return (string) file_get_contents($filePath);
		}

		return '';
	}

	/**
	 * Accepts a path to a System Config setting that contains a comma-delimited list of files to load. Loads those
	 * files and then returns the concatenated content.
	 *
	 * @param $configPath
	 * @return string
	 */
	protected function _getCssByConfig($configPath, $storeId)
	{
		$filesToLoad = Mage::getStoreConfig($configPath);
		if (!$filesToLoad) {
			return '';
		}

		$files = array_map('trim', explode(",", $filesToLoad));

		$css = '';
		foreach ($files as $fileName)
		{
			$css .= $this->_getCssFileContent($fileName, $storeId) . "\n";
		}

		return $css;
	}

	/**
	 * Apply inline CSS
	 *
	 * @param string $html
	 * @param string $css
	 * @return string
	 */
	protected function _applyInlineCss($html, $css)
	{
		if (strlen($html))
		{
			$emogrifier = new Pelago_Emogrifier();
			$emogrifier->setHtml($html);
			$emogrifier->setCss($css);
			$emogrifier->setParseInlineStyleTags(false);
			$processedHtml = $emogrifier->emogrify();
		}
		else
		{
			$processedHtml = $html;
		}

		return $processedHtml;
	}

	/**
	 * Process Inline CSS directive
	 *
	 * @param array $data
	 * @return string
	 */
	protected function _processInlinecssDirective($data)
	{
		$cssFile = $this->getFilter()->getInlineCssFile();
		return $this->_getFullFilenamePath($cssFile, 'inlinecss');
	}

	/**
	 * Process template directive
	 *
	 * @param array $data
	 * @return string
	 */
	protected function _processTemplateDirective($data)
	{
		preg_match('/config_path\s*=\s*["\']([^"\']+)["\']/', $data[2], $param);

		$template = $this->_getTemplateFromConfigPath($param[1]);
		return $this->_getFullFilenamePath($template, 'email_template');
	}

	/**
	 * Process layout directive
	 *
	 * @param array $data
	 * @return string
	 */
	protected function _processLayoutDirective($data)
	{
		preg_match('/area\s*=\s*["\']([^"\']+)["\']/', $data[2], $area);
		preg_match('/handle\s*=\s*["\']([^"\']+)["\']/', $data[2], $handle);

		$params = array();
		if (!empty($area[1]))
		{
			$params['area'] = $area[1];
		}
		if (!empty($handle[1]))
		{
			$params['handle'] = $handle[1];
		}

		return $this->_getFullFilenamePath(
			$this->_getLayoutTemplate($params),
			'template'
		);
	}

	/**
	 * Process block directive
	 *
	 * @param array $data
	 * @return string
	 */
	protected function _processBlockDirective($data)
	{
		preg_match('/template\s*=\s*["\']([^"\']+)["\']/', $data[2], $template);
		preg_match('/type\s*=\s*["\']([^"\']+)["\']/', $data[2], $type);
		preg_match('/area\s*=\s*["\']([^"\']+)["\']/', $data[2], $area);

		// If no type, return nothing
		if (empty($type[1]))
		{
			return '';
		}

		$layout = Mage::getModel('core/layout');

		/* @var $layout Mage_Core_Model_Layout */
		if (isset($params['area']))
		{
			$layout->setArea($params['area']);
		}
		else
		{
			$layout->setArea(Mage::app()->getLayout()->getArea());
		}

		/** @var Mage_Core_Block_Template $block */
		$block = $layout->createBlock($type[1]);

		if (!empty($template[1]))
		{
			$block->setTemplate($template[1]);
		}

		return $this->_getFullFilenamePath($block->getTemplate(), 'template');
	}

	/**
	 * Get full file path based on type
	 *
	 * @param string $filename
	 * @param string $type
	 * @return string
	 */
	protected function _getFullFilenamePath($filename, $type = 'template')
	{
		$path = '';
		if ($type == 'template')
		{
			$path = '/app/design/' . Mage::getDesign()
				->getTemplateFilename($filename, array('_relative' => true));
		}
		else if ($type == 'email_template')
		{
			$localeCode = Mage::app()->getLocale()->getLocaleCode();

			$filePath = Mage::getBaseDir('locale')  . DS
				. $localeCode . DS . 'template' . DS . 'email' . DS . $filename;

			if (!file_exists($filePath))
			{
				$filePath = Mage::getBaseDir('locale') . DS
					. Mage::app()->getLocale()->getDefaultLocale()
					. DS . 'template' . DS . 'email' . DS . $filename;
			}

			if (!file_exists($filePath))
			{
				$filePath = Mage::getBaseDir('locale') . DS
					. Mage_Core_Model_Locale::DEFAULT_LOCALE
					. DS . 'template' . DS . 'email' . DS . $filename;
			}

			$path = str_replace(Mage::getBaseDir(), '', $filePath);
		}
		else if ($type == 'inlinecss')
		{
			$path = Mage::getDesign()->getFilename(
				'css' . DS . $filename,
				array(
					'_type' => 'skin',
					'_default' => false,
					'_store' => $this->getFilter()->getStoreId(),
					'_area' => 'frontend',
					'_package' => Mage::getDesign()->getPackageName(),
					'_theme' => Mage::getDesign()->getTheme('skin'),
				)
			);

			$path = str_replace(Mage::getBaseDir(), '', $path);
		}

		// Fix windows paths
		$path = str_replace('\\', '/', $path);
		$path = trim($path, ' \/');

		return $path;
	}

	/**
	 * Return template ID or path
	 *
	 * @param string $configPath
	 * @return mixed|null
	 */
	protected function _getTemplateFromConfigPath($configPath)
	{
		$templateId = Mage::getStoreConfig($configPath);

		if (is_numeric($templateId))
		{
			return $templateId;
		}

		$defaultTemplates = Mage_Core_Model_Email_Template::getDefaultTemplates();

		if (isset($defaultTemplates[$templateId]))
		{
			return $defaultTemplates[$templateId]['file'];
		}

		return null;
	}

	/**
	 * Get layout template file path from params
	 *
	 * @param array $params
	 * @return string
	 */
	protected function _getLayoutTemplate($params)
	{
		$layout = Mage::getModel('core/layout');
		/* @var $layout Mage_Core_Model_Layout */
		if (isset($params['area']))
		{
			$layout->setArea($params['area']);
		}
		else
		{
			$layout->setArea(Mage::app()->getLayout()->getArea());
		}

		$layout->getUpdate()->addHandle($params['handle']);
		$layout->getUpdate()->load();

		$layout->generateXml();
		$layout->generateBlocks();

		$allBlocks = $layout->getAllBlocks();
		$firstBlock = reset($allBlocks);
		if ($firstBlock)
		{
			return $firstBlock->getTemplate();
		}

		return '';
	}

	/**
	 * Start Store emulation
	 *
	 * @param int $storeId
	 * @return void
	 */
	protected function _startEmulation($storeId)
	{
		$this->_emulation[0] = Mage::getSingleton('core/app_emulation');
		$this->_emulation[1] = $this->_emulation[0]->startEnvironmentEmulation($storeId);
	}

	/**
	 * Stop Store emulation
	 *
	 * @return void
	 */
	protected function _stopEmulation()
	{
		$this->_emulation[0]->stopEnvironmentEmulation($this->_emulation[1]);
	}
}