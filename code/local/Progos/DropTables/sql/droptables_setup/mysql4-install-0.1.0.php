<?php

$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS mageplaza_betterblog_category;
DROP TABLE IF EXISTS mageplaza_betterblog_category_store;
DROP TABLE IF EXISTS mageplaza_betterblog_eav_attribute;
DROP TABLE IF EXISTS mageplaza_betterblog_post;
DROP TABLE IF EXISTS mageplaza_betterblog_post_category;
DROP TABLE IF EXISTS mageplaza_betterblog_post_comment;
DROP TABLE IF EXISTS mageplaza_betterblog_post_comment_store;
DROP TABLE IF EXISTS mageplaza_betterblog_post_datetime;
DROP TABLE IF EXISTS mageplaza_betterblog_post_decimal;
DROP TABLE IF EXISTS mageplaza_betterblog_post_int;
DROP TABLE IF EXISTS mageplaza_betterblog_post_tag;
DROP TABLE IF EXISTS mageplaza_betterblog_post_text;
DROP TABLE IF EXISTS mageplaza_betterblog_post_varchar;
DROP TABLE IF EXISTS mageplaza_betterblog_tag;
DROP TABLE IF EXISTS mageplaza_betterblog_tag_store;
DROP TABLE IF EXISTS bronto_common_api;
DROP TABLE IF EXISTS bronto_common_api_errors;
DROP TABLE IF EXISTS bronto_common_api_send_queue;
DROP TABLE IF EXISTS bronto_customer_queue;
DROP TABLE IF EXISTS bronto_email_log;
DROP TABLE IF EXISTS bronto_email_template;
DROP TABLE IF EXISTS bronto_emailcapture_queue;
DROP TABLE IF EXISTS bronto_news_item;
DROP TABLE IF EXISTS bronto_newsletter_queue;
DROP TABLE IF EXISTS bronto_order_queue;
DROP TABLE IF EXISTS bronto_product_recommendation;
DROP TABLE IF EXISTS bronto_reminder_delivery_log;
DROP TABLE IF EXISTS bronto_reminder_message;
DROP TABLE IF EXISTS bronto_reminder_rule;
DROP TABLE IF EXISTS bronto_reminder_rule_coupon;
DROP TABLE IF EXISTS bronto_reminder_rule_log;
DROP TABLE IF EXISTS bronto_reminder_rule_website;
DROP TABLE IF EXISTS bronto_reviews_log;
DROP TABLE IF EXISTS bronto_reviews_post_purchase;
DROP TABLE IF EXISTS aoe_profiler_run;
");


$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$sql = "delete from core_resource where code='bronto_common_setup'; "
        . "delete from core_resource where code='bronto_customer_setup'; "
        . "delete from core_resource where code='bronto_emailcapture_setup'; "
        . "delete from core_resource where code='bronto_email_setup'; "
        . "delete from core_resource where code='bronto_newsletter_setup';"
        . "delete from core_resource where code='bronto_news_setup'; "
        . "delete from core_resource where code='bronto_order_setup'; "
        . "delete from core_resource where code='bronto_product_setup'; "
        . "delete from core_resource where code='bronto_reminder_setup';"
        . "delete from core_resource where code='bronto_reviews_setup'; "
        . "delete from core_resource where code='mageplaza_betterblog_setup'; "
        . "delete from core_resource where code='aoe_profiler_setup'; ";
$write->exec($sql);

$sql = "delete from core_config_data where path like 'mageplaza_betterblog/%'; "
        . "delete from core_config_data where path='advanced/modules_disable_output/Mageplaza_BetterBlog'; ";
$write->exec($sql);

$installer->endSetup();
