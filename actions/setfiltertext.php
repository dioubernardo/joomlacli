<?php

$groupId    = $actionArgs[0] ?? null;
$filterType = $actionArgs[1] ?? null;

if ($groupId === null || !$filterType) {
    echo "Erro: Informe o ID do grupo de usuarios e o tipo de filtro.\n";
    echo "Uso: joomlacli <caminho_joomla> setfiltertext <id_grupo> <tipo_filtro>\n";
    echo "Tipos validos: NONE (Sem filtragem), NH (Sem HTML), CBL (Lista negra customizada), BL (Lista negra), INT (Apenas texto), DEFAULT (Padrao)\n";
    exit(1);
}

$groupId    = (string) $groupId;
$filterType = strtoupper((string) $filterType);

$validTypes = ['NONE', 'NH', 'CBL', 'BL', 'INT', 'DEFAULT'];
if (!in_array($filterType, $validTypes, true)) {
    echo "Erro: Tipo de filtro '$filterType' invalido.\n";
    echo "Opcoes permitidas: " . implode(', ', $validTypes) . "\n";
    exit(1);
}

$db = JFactory::getDbo();

// 1. O Text Filter fica salvo nas configuracoes globais (com_config)
$query = $db->getQuery(true)
    ->select('extension_id, name, params')
    ->from($db->quoteName('#__extensions'))
    ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
    ->where($db->quoteName('element') . ' = ' . $db->quote('com_config'));

$db->setQuery($query);
$component = $db->loadObject();

if (!$component) {
    echo "Erro: Componente 'com_config' nao encontrado no banco de dados.\n";
    exit(1);
}

// 2. Decodifica os parametros do com_config
$params = json_decode($component->params ?? '{}', true);

if (!is_array($params)) {
    $params = [];
}

// Garante que a estrutura de 'filters' existe
if (!isset($params['filters']) || !is_array($params['filters'])) {
    $params['filters'] = [];
}

// 3. Atualiza ou cria a regra para o ID do grupo informado
$existingGroupFilter = $params['filters'][$groupId] ?? [];

$params['filters'][$groupId] = [
    'filter_type'       => $filterType,
    'filter_tags'       => $existingGroupFilter['filter_tags'] ?? '',
    'filter_attributes' => $existingGroupFilter['filter_attributes'] ?? ''
];

$finalParamsJson = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// 4. Salva os parametros na tabela #__extensions
JLoader::import('joomla.database.table');
$table = JTable::getInstance('extension');

if (!$table->load($component->extension_id)) {
    echo "Erro: Nao foi possivel carregar o componente com_config (ID: {$component->extension_id}).\n";
    exit(1);
}

$table->params = $finalParamsJson;

if (!$table->store()) {
    echo "Erro: Falha ao atualizar a filtragem de texto no com_config.\n";
    if ($table->getError()) {
        echo "Detalhe: " . $table->getError() . "\n";
    }
    exit(1);
}

echo "OK: Filtro de texto do com_config para o Grupo ID {$groupId} definido para '{$filterType}' com sucesso!\n";

// 5. Limpa caches globais de configuracao
try {
    $cache = JFactory::getCache();
    $cache->clean('com_config');
    $cache->clean('_system');
} catch (\Throwable $e) {
    // Ignora erros ao limpar cache
}

exit(0);