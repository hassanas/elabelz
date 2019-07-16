<?php
/**
 * @author Umar
 * @copyright Copyright (c) 2018 Progos
 * @package Progos_AgentComments
 */

/**
 * Form on customer admin side to get the comments from the admin
 */
class Progos_AgentComments_Block_Adminhtml_Customer_Edit_View_Tabs_Reviews_Comments
    extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * initializing form
     */
    public function initForm()
    {
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('agentcomments_');

        $fieldset = $form->addFieldset(
            'progos_agentcomments_fieldset', array('legend' => Mage::helper('progos_agentcomments')->__('Comments'))
        );

        $text = $fieldset->addField(
            'progos_agentcomments_comment',
            'text',
            array(
                'label' => Mage::helper('progos_agentcomments')->__('Comment'),
                'name' => 'agent_comment_textfield',
                'note' => Mage::helper('progos_agentcomments')->__('Enter a comment'),
                'class' => 'validate agentcommenttextfield',
            )
        );
        $url = Mage::helper('adminhtml')->getUrl('adminhtml/agentreviews/saveagentcomment');
        $saveAgentCommentBtn = $fieldset->addField('save_agent_comment', 'button', array(
            'label' => $this->__('Click to add comment'),
            'value' => $this->__('Add Comment'),
            'name' => 'save_agent_comment',
            'onclick' => 'checkSelectedItem()',
        ));
        $saveAgentCommentBtn->setAfterElementHtml("<script type=\"text/javascript\">
    function checkSelectedItem(){
        var completeUrl = '" . $url . "';
        var comment = jQuery('.agentcommenttextfield').val();
        var customer = '" . Mage::app()->getRequest()->getParam('id') . "';
        var admin = '" . Mage::getSingleton('admin/session')->getUser()->getUserId() . "';
        new Ajax.Request(completeUrl, {
            method: 'post',
            parameters: {comment: comment, customer: customer, admin: admin},
            onLoading: function (transport) {
                $('parent_id').update('Searching...');
            },
            onSuccess:function(response){
                        var raw = response.responseText;
                        if(raw == 'success'){
             showMessage('" . Mage::helper('progos_agentcomments')->__('Comment added successfully') . "','success');           
                        }
            }
        });
    }
    function showMessage(txt, type) {
    var html = '<ul class=\"messages\"><li class=\"'+type+'-msg\"><ul><li>' + txt + '</li></ul></li></ul>';
    $('messages').update(html);
}
</script><style type=\"text/css\"> #agentcomments_save_agent_comment{border-width: 1px;
    border-style: solid;
    border-color: #ed6502 #a04300 #a04300 #ed6502;
    padding: 1px 7px 2px 7px;
    background: #ffac47 url('" . $this->getSkinUrl('images/btn_bg.gif', array('_secure' => true)) . "') repeat-x 0 100%;
    color: #fff;
    font: bold 12px arial, helvetica, sans-serif;
    cursor: pointer;
    text-align: center !important;
    white-space: nowrap;}</style>");
        $this->setForm($form);
        return $this;
    }
}