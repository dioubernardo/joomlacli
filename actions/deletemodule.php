<?php

$title    = $actionArgs[0] ?? null;
$clientId = $actionArgs[1] ?? null;

if ($title === null || $clientId === null || !in_array((string)$clientId, ['0', '1'], true)) {
    echo "Erro: Informe o titulo e o client_id (0 para site, 1 para admin).\n";
    echo "Uso: joomlacli <caminho_joomla> removemodule \"<titulo_modulo>\" <client_id>\n";
    exit(1);
}

$clientId = (int) $clientId;
$tipoClient = (($clientId == 1) ? 'Admin' : 'Site');

$db = JFactory::getDbo();

// 1. Busca os módulos com o título e client_id especificados
$query = $db->getQuery(true)
    ->select('id, title, module, client_id')
    ->from($db->quoteName('#__modules'))
    ->where($db->quoteName('title') . ' = ' . $db->quote($title))
    ->where($db->quoteName('client_id') . ' = ' . $clientId);

$db->setQuery($query);
$modules = $db->loadObjectList();

if (empty($modules)) {
    echo "Erro: Modulo '$title' ($tipoClient) nao encontrado.\n";
    exit(1);
}

JLoader::import('joomla.database.table');

foreach ($modules as $module) {
    // 2. Remove as ligacoes de exibicao do modulo nas paginas/menus (#__modules_menu)
    $queryDeleteMap = $db->getQuery(true)
        ->delete($db->quoteName('#__modules_menu'))
        ->where($db->quoteName('moduleid') . ' = ' . (int) $module->id);

    $db->setQuery($queryDeleteMap);

    try {
        $db->execute();
    } catch (\Throwable $e) {
        echo "Erro: Falha ao remover associacoes de menu do modulo '{$module->title}' (ID: {$module->id}).\n";
        if ($e->getMessage()) {
            echo "Detalhe: " . $e->getMessage() . "\n";
        }
        exit(1);
    }

    // 3. Remove o registro do modulo da tabela #__modules
    $table = JTable::getInstance('module');

    if (!$table->load($module->id)) {
        echo "Erro: Nao foi possivel carregar o registro do modulo ID {$module->id}.\n";
        exit(1);
    }

    if (!$table->delete()) {
        echo "Erro: Falha ao remover o modulo '{$module->title}' (ID: {$module->id}).\n";
        if ($table->getError()) {
            echo "Detalhe: " . $table->getError() . "\n";
        }
        exit(1);
    }

    echo "OK: Modulo '{$module->title}' (ID: {$module->id}, {$tipoClient}) removido com sucesso!\n";
}

// 4. Limpa o cache relativo aos modulos
try {
    $cache = JFactory::getCache();
    $cache->clean('com_modules');
    $cache->clean('_system');
} catch (\Throwable $e) {
    // Ignora erros ao limpar cache
}

exit(0);