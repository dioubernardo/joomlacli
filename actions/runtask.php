<?php

$option = $actionArgs[0] ?? null; // Ex: com_assistente
$task   = $actionArgs[1] ?? null; // Ex: siteBase

if (!$option || !$task) {
    echo "Erro: Informe o componente e o nome da task a ser executada.\n";
    echo "Uso: joomlacli <caminho_joomla> runtask <componente> <task>\n";
    echo "Exemplo: joomlacli /var/www/html runtask com_assistente siteBase\n";
    exit(1);
}

// Garante o prefixo com_
if (strpos($option, 'com_') !== 0) {
    $option = 'com_' . $option;
}

$app   = JFactory::getApplication();
$input = $app->input;

// Configura parametros de contexto na requisicao
$input->set('option', $option);
$input->set('task', $task);
$_REQUEST['option'] = $option;
$_REQUEST['task']   = $task;

// Define caminhos do componente no Administrator e Frontend
$componentName  = ucfirst(str_replace('com_', '', $option));
$adminPath      = JPATH_ADMINISTRATOR . '/components/' . $option;
$sitePath       = JPATH_SITE . '/components/' . $option;

if (!file_exists($adminPath)) {
    echo "Erro: O diretorio de admin do componente '$option' nao existe em '$adminPath'.\n";
    exit(1);
}

// Define as constantes do componente para o Joomla / Models
if (!defined('JPATH_COMPONENT')) {
    define('JPATH_COMPONENT', $adminPath);
}
if (!defined('JPATH_COMPONENT_ADMINISTRATOR')) {
    define('JPATH_COMPONENT_ADMINISTRATOR', $adminPath);
}
if (!defined('JPATH_COMPONENT_SITE')) {
    define('JPATH_COMPONENT_SITE', $sitePath);
}

// Autoloader e inclusao do controller principal do Admin
JLoader::registerPrefix($componentName, $adminPath);

$controllerFile = $adminPath . '/controller.php';

if (!file_exists($controllerFile)) {
    echo "Erro: O arquivo de controller de admin '$controllerFile' nao foi encontrado.\n";
    exit(1);
}

require_once $controllerFile;

$className = $componentName . 'Controller';

if (!class_exists($className)) {
    echo "Erro: A classe '$className' nao foi encontrada no arquivo '$controllerFile'.\n";
    exit(1);
}

// Instancia o Controller e adiciona a pasta de Models do Admin
$config     = ['base_path' => $adminPath];
$controller = new $className($config);

$modelPath = $adminPath . '/models';
if (file_exists($modelPath)) {
    $controller->addModelPath($modelPath, $componentName . 'Model');
}

// Executa a task no Controller
try {
    echo "Executando task '$task' no componente de Admin '$option'...\n\n";

    $controller->execute($task);

    // TRATAMENTO DAS MENSAGENS ENFILEIRADAS
    $rawMessages = $app->getMessageQueue();
    $messages    = is_array($rawMessages) ? $rawMessages : [];
    $hasError    = false;

    if (!empty($messages)) {
        foreach ($messages as $msg) {
            if (is_array($msg)) {
                $type = strtolower($msg['type'] ?? 'info');
                $text = $msg['message'] ?? '';

                if (in_array($type, ['error', 'err'], true)) {
                    $hasError = true;
                    echo "[ERROR] {$text}\n";
                } else {
                    echo "[" . strtoupper($type) . "] {$text}\n";
                }
            } elseif (is_string($msg)) {
                echo "[INFO] {$msg}\n";
            }
        }
    }

    // Se houve alguma mensagem de erro enfileirada no Joomla, encerra com falha (exit 1)
    if ($hasError) {
        echo "\nErro: A task '$task' foi executada, mas retornou mensagens de erro no Joomla.\n";
        exit(1);
    }

    echo "\nOK: Task '$task' executada com sucesso!\n";
} catch (\Throwable $e) {
    echo "Erro durante a execucao da task '$task': " . $e->getMessage() . "\n";
    if ($e->getTraceAsString()) {
        echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    }
    exit(1);
}

exit(0);