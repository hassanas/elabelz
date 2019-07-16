<?php
/*
 * Author Rudyuk Vitalij Anatolievich
 * Email rvansp@gmail.com
 * Blog www.cervic.info
 */
?>
<?php

$installer = $this;

$installer->startSetup();

$tableName = $installer->getTable('dhllabelconformity');
if ($installer->getConnection()->isTableExists($tableName)) {
    $table = $installer->getConnection();
    $table->changeColumn(
        $tableName,
        'method_id',
        'method_id',
        'VARCHAR(255) NOT NULL DEFAULT ""'
    );
    $table->changeColumn(
        $tableName,
        'dhlmethod_id',
        'dhlmethod_id',
        'VARCHAR(255) NOT NULL DEFAULT ""'
    );
}

$installer->endSetup();