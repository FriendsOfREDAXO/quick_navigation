<?php
$sql = rex_sql::factory();
$sql->setQuery('SELECT * FROM rex_config WHERE namespace = :namespace', ['namespace' => 'quick_navigation']);

foreach ($sql as $row) {
    $key = $row->getValue('key');

    // Wenn der key mit quicknavi_ beginnt, ersetze es durch quick_navigation_
    if (strpos($key, 'quicknavi_') === 0) {
        $newKey = str_replace('quicknavi_', 'quick_navigation_', $key);

        // Update den key in der Tabelle
        $updateSql = rex_sql::factory();
        $updateSql->setTable('rex_config');
        $updateSql->setWhere(['namespace' => 'quick_navigation', 'key' => $key]);
        $updateSql->setValue('key', $newKey);
        $updateSql->update();
    }
}
