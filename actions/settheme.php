<?php

$templateName = $actionArgs[0] ?? null;

if (!$templateName) {
    echo "Erro: Informe o nome do template/tema a ser definido como padrao.\n";
    echo "Uso: joomlacli <caminho_joomla> settheme <nome_template>\n";
    exit(1);
}

$db = JFactory::getDbo();

// 1. Localiza o estilo correspondente ao template para o site (client_id = 0)
$query = $db->getQuery(true)
    ->select('id, template, title')
    ->from($db->quoteName('#__template_styles'))
    ->where($db->quoteName('template') . ' = ' . $db->quote($templateName))
    ->where($db->quoteName('client_id') . ' = 0');

$db->setQuery($query);
$style = $db->loadObject();

if (!$style) {
    echo "Erro: Estilo para o template '$templateName' nao encontrado no site (client_id = 0).\n";
    exit(1);
}

try {
    // 2. Remove o status de padrão (home = '0') de todos os outros temas do site (client_id = 0)
    $queryReset = $db->getQuery(true)
        ->update($db->quoteName('#__template_styles'))
        ->set($db->quoteName('home') . ' = ' . $db->quote('0'))
        ->where($db->quoteName('client_id') . ' = 0');

    $db->setQuery($queryReset);
    $db->execute();

    // 3. Define o template selecionado como o padrão (home = '1')
    $querySetDefault = $db->getQuery(true)
        ->update($db->quoteName('#__template_styles'))
        ->set($db->quoteName('home') . ' = ' . $db->quote('1'))
        ->where($db->quoteName('id') . ' = ' . (int) $style->id);

    $db->setQuery($querySetDefault);
    $db->execute();

    echo "OK: Tema '{$templateName}' (Estilo: '{$style->title}', ID: {$style->id}) definido como padrao do site com sucesso!\n";

} catch (\Throwable $e) {
    echo "Erro: Falha ao atualizar o tema padrao no banco de dados.\n";
    if ($e->getMessage()) {
        echo "Detalhe: " . $e->getMessage() . "\n";
    }
    exit(1);
}

// 4. Limpa os caches de templates e estilos
try {
    $cache = JFactory::getCache();
    $cache->clean('com_templates');
    $cache->clean('_system');
} catch (\Throwable $e) {
    // Ignora erros ao limpar cache
}

exit(0);