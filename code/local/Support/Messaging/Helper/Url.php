<?php
class Support_Messaging_Helper_Url extends Mage_Core_Helper_Abstract {

    public function getMessagingDashboardUrl() {
        return Mage::getUrl ('messaging/index/index' );
    }

    public function getMessageHistoryUrl() {
        return Mage::getUrl ('messaging/history' );
    }

    public function getSendMessageUrl($id) {
        return Mage::getUrl ('messaging/history/show', ["id"=>$id]);
    }

}
