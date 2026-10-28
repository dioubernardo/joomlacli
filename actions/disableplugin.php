<?php

$name = $actionArgs[0] ?? null;

if (!$name) {
    echo "Erro: Informe o nome do plugin a ser desativado.\n";
    echo "Uso: joomlacli <caminho_joomla> disableplugin <nome_plugin>\n";
    exit(1);
}

$db = JFactory::getDbo();
$query = $db->getQuery(true)
    ->select('extension_id, name, element, folder, enabled')
    ->from($db->quoteName('#__extensions'))
    ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
    ->where($db->quoteName('name') . ' = ' . $db->quote($name));

$db->setQuery($query);
$plugins = $db->loadObjectList();

if (empty($plugins)) {
    echo "Erro: Plugin '$name' nao encontrado.\n";
    exit(1);
}

JLoader::import('joomla.database.table');
$table = JTable::getInstance('extension');

foreach ($plugins as $plugin) {
    if ((int) $plugin->enabled === 0) {
        echo "OK: Plugin '{$plugin->name}' ({$plugin->folder}/{$plugin->element}) ja esta desativado.\n";
        continue;
    }

    $table->load($plugin->extension_id);
    $table->enabled = 0;

    if (!$table->store()) {
        echo "Erro: Falha ao desativar o plugin '{$plugin->name}'.\n";
        if ($table->getError()) {
            echo "Detalhe: " . $table->getError() . "\n";
        }
        exit(1);
    }

    echo "OK: Plugin '{$plugin->name}' ({$plugin->folder}/{$plugin->element}) desativado com sucesso!\n";
}

// Limpa cache dos plugins
try {
    $cache = JFactory::getCache();
    $cache->clean('com_plugins');
    $cache->clean('_system');
} catch (\Throwable $e) {
    // Ignora erros ao limpar cache
}

exit(0);
