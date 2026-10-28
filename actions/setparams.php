<?php

$name       = $actionArgs[0] ?? null;
$jsonParams = $actionArgs[1] ?? null;

if (!$name || !$jsonParams) {
    echo "Erro: Informe o nome da extensao e o JSON com os parametros.\n";
    echo "Uso: joomlacli <caminho_joomla> setparams <nome_extensao> '<json_parametros>'\n";
    exit(1);
}

// Valida e decodifica o JSON de entrada
$newParams = json_decode($jsonParams, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($newParams)) {
    echo "Erro: O argumento fornecido nao e um JSON valido.\n";
    echo "Detalhe: " . json_last_error_msg() . "\n";
    exit(1);
}

$db = JFactory::getDbo();

// 1. Busca as extensoes correspondentes pelo campo 'name'
$query = $db->getQuery(true)
    ->select('extension_id, name, type, element, folder, params')
    ->from($db->quoteName('#__extensions'))
    ->where($db->quoteName('name') . ' = ' . $db->quote($name));

$db->setQuery($query);
$extensions = $db->loadObjectList();

if (empty($extensions)) {
    echo "Erro: Extensao '$name' nao encontrada.\n";
    exit(1);
}

JLoader::import('joomla.database.table');
$table = JTable::getInstance('extension');

foreach ($extensions as $extension) {
    // Decodifica os parametros existentes no banco (se vazio, assume array vazio)
    $currentParams = json_decode($extension->params ?? '{}', true);

    if (!is_array($currentParams)) {
        $currentParams = [];
    }

    // Atualiza/combina mantendo as chaves antigas e substituindo apenas as enviadas
    $mergedParams = mergeParamsSmart($currentParams, $newParams);
    $finalParamsJson = json_encode($mergedParams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Carrega o registro da tabela para alteracao
    if (!$table->load($extension->extension_id)) {
        echo "Erro: Nao foi possivel carregar a extensao ID {$extension->extension_id}.\n";
        exit(1);
    }

    $table->params = $finalParamsJson;

    if (!$table->store()) {
        echo "Erro: Falha ao atualizar parametros da extensao '{$extension->name}'.\n";
        if ($table->getError()) {
            echo "Detalhe: " . $table->getError() . "\n";
        }
        exit(1);
    }

    echo "OK: Parametros da extensao '{$extension->name}' ({$extension->type}) atualizados com sucesso!\n";
}

// Limpa caches globais que guardam configuracoes de extensoes
try {
    $cache = JFactory::getCache();
    $cache->clean('com_plugins');
    $cache->clean('com_modules');
    $cache->clean('_system');
} catch (\Throwable $e) {
    // Ignora erros ao limpar cache
}

exit(0);

function mergeParamsSmart(array $current, array $new): array
{
    foreach ($new as $key => $value) {
        // Se o valor enviado for null, remove a chave do array de destino
        if ($value === null) {
            unset($current[$key]);
            continue;
        }

        if (is_array($value) && isset($current[$key]) && is_array($current[$key])) {
            // Se for um array indexado numericamente (ex: lista de botoes ["bold", "italic"])
            if (array_keys($value) === range(0, count($value) - 1)) {
                $current[$key] = $value; // Substitui completamente
            } else {
                // Se for um array associativo (ex: {"toolbar1": [...], "menu": [...]})
                $current[$key] = mergeParamsSmart($current[$key], $value);
            }
        } else {
            $current[$key] = $value;
        }
    }

    return $current;
}