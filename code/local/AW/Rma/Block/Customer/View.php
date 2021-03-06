<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This software is designed to work with Magento community edition and
 * its use on an edition other than specified is prohibited. aheadWorks does not
 * provide extension support in case of incorrect edition use.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Rma
 * @version    1.6.0
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE.txt
 */


class AW_Rma_Block_Customer_View extends Mage_Core_Block_Template
{
    /**
     * Collection of comments
     *
     * @var AW_Rma_Model_Mysql4_Entitycomments_Collection
     */
    private $_comments = null;
    private $_guestMode = false;
    private $_rmaRequest = null;

    public function __construct()
    {
        parent::__construct();
        if (Mage::helper('awrma')->checkExtensionVersion('Mage_Core', '0.8.25')) {
            $_template = 'aw_rma/customer/view.phtml';
        } else {
            $_template = 'aw_rma/customer/view13x.phtml';
        }
        $this->setTemplate($_template);
        return $this;
    }

    /**
     * Returns RMA request and loads all comments for it
     *
     * @return AW_Rma_Model_Entity
     */
    public function getRMARequest()
    {
        if (!$this->_rmaRequest) {
            $_request = Mage::registry('awrma-request');
            if (!is_null($_request) && is_null($this->_comments)) {
                $this->_comments = Mage::getModel('awrma/entitycomments')->getCollection()
                    ->setEntityFilter($_request->getId())
                    ->setOrder('created_at', 'DESC')
                    ->setOrder('id', 'DESC')
                    ->load()
                ;
            }
            $this->_rmaRequest = $_request;
        }
        return $this->_rmaRequest;
    }

    /**
     * Returns all comments for current request
     *
     * @return AW_Rma_Model_Mysql4_Entitycomments_Collection
     */
    public function getComments()
    {
        return $this->_comments;
    }

    /**
     * Returns stored form data
     *
     * @return array
     */
    public function getFormData()
    {
        return Mage::getSingleton('customer/session')->getAWRMACommentFormData(true);
    }

    public function setGuestMode($val = true)
    {
        $this->_guestMode = (bool)$val;
        return $this;
    }

    public function getGuestMode()
    {
        return $this->_guestMode;
    }

    public function getPrintLabelUrl()
    {
        if ($this->getGuestMode()) {
            return $this->getUrl(
                'awrma/guest_rma/printlabel', array('id' => $this->getRMARequest()->getExternalLink(), '_secure' => Mage::app()->getStore(true)->isCurrentlySecure())
            );
        } else {
            return $this->getUrl('awrma/customer_rma/printlabel', array('id' => $this->getRMARequest()->getId(), '_secure' => Mage::app()->getStore(true)->isCurrentlySecure()));
        }
    }

    public function getConfirmSendUrl()
    {
        if ($this->getGuestMode()) {
            return $this->getUrl(
                'awrma/guest_rma/confirmsend', array('id' => $this->getRMARequest()->getExternalLink(), '_secure' => Mage::app()->getStore(true)->isCurrentlySecure())
            );
        } else {
            return $this->getUrl('awrma/customer_rma/confirmsend', array('id' => $this->getRMARequest()->getId(), '_secure' => Mage::app()->getStore(true)->isCurrentlySecure()));
        }
    }

    public function getCancelUrl()
    {
        if ($this->getGuestMode()) {
            return $this->getUrl('awrma/guest_rma/cancel', array('id' => $this->getRMARequest()->getExternalLink(), '_secure' => Mage::app()->getStore(true)->isCurrentlySecure()));
        } else {
            return $this->getUrl('awrma/customer_rma/cancel', array('id' => $this->getRMARequest()->getId(), '_secure' => Mage::app()->getStore(true)->isCurrentlySecure()));
        }
    }

    public function getCommentUrl()
    {
        if ($this->getGuestMode()) {
            return $this->getUrl('awrma/guest_rma/comment', array('id' => $this->getRMARequest()->getExternalLink(), '_secure' => Mage::app()->getStore(true)->isCurrentlySecure()));
        } else {
            return $this->getUrl('awrma/customer_rma/comment', array('id' => $this->getRMARequest()->getId(), '_secure' => Mage::app()->getStore(true)->isCurrentlySecure()));
        }
    }

    public function getDownloadUrl(AW_Rma_Model_Entitycomments $comment)
    {
        if ($this->getGuestMode()) {
            $entity = $comment->getEntity();
            return $this->getUrl('awrma/guest_rma/download', array(
                'cid' => $comment->getId(),
                'e' => md5($entity->getData('customer_email')),
            ));
        } else {
            return $this->getUrl('awrma/customer_rma/download', array('cid' => $comment->getId(), '_secure' => Mage::app()->getStore(true)->isCurrentlySecure()));
        }
    }

    public function getPreparedJSConfirmText()
    {
        $confirmtext = Mage::helper('awrma/config')->getConfirmSendingText();
        $confirmtext = addslashes($confirmtext);
        $confirmtext = mb_ereg_replace("[\x0A]", '\n', $confirmtext);
        $confirmtext = mb_ereg_replace("[\x00-\x09\x0B-\x19\x7F]", '', $confirmtext);
        return $confirmtext;
    }
}
