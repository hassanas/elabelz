<?php
/**
 * Progos_OrdersEdit
 *
 * @category    Progos
 * @package     Progos_OrdersEdit
 * @author      Sergejs Plisko <sergejs.plisko@redboxdigital.com>
 * @copyright   Copyright (c) 2017 Progos, Ltd (http://progos.org)
 */
?>
<?php
/**
 * Class Progos_OrdersEdit_Adminhtml_Mageworx_Ordersedit_HistoryController
 */
include_once('Mage/Adminhtml/controllers/Sales/OrderController.php');

class Progos_OrdersEdit_Adminhtml_Mageworx_Ordersedit_HistoryController extends Mage_Adminhtml_Sales_OrderController
{
    /**
     * Save new order comment
     */
    public function addCommentAction()
    {
        /** Begin: Update add comment action functionality */
        $order_id = $this->getRequest()->getParam("order_id");
        $ref = $this->getRequest()->getParam("ref");

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel("sales/order")->load($order_id);
        if ($order->getEntityId() && $ref == "cs") {
            try {
                $response = false;
                $data = $this->getRequest()->getPost('history');

                $date = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                $preComments = unserialize($order->getCallLog());
                $preComments[] = [
                    "timestamp" => $date,
                    "comment" => $data["comment"],
                    "status" => (int)$data["call_response"]
                ];

                $callResponse = $data["call_response"];

                if ($callResponse == "0") {
                    $callResponse = "<strong>No answer from customer</strong>";

                    if (is_null($order->getSession())) {
                        $next_session = Mage::helper('marketplace/marketplace')->getSession($order->getCreatedAt(), true);
                    } else {
                        if ($order->getSession() == "Morning") {
                            $next_session = "Afternoon";
                        } elseif ($order->getSession() == "Afternoon") {
                            $next_session = "Evening";
                        } else {
                            $next_session = "Morning";
                        }
                    }

                    $order->setSession($next_session);
                    $order->save();
                }

                if ($callResponse == "1") {
                    $callResponse = "Call Response: <strong>Customer approved all items</strong>";
                }

                if ($callResponse == "2") {
                    $callResponse = "Call Response: <strong>Customer wants changes</strong>";
                }

                if ($callResponse == "3") {
                    $callResponse = "Call Response: <strong>Wrong number</strong>";
                }

                $comment = $data["comment"];
                if (strlen(trim($comment))) {
                    $comment = $callResponse . "!<br><strong>Agent Comment:</strong> {$comment}<br>-- via Call Center.";
                } else {
                    $comment = $callResponse . " via Call Center.";
                }

                $order->addStatusHistoryComment($comment, $order->getStatus())
                    ->setIsVisibleOnFront(0)
                    ->setIsCustomerNotified(0);
                $order->save();

                $order->setCallLog(serialize($preComments));
                $order->save();

                $this->loadLayout('empty');

                if ($ref == "cs") {
                    $this->getLayout()->getBlock('order_history')->setTemplate('callcenter/comments-history.phtml');
                }

                $this->renderLayout();
            } catch (Mage_Core_Exception $e) {
                $response = array(
                    'error' => true,
                    'message' => $e->getMessage(),
                );
            } catch (Exception $e) {
                $response = array(
                    'error' => true,
                    'message' => $this->__('Cannot add order history.')
                );
            }

            if (is_array($response)) {
                $response = Mage::helper('core')->jsonEncode($response);
                $this->getResponse()->setBody($response);
            }
        } else {
        /** End: Update add comment action functionality */
            if ($order = $this->_initOrder()) {
                try {
                    $response = false;
                    $data = $this->getRequest()->getPost('history');
                    $notify = isset($data['is_customer_notified']) ? $data['is_customer_notified'] : false;
                    $visible = isset($data['is_visible_on_front']) ? $data['is_visible_on_front'] : false;

                    /** Begin: Update add comment action functionality */
                    $is_update_order_status = isset($data['is_update_order_status']) ? $data['is_update_order_status'] : false;
                    $currentDateTime = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                    $order_id = $this->getRequest()->getParam('order_id');
                    $modelCommission = Mage::getModel('marketplace/commission')->getCollection()
                        ->addFieldToFilter("order_id", ["eq" => $order_id]);
                    foreach ($modelCommission as $row) {
                        if ($data['status'] == 'successful_delivery' || $data['status'] == 'failed_delivery' || $data['status'] == 'shipped_from_elabelz') {
                            if ($row->getItemOrderStatus() == 'canceled') {
                                $row->setOrderStatus($data['status'])->save();
                            } else {
                                $row->setOrderStatus($data['status'])->save();
                                $row->setItemOrderStatus($data['status'])->save();
                            }

                            if ($data['status'] == 'shipped_from_elabelz') {
                                $row->setShippedFromElabelzDate($currentDateTime)->save();
                            }
                        } else {
                            if ($data['status'] == 'successful_delivery_partially') {
                            } else {
                                if ($data['status'] == 'complete') {
                                    $row->setSuccessfulNonRefundableDate($currentDateTime)->save();
                                }
                            }
                            $row->setOrderStatus($data['status'])->save();
                        }
                    }

                    if (isset($is_update_order_status) && $is_update_order_status > 0) {
                    /** End: Update add comment action functionality */
                        $order->addStatusHistoryComment($data['comment'], $data['status'])
                            ->setIsVisibleOnFront($visible)
                            ->setIsCustomerNotified($notify);
                    /** Begin: Update add comment action functionality */
                    } else {
                        $order->addStatusHistoryComment($data['comment'])
                            ->setIsVisibleOnFront($visible)
                            ->setIsCustomerNotified($notify);
                    }
                    /** End: Update add comment action functionality */

                    $comment = trim(strip_tags($data['comment']));

                    $order->save();
                    // update extended orders grid for status
                    if(isset($is_update_order_status) && $is_update_order_status>0){
                        Mage::getModel('mageworx_ordersgrid/order_grid')->syncOrdersStatus($order->getId());
                    }

                    // if send upload file
                    if (isset($_FILES['send_file']['size']) && $_FILES['send_file']['size'] > 0) {

                        $histories = $order->getStatusHistoryCollection(true);
                        foreach ($histories as $h) {
                            $historyId = $h->getEntityId();
                            break;
                        }

                        $uploadFile = Mage::getModel('mageworx_ordersedit/upload_files')
                            ->setHistoryId($historyId)
                            ->setFileName($_FILES['send_file']['name'])
                            ->setFileSize($_FILES['send_file']['size'])
                            ->save();

                        $fileId = $uploadFile->getEntityId();
                        $filePath = $this->getMwHelper()->getUploadFilesPath($fileId, true);
                        copy($_FILES['send_file']['tmp_name'], $filePath);

                        $this->getMwHelper()->sendOrderUpdateEmail($order, $notify, $comment, $filePath, $uploadFile->getFileName());
                        return $this->_redirectReferer();
                    }

                    $order->sendOrderUpdateEmail($notify, $comment);

                    $this->loadLayout('empty');
                    $this->renderLayout();
                } catch (Mage_Core_Exception $e) {
                    $response = array(
                        'error' => true,
                        'message' => $e->getMessage(),
                    );
                } catch (Exception $e) {
                    $response = array(
                        'error' => true,
                        'message' => $this->__('Cannot add order history.')
                    );
                }
                if (is_array($response)) {
                    $response = Mage::helper('core')->jsonEncode($response);
                    $this->getResponse()->setBody($response);
                }
            }
        }
        
        /** Begin: Sending Failed Delivery Email */
        if($order->getStatus() == "failed_delivery"){
         Mage::helper('marketplace/vieworder')->failedOrderItemEmail($order->getId());
        }
        /** End: Sending Failed Delivery Email */

        /** Begin: Adding Failed Delivery Value */
        $this->getProgosHelper()->getFailedDeliveryStatus($order);
        /** End: Adding Failed Delivery Value */
    }


    /**
     * Get Progos OrdersGrid helper
     *
     * @return Progos_OrdersEdit_Helper_Data
     */
    protected function getProgosHelper()
    {
        return Mage::helper('progos_ordersedit');
    }

    /**
     * Get MageWorx OrdersGrid helper
     *
     * @return MageWorx_OrdersEdit_Helper_Data
     */
    protected function getMwHelper()
    {
        return Mage::helper('mageworx_ordersedit');
    }
}