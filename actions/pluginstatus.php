<?php

$name   = isset($actionArgs[0]) ? trim((string) $actionArgs[0]) : null;
$status = isset($actionArgs[1]) ? trim((string) $actionArgs[1]) : null;

if (!$name || ($status !== '0' && $status !== '1')) {
    echo "Erro: Informe o nome do plugin e o status (0 para desativar ou 1 para ativar).\n";
    echo "Uso: joomlacli <caminho_joomla> pluginstatus <nome_plugin> <0|1>\n";
    exit(1);
}

$targetStatus = (int) $status;
$actionVerb   = $targetStatus === 1 ? 'ativar' : 'desativar';
$actionPast   = $targetStatus === 1 ? 'ativado' : 'desativado';

$frag = explode('_', $name, 3);

$db = JFactory::getDbo();
$query = $db->getQuery(true)
    ->select('extension_id, name, element, folder, enabled')
    ->from($db->quoteName('#__extensions'))
    ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
    ->where($db->quoteName('folder') . ' = ' . $db->quote($frag[1]))
    ->where($db->quoteName('element') . ' = ' . $db->quote($frag[2]));

$db->setQuery($query);
$plugins = $db->loadObjectList();

if (empty($plugins)) {
    echo "Erro: Plugin '$name' nao encontrado.\n";
    exit(1);
}

JLoader::import('joomla.database.table');
$table = JTable::getInstance('extension');

foreach ($plugins as $plugin) {
    if ((int) $plugin->enabled === $targetStatus) {
        echo "OK: Plugin '{$plugin->name}' ({$plugin->folder}/{$plugin->element}) ja esta {$actionPast}.\n";
        continue;
    }

    $table->load($plugin->extension_id);
    $table->enabled = $targetStatus;

    if (!$table->store()) {
        echo "Erro: Falha ao {$actionVerb} o plugin '{$plugin->name}'.\n";
        if ($table->getError()) {
            echo "Detalhe: " . $table->getError() . "\n";
        }
        exit(1);
    }

    echo "OK: Plugin '{$plugin->name}' ({$plugin->folder}/{$plugin->element}) {$actionPast} com sucesso!\n";
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