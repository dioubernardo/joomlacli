<?php

$actionName = $actionArgs[0] ?? null; // Ex: core.login.admin
$groupId    = $actionArgs[1] ?? null; // Ex: 7
$value      = $actionArgs[2] ?? null; // Ex: 1, 0, ou "" (vazio)

if (!$actionName || $groupId === null) {
    echo "Erro: Informe o nome da regra (action) e o ID do grupo.\n";
    echo "Uso: joomlacli <caminho_joomla> setpermission <regra> <id_grupo> [valor]\n";
    echo "Valores permitidos: 1 (Permitir/true), 0 (Negar/false), \"\" (Vazio para Herdar/Remover)\n";
    exit(1);
}

$groupId = (int) $groupId;

// Valida o valor recebido
if ($value !== null && $value !== '' && !in_array((string)$value, ['0', '1', 'true', 'false'], true)) {
    echo "Erro: Valor invalido. Use 1 (true) para Permitir, 0 (false) para Negar ou deixe vazio (\"\") para Herdar.\n";
    exit(1);
}

$db = JFactory::getDbo();

// 1. Busca o asset raiz global do Joomla ('root.1')
$query = $db->getQuery(true)
    ->select('id, rules')
    ->from($db->quoteName('#__assets'))
    ->where($db->quoteName('name') . ' = ' . $db->quote('root.1'));

$db->setQuery($query);
$asset = $db->loadObject();

if (!$asset) {
    echo "Erro: Asset raiz 'root.1' nao encontrado na tabela #__assets.\n";
    exit(1);
}

// 2. Decodifica a estrutura de regras JSON atual
$rules = json_decode($asset->rules ?? '{}', true);

if (!is_array($rules)) {
    $rules = [];
}

// 3. Modifica a regra de acordo com o valor especificado
if ($value === null || $value === '') {
    // Se for vazio, remove a definicao do grupo para a regra funcionar via heranca
    if (isset($rules[$actionName][(string)$groupId])) {
        unset($rules[$actionName][(string)$groupId]);

        // Se a acao ficou sem nenhum grupo configurado, remove a chave da acao
        if (empty($rules[$actionName])) {
            unset($rules[$actionName]);
        }
    }
    $statusText = "removida (definida para herdar)";
} else {
    // Converte para booleano real (true = 1, false = 0)
    $boolValue = in_array((string)$value, ['1', 'true'], true);

    if (!isset($rules[$actionName]) || !is_array($rules[$actionName])) {
        $rules[$actionName] = [];
    }

    // O Joomla grava as chaves dos grupos como string e os valores como booleanos (1 / true, 0 / false)
    $rules[$actionName][(string)$groupId] = $boolValue ? 1 : 0;
    $statusText = $boolValue ? "definida para PERMITIR (1)" : "definida para NEGAR (0)";
}

$finalRulesJson = json_encode($rules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// 4. Atualiza o registro do asset raiz
JLoader::import('joomla.database.table');
$table = JTable::getInstance('asset');

if (!$table->load($asset->id)) {
    echo "Erro: Nao foi possivel carregar o registro do asset root.1 (ID: {$asset->id}).\n";
    exit(1);
}

$table->rules = $finalRulesJson;

if (!$table->store()) {
    echo "Erro: Falha ao salvar as novas permissoes no asset raiz.\n";
    if ($table->getError()) {
        echo "Detalhe: " . $table->getError() . "\n";
    }
    exit(1);
}

echo "OK: Permissao '{$actionName}' para o Grupo ID {$groupId} foi {$statusText} com sucesso!\n";

// 5. Limpa os caches da ACL
try {
    JAccess::cleanCache();
    $cache = JFactory::getCache();
    $cache->clean('_system');
} catch (\Throwable $e) {
    // Ignora erros ao limpar cache
}

exit(0);