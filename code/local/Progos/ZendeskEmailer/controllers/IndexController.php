<?php
class Progos_ZendeskEmailer_IndexController extends Mage_Core_Controller_Front_Action {
	/**
	*	index action
	*	@access public
	*	@return void
	*/
	public function indexAction() {

		$refererURL = Mage::app()->getRequest()->getServer('HTTP_REFERER');

		if (isset($refererURL) && strpos($refererURL,'elabelz.zendesk.com')!=false) {

			$emailTemplate = Mage::getModel('core/email_template')->loadDefault('zendesk_email_template');
			
			$name 			  = $this->getRequest()->getParam('name');
			$orderno 		  = $this->getRequest()->getParam('orderno');
			$mobile 		  = $this->getRequest()->getParam('mobile');
			$issue_desc		  = $this->getRequest()->getParam('desc');
			$submission_title = $this->getRequest()->getParam('submission_title');
			$layout			  = $this->getRequest()->getParam('layout');

			if (isset($layout) && $layout=='Arabic') {
				$redirectTo = "https://elabelz.zendesk.com/hc/ar/articles/115000456169";
			} else {
				$redirectTo = "https://elabelz.zendesk.com/hc/en-us/articles/115000456169";
			}

			// custom variables for template
			/*$emailTemplateVariables = array(
				'name' => $name,
				'orderNo' => $orderno,
				'mobile' => $mobile,
				'description' => $issue_desc,
				'submission_title' => $submission_title
			);


			$processedTemplate = $emailTemplate->getProcessedTemplate($emailTemplateVariables);*/

			$html = '<table width="648" height="117" border="0" cellpadding="12" cellspacing="0" style="margin: 0;font-family: Tahoma,Arial,sans-serif;width: 100%;max-width: 648px;color: #000;border: 1px solid #ccc;padding: 10px 5px;text-align: left;">
						<tr><th style="vertical-align: top;width: 150px;">Name: </th><td>'.$name.'</td></tr>
						<tr><th style="vertical-align: top;width: 150px;">Order No: </th><td>'.$orderno.'</td></tr>
						<tr><th style="vertical-align: top;width: 150px;">Mobile: </th><td>'.$mobile.'</td></tr>
						<tr><th style="vertical-align: top;width: 150px;">Issue Description: </th><td>'.$issue_desc.'</td></tr>
						<tr><th style="vertical-align: top;width: 150px;">Submission Title: </th><td>'.$submission_title.'</td></tr>
					 </table>';



			$email = Mage::getModel('core/email')
							->setToName('Elabelz')
							->setToEmail(array('m.bin.tabassum@progos.org')) //wecare@elabelz.com
							->setBody($html)
							->setSubject('Zendesk | Complains & Suggestions')
							->setFromEmail('complains_suggestions@elabelz.com') // complains@elabelz.zendesk.com
							->setFromName('Elabelz Zendesk')
							->setType('html');

			try {
				$email->send();
				$this->_redirectUrl($redirectTo.'?msg=success');
			} catch (Exception $error) {
				//echo $error->getMessage(); exit;
				$this->_redirectUrl($redirectTo.'?msg=error');
			}


		} else {
			$this->_redirectUrl($redirectTo.'?msg=invalid');
		}


	}

}