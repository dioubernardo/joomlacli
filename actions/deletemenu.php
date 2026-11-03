<?php

$link     = $actionArgs[0] ?? null;
$clientId = $actionArgs[1] ?? '0'; // Padrão 0 (Site) caso não seja fornecido

if ($link === null) {
    echo "Erro: Informe o link do item de menu.\n";
    echo "Uso: joomlacli <caminho_joomla> removemenuitem \"index.php?option=com_exemplo...\" [client_id]\n";
    exit(1);
}

$clientId = (int) $clientId;
$tipoClient = (($clientId == 1) ? 'Admin' : 'Site');

$db = JFactory::getDbo();

// 1. Busca os itens de menu pelo link e client_id especificados
$query = $db->getQuery(true)
    ->select('id, title, link, client_id')
    ->from($db->quoteName('#__menu'))
    ->where($db->quoteName('link') . ' = ' . $db->quote($link))
    ->where($db->quoteName('client_id') . ' = ' . $clientId);

$db->setQuery($query);
$menuItems = $db->loadObjectList();

if (empty($menuItems)) {
    echo "Erro: Item de menu com o link '$link' ($tipoClient) nao encontrado.\n";
    exit(1);
}

JLoader::import('joomla.database.table');

foreach ($menuItems as $item) {
    // 2. Remove as ligacoes de exibicao de modulos vinculados a este item de menu (#__modules_menu)
    $queryDeleteMap = $db->getQuery(true)
        ->delete($db->quoteName('#__modules_menu'))
        ->where($db->quoteName('menuid') . ' = ' . (int) $item->id);

    $db->setQuery($queryDeleteMap);

    try {
        $db->execute();
    } catch (\Throwable $e) {
        echo "Erro: Falha ao remover associacoes de modulos do item de menu '{$item->title}' (ID: {$item->id}).\n";
        if ($e->getMessage()) {
            echo "Detalhe: " . $e->getMessage() . "\n";
        }
        exit(1);
    }

    // 3. Remove o registro do item de menu via JTable (gerencia reordenacao e arvores de menu)
    $table = JTable::getInstance('Menu', 'JTable');

    if (!$table->load($item->id)) {
        echo "Erro: Nao foi possivel carregar o registro do item de menu ID {$item->id}.\n";
        exit(1);
    }

    if (!$table->delete($item->id)) {
        echo "Erro: Falha ao remover o item de menu '{$item->title}' (ID: {$item->id}).\n";
        if ($table->getError()) {
            echo "Detalhe: " . $table->getError() . "\n";
        }
        exit(1);
    }

    echo "OK: Item de menu '{$item->title}' (ID: {$item->id}, Link: '{$item->link}', {$tipoClient}) removido com sucesso!\n";
}

// 4. Limpa o cache relativo aos menus do Joomla
try {
    $cache = JFactory::getCache('com_menus', '');
    $cache->clean();
} catch (\Throwable $e) {
    // Ignora erros ao limpar cache
}

exit(0);