<?php

$name = $actionArgs[0] ?? null;

if (!$name) {
    echo "Erro: Informe o nome da extensao a ser desinstalada.\n";
    echo "Uso: joomlacli <caminho_joomla> uninstall <nome_extensao>\n";
    exit(1);
}

// Carregar classes necessárias do instalador
JLoader::import('joomla.installer.installer');
JLoader::import('joomla.installer.helper');

// Coletor de erros/mensagens do JLog
$errors = array();
JLog::addLogger(
    array(
        'logger'   => 'callback',
        'callback' => function ($entry) use (&$errors) {
            $errors[] = strip_tags($entry->message);
        }
    ),
    JLog::ALL,
    array('jerror', 'installer')
);

// Consulta extensões no banco pelo campo name
$db = JFactory::getDbo();
$query = $db->getQuery(true)
    ->select('extension_id, name, type, element, folder, client_id')
    ->from($db->quoteName('#__extensions'))
    ->where($db->quoteName('name') . ' = ' . $db->quote($name));

$db->setQuery($query);
$extensions = $db->loadObjectList();

if (empty($extensions)) {
    echo "Erro: Nenhuma extensao encontrada com o nome '$name'.\n";
    exit(1);
}

$installer = JInstaller::getInstance();
$allSuccess = true;

// Laço para desinstalar cada extensão encontrada (trata repetições de client_id 0 e 1)
foreach ($extensions as $ext) {
    $clientLabel = ($ext->client_id == 1) ? 'Administrator' : 'Site';
    echo "Desinstalando {$ext->type} '{$ext->name}' (ID: {$ext->extension_id}, Client: {$clientLabel})...\n";

    $errors = array();
    $result = false;

    try {
        $result = $installer->uninstall($ext->type, (int) $ext->extension_id);
    } catch (\Throwable $e) {
        $errors[] = $e->getMessage();
    }

    if ($result) {
        echo "OK: {$ext->type} '{$ext->name}' (ID: {$ext->extension_id}) desinstalado com sucesso!\n";
    } else {
        $allSuccess = false;
        echo "Erro: Falha ao desinstalar {$ext->type} '{$ext->name}' (ID: {$ext->extension_id}).\n";

        if (!empty($errors)) {
            foreach (array_unique($errors) as $err) {
                echo "Detalhe: $err\n";
            }
        }

        if (!empty($installer->message)) {
            echo "Detalhe: " . strip_tags($installer->message) . "\n";
        }
    }
}

// Limpeza de caches do sistema
try {
    $cache = JFactory::getCache();
    $cache->clean('_system');
    $cache->clean('com_modules');
    $cache->clean('com_plugins');
} catch (\Throwable $e) {
    // Ignora erros ao limpar cache
}

exit($allSuccess ? 0 : 1);
