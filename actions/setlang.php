<?php

$langCode = $actionArgs[0] ?? null;

if (!$langCode) {
    echo "Erro: Informe a tag do idioma (ex: pt-BR, en-GB).\n";
    echo "Uso: joomlacli <caminho_joomla> setlang <lang_code>\n";
    exit(1);
}

// Garante o formato correto do código de idioma (ex: pt-BR)
$parts = explode('-', $langCode);
if (count($parts) === 2) {
    $langCode = strtolower($parts[0]) . '-' . strtoupper($parts[1]);
}

$db = JFactory::getDbo();

// 1. Busca a extensao com_languages na tabela #__extensions
$query = $db->getQuery(true)
    ->select('extension_id, name, params')
    ->from($db->quoteName('#__extensions'))
    ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
    ->where($db->quoteName('element') . ' = ' . $db->quote('com_languages'));

$db->setQuery($query);
$component = $db->loadObject();

if (!$component) {
    echo "Erro: Componente 'com_languages' nao encontrado no banco de dados.\n";
    exit(1);
}

// 2. Decodifica os parametros do com_languages
$params = json_decode($component->params ?? '{}', true);

if (!is_array($params)) {
    $params = [];
}

// 3. Atualiza as chaves de idioma padrao do frontend (site) e backend (administrator)
$params['site']          = $langCode;
$params['administrator'] = $langCode;

$finalParamsJson = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// 4. Salva as alteracoes via JTable
JLoader::import('joomla.database.table');
$table = JTable::getInstance('extension');

if (!$table->load($component->extension_id)) {
    echo "Erro: Nao foi possivel carregar a extensao com_languages (ID: {$component->extension_id}).\n";
    exit(1);
}

$table->params = $finalParamsJson;

if (!$table->store()) {
    echo "Erro: Falha ao atualizar o idioma padrao no com_languages.\n";
    if ($table->getError()) {
        echo "Detalhe: " . $table->getError() . "\n";
    }
    exit(1);
}

echo "OK: Idioma padrao do Site e do Administrator definido para '{$langCode}' com sucesso!\n";

// 5. Limpa caches de idioma e do sistema
try {
    $cache = JFactory::getCache();
    $cache->clean('com_languages');
    $cache->clean('_system');
} catch (\Throwable $e) {
    // Ignora erros ao limpar cache
}

exit(0);