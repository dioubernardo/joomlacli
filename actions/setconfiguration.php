<?php

$jsonConfig = $actionArgs[0] ?? null;

if (!$jsonConfig) {
    echo "Erro: Informe o JSON com as configuracoes a serem atualizadas.\n";
    echo "Uso: joomlacli <caminho_joomla> setconfiguration '<json_config>'\n";
    exit(1);
}

// 1. Valida e decodifica o JSON recebido
$newConfig = json_decode($jsonConfig, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($newConfig)) {
    echo "Erro: O argumento fornecido nao e um JSON valido.\n";
    echo "Detalhe: " . json_last_error_msg() . "\n";
    exit(1);
}

// 2. Carrega a configuracao atual do Joomla
$config = JFactory::getConfig();

// 3. Atualiza as chaves passadas no JSON
foreach ($newConfig as $key => $value) {
    $config->set($key, $value);
}

// 4. Prepara o conteudo para salvar no configuration.php
$configClass = $config->toString('PHP', ['class' => 'JConfig']);

// Localiza o caminho do arquivo configuration.php
$configFile = JPATH_CONFIGURATION . '/configuration.php';

// Garante permissao de escrita no arquivo se necessario
if (file_exists($configFile) && !is_writable($configFile)) {
    @chmod($configFile, 0644);
}

// 5. Escreve a nova configuracao no arquivo
if (file_put_contents($configFile, $configClass) === false) {
    echo "Erro: Nao foi possivel escrever no arquivo '{$configFile}'. Verifique as permissoes.\n";
    exit(1);
}

echo "OK: Configuracoes globais do Joomla atualizadas com sucesso!\n";

// Limpa cache global
try {
    $cache = JFactory::getCache();
    $cache->clean('_system');
} catch (\Throwable $e) {
    // Ignora erros ao limpar cache
}

exit(0);