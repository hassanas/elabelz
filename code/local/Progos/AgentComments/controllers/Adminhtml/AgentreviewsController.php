<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AgentComments
 */

/**
 * Controller for saving the comments
 */
class Progos_AgentComments_Adminhtml_AgentreviewsController extends Mage_Core_Controller_Front_Action
{
    public function saveagentcommentAction()
    {
        if($this->getRequest()->isXmlHttpRequest()) {
            $post = $this->getRequest()->getPost();
            $comment = $post['comment'];
            $admin = $post['admin'];
            $customer = $post['customer'];
            if(!empty($post) && !empty($comment) && !empty($customer)){
                try {
                    $model = Mage::getModel('progos_agentcomments/comments');
                    $model->setComment($comment);
                    $model->setCustomerId($customer);
                    $model->setAdminId($admin);
                    $model->save();
                    return $this->getResponse()->setBody('success');
                }catch (Exception $e) {
                    __error($e);
                }
            }else{
                return $this->getResponse()->setBody('failed');
            }
        }
        return $this->getResponse()->setBody('failed');
    }
}