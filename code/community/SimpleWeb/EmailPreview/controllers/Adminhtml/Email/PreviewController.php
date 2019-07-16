<?php
/**
 * SimpleWeb
 *
 * @category    Community
 * @package     SimpleWeb_EmailPreview
 * @author		SimpleWeb <support@simpleweb.lv>
 */
class SimpleWeb_EmailPreview_Adminhtml_Email_PreviewController
	extends Mage_Adminhtml_Controller_Action
{
	/**
	 * Check if resource is available
	 *
	 * @return mixed
	 */
	protected function _isAllowed()
	{
		return Mage::getSingleton('admin/session')->isAllowed('system/tools/email_preview');
	}

	/**
	 * Main action
	 */
	public function indexAction()
	{
		$this->_title($this->__('Email Preview'));
		$this->loadLayout();
		$this->_setActiveMenu('system');
		$this->renderLayout();
	}

	/**
	 * Preview in browser action
	 */
	public function previewAction()
	{
		$post = $this->getRequest()->getPost();
		$templateId = $this->getRequest()->getPost('template_id');

		$templateText = $this->_getModel()->loadTemplate($templateId);

		if ($templateText)
		{
			$templateText = $this->_getModel()->getProcessedTemplateText($templateText, $post);
		}

		$this->getResponse()->setBody($templateText);
	}

	/**
	 * Send to email action
	 */
	public function sendAction()
	{
		$post = $this->getRequest()->getPost();
		$templateId = $this->getRequest()->getPost('template_id');

		$templateText = $this->_getModel()->loadTemplate($templateId);

		if ($templateText)
		{
			$templateText = $this->_getModel()->getProcessedTemplateText($templateText, $post);
		}

		$templateSubject = $this->_getModel()->getProcessedTemplateSubject();

		$response = array('success' => false);

		try
		{
			$this->_getModel()->sendEmail($post['recipient'], $templateSubject, $templateText);
			$response['success'] = true;
		}
		catch (Exception $e)
		{
			$response['message'] = $e->getMessage();
		}

		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($response));
	}

	/**
	 * Get template variable data
	 */
	public function getVariablesAction()
	{
		$post = $this->getRequest()->getPost();
		$templateId = $this->getRequest()->getPost('template_id');

		$templateText = $this->_getModel()->loadTemplate($templateId);
		$variables = $this->_getModel()->getTemplateVariableData($templateText, $post);

		$this->loadLayout();
		$this->getLayout()->getBlock('root')->addData($variables);

		$this->renderLayout();
	}

	/**
	 * On customer change, return customer orders and wishlists
	 */
	public function changeCustomerAction()
	{
		$customerId = $this->getRequest()->getPost('customer_id');

		$json = $this->_getModel()->getCustomerData($customerId);

		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($json));
	}

	/**
	 * On order change, return linked invoices, shipments, creditmemos
	 */
	public function changeOrderAction()
	{
		$orderId = $this->getRequest()->getPost('order_id');

		$json = $this->_getModel()->getOrderData($orderId);

		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody(json_encode($json));
	}

	/**
	 * Install test data
	 */
	public function installAction()
	{
		try
		{
			$data = Mage::getModel('simpleweb_emailpreview/data');
			$data->install();

			$this->_getSession()->addSuccess(
				$this->_getHelper()->__('Test data installed.')
			);
		}
		catch (Exception $e)
		{
			$this->_getSession()->addError(
				$this->_getHelper()->__('Could not install test data. ' . $e->getMessage())
			);
		}

		Mage::app()->getCacheInstance()->cleanType('config');

		$this->_redirectReferer();
	}

	/**
	 * Uninstall test data
	 */
	public function uninstallAction()
	{
		try
		{
			$data = Mage::getModel('simpleweb_emailpreview/data');
			$data->uninstall();

			$this->_getSession()->addSuccess(
				$this->_getHelper()->__('Test data uninstalled.')
			);
		}
		catch (Exception $e)
		{
			$this->_getSession()->addError(
				$this->_getHelper()->__('Could not uninstall test data. ' . $e->getMessage())
			);
		}

		Mage::app()->getCacheInstance()->cleanType('config');

		$this->_redirectReferer();
	}

	/**
	 * @return SimpleWeb_EmailPreview_Model_Preview
	 */
	protected function _getModel()
	{
		return Mage::getSingleton('simpleweb_emailpreview/preview');
	}
}
