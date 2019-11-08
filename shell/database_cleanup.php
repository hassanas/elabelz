<?php
/**
 * Shell script that removes tables of unused extensions and also removes module record in core_resource table
 */
require_once 'abstract.php';

class Database_Shell_Cleanup extends Mage_Shell_Abstract
{

    protected $tableNames = [
        'bronto_common_api',
        'bronto_common_api_errors',
        'bronto_common_api_send_queue',
        'bronto_customer_queue',
        'bronto_email_log',
        'bronto_email_template',
        'bronto_emailcapture_queue',
        'bronto_news_item',
        'bronto_newsletter_queue',
        'bronto_order_queue',
        'bronto_product_recommendation',
        'bronto_reminder_rule',
        'bronto_reminder_rule_website',
        'bronto_reminder_message',
        'bronto_reminder_rule_coupon',
        'bronto_reminder_rule_log',
        'bronto_reminder_delivery_log',
        'bronto_guest_emails',
        'bronto_reviews_queue',
        'bronto_reviews_post_purchase',
        'bronto_reviews_log',
        'rewards_milestone_rule',
        'rewards_milestone_rule_log',
        'rewards_reports_indexer_order',
        'rewards_transfer',
        'rewards_transfer_reference',
        'rewards_currency',
        'rewards_store_currency',
        'rewards_special',
        'rewards_customer_index_points',
        'rewards_catalogrule_label',
        'rewards_catalogrule_product',
        'rewards_importer',
        'aw_raf2_rule',
        'aw_raf2_activity',
        'aw_raf2_transaction',
        'aw_raf2_discount',
        'aw_raf2_referral',
        'aw_raf2_trigger',
        'aw_raf2_orderref',
        'aw_raf2_statistics',
        'japi_sales_order_aggregated',
        'mageplaza_betterblog_post',
        'mageplaza_betterblog_category',
        'mageplaza_betterblog_tag',
        'mageplaza_betterblog_category_store',
        'mageplaza_betterblog_tag_store',
        'mageplaza_betterblog_post_category',
        'mageplaza_betterblog_post_category',
        'mageplaza_betterblog_post_tag',
        'mageplaza_betterblog_post_tag',
        'mageplaza_betterblog_post_comment',
        'mageplaza_betterblog_post_comment_store',
        'mageplaza_betterblog_eav_attribute',
        'imaclean',
        'oneall_sociallogin_entity',
    ];

    protected $resourceRowNames = [
        'bronto_common_setup',
        'bronto_customer_setup',
        'bronto_emailcapture_setup',
        'bronto_email_setup',
        'bronto_newsletter_setup',
        'bronto_news_setup',
        'bronto_order_setup',
        'bronto_product_setup',
        'bronto_reminder_setup',
        'bronto_reviews_setup',
        'tbtmilestone_setup',
        'tbtreports_setup',
        'rewards_setup',
        'rewardsapi_setup',
        'rewardsonly_setup',
        'rewardsplat_setup',
        'rewardsref_setup',
        'rewardssocial2_setup',
        'awraf_setup',
        'japi_setup',
        'mageplaza_betterblog_setup',
        'mgt_base_setup',
        'oneall_sociallogin_setup',
        'tatvic_uaee_setup',
    ];

    public function run()
    {
        error_reporting(E_ALL);
        ini_set('error_reporting', E_ALL);
        ini_set('max_execution_time', 360000);
        set_time_limit(360000);

        if ($this->getArg('help')) {
            $this->usageHelp();
        } else {
            $this->removeTables();
        }
    }

    protected function removeTables()
    {
        $timeStart = microtime(true);
        $startMemory = memory_get_usage();
        $resource = Mage::getSingleton('core/resource');

        try {
            $connection = $resource->getConnection('core_write');
            foreach ($this->tableNames as $tableName) {
                $connection->query("DROP TABLE IF EXISTS {$resource->getTableName($tableName)}");
                echo "Table {$tableName} deleted successfully";
            }
            foreach ($this->resourceRowNames as $resourceRowName) {
                $connection->query(
                    "DELETE FROM {$resource->getTableName('core_resource')}
                        WHERE 'code' = {$resource->getTableName($resourceRowName)}"
                );
                echo "Row {$resourceRowName} has been deleted from core_resource successfully";
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::log($e->getMessage(), null, 'database_cleanup.log');
        }
        $endMemory = memory_get_usage();
        echo "Time: " . ((microtime(true) - $timeStart) / 60) . " minutes\n";
        echo "Memory: " . (int)($endMemory - $startMemory) . " bytes\n";
    }
}

$shell = new Database_Shell_Cleanup();
$shell->run();