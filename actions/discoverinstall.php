<?php

$targetPath = trim(ltrim($actionArgs[0] ?? '', '/'), '/');

if (!$targetPath) {
    echo "Erro: Informe o caminho relativo da extensao.\n";
    echo "Uso: joomlacli <caminho_joomla> discoverinstall <path_relativo>\n";
    echo "Exemplo: joomlacli /var/www/html discoverinstall administrator/components/com_contactformview\n";
    exit(1);
}

// Carregar dependências do instalador
JLoader::import('joomla.installer.installer');
JLoader::import('joomla.installer.helper');

$db = JFactory::getDbo();

/**
 * Retorna os caminhos possíveis correspondentes à extensão
 */
$getExtensionPaths = function ($ext) {
    $paths   = array();
    $element = strtolower($ext->element ?? '');
    $folder  = strtolower($ext->folder ?? '');
    $type    = strtolower($ext->type ?? '');
    $client  = (int) ($ext->client_id ?? 0);

    switch ($type) {
        case 'plugin':
            $paths[] = "plugins/{$folder}/{$element}";
            break;

        case 'component':
            // Componentes ficam em administrator/components (onde reside o XML) e/ou components
            $paths[] = "administrator/components/{$element}";
            $paths[] = "components/{$element}";
            break;

        case 'module':
            if ($client === 1) {
                $paths[] = "administrator/modules/{$element}";
            } else {
                $paths[] = "modules/{$element}";
            }
            break;

        case 'template':
            if ($client === 1) {
                $paths[] = "administrator/templates/{$element}";
            } else {
                $paths[] = "templates/{$element}";
            }
            break;

        default:
            $paths[] = $element;
            break;
    }

    return $paths;
};

$matchesPath = function ($ext, $target) use ($getExtensionPaths) {
    $targetNorm = strtolower($target);
    $paths = $getExtensionPaths($ext);

    foreach ($paths as $p) {
        if (strtolower($p) === $targetNorm) {
            return true;
        }
    }

    return false;
};

// 1. Verifica se já está registrada no banco
$query = $db->getQuery(true)
    ->select($db->quoteName(array('extension_id', 'element', 'folder', 'client_id', 'type', 'name', 'state')))
    ->from($db->quoteName('#__extensions'));
$db->setQuery($query);
$allExtensions = $db->loadObjectList();

$existing = null;
foreach ($allExtensions as $ext) {
    if ($matchesPath($ext, $targetPath)) {
        $existing = $ext;
        break;
    }
}

// Já instalada normalmente
if ($existing && (int) $existing->state === 0) {
    echo "OK: Extensao '$targetPath' ja esta instalada e registrada no Joomla.\n";
    exit(0);
}

$installer = JInstaller::getInstance();

// 2. Se não estiver no banco ou não estiver com state -1, busca no sistema de arquivos
$eid = null;
$extName = null;
$extType = null;

if ($existing && (int) $existing->state === -1) {
    $eid = (int) $existing->extension_id;
    $extName = $existing->name;
    $extType = $existing->type;
} else {
    $discovered = $installer->discover();
    $selected = null;

    foreach ($discovered as $ext) {
        if ($matchesPath($ext, $targetPath)) {
            $selected = $ext;
            break;
        }
    }

    if (!$selected) {
        echo "Erro: Nenhuma extensao encontrada no caminho '$targetPath'.\n";
        exit(1);
    }

    $selected->check();
    $selected->store();
    $eid = (int) $selected->extension_id;
    $extName = $selected->name;
    $extType = $selected->type;
}

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

// 3. Executa a instalação oficial da extensão descoberta
$installed = false;
try {
    $installed = $installer->discover_install($eid);
} catch (\Throwable $e) {
    $errors[] = $e->getMessage();
}

// 4. Exibição do resultado
if ($installed) {
    echo "OK: Extensao '$extName' ($extType) em '$targetPath' instalada com sucesso!\n";
    if (!empty($installer->message)) {
        echo strip_tags($installer->message) . "\n";
    }

    $exitcode = 0;
} else {
    echo "Erro: Falha ao instalar a extensao em '$targetPath'.\n";

    if (!empty($errors)) {
        foreach (array_unique($errors) as $err) {
            echo "Detalhe: $err\n";
        }
    }

    if (!empty($installer->message)) {
        echo "Detalhe: " . strip_tags($installer->message) . "\n";
    }
    $exitcode = 1;
}

// Limpa cache pós-instalação
try {
    $cache = JFactory::getCache();
    $cache->clean('com_plugins');
    $cache->clean('_system');
} catch (\Throwable $e) {
    // Silencia erros de limpeza de cache em CLI
}

exit($exitcode);