<?php

$args = array_slice($argv, 1);
$joomlaPath = $args[0] ?? null;
$actionName = $args[1] ?? null;

if (!$joomlaPath || !$actionName) {
    echo "Uso: joomlacli <caminho_joomla> <action> [argumentos...]\n";
    echo "Exemplo: joomlacli /var/www/html install-url https://exemplo.com/extensao.zip\n";
    exit(1);
}

$realJoomlaPath = realpath($joomlaPath);

if (!$realJoomlaPath || !is_dir($realJoomlaPath) || !file_exists($realJoomlaPath . '/includes/framework.php')) {
    fwrite(STDERR, "Erro: O diretorio '$joomlaPath' nao e uma instalacao valida do Joomla 3.\n");
    exit(1);
}

$actionFile = __DIR__ . '/actions/' . $actionName . '.php';

if (!file_exists($actionFile)) {
    fwrite(STDERR, "Erro: Action '$actionName' nao encontrada em 'actions/$actionName.php'.\n");
    exit(1);
}

# ---

const _JEXEC = 1;

if (file_exists($realJoomlaPath . '/defines.php')) {
    require_once $realJoomlaPath . '/defines.php';
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', $realJoomlaPath);
    require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_LIBRARIES . '/import.legacy.php';
require_once JPATH_LIBRARIES . '/cms.php';
require_once JPATH_CONFIGURATION . '/configuration.php';
require_once JPATH_BASE . '/includes/framework.php';

class joomlaCli extends JApplicationCli {

    // Fila para guardar as mensagens do enqueueMessage
    protected $messageQueue = [];

    public function flushAssets() {}

    public function enqueueMessage($msg, $type = 'message') {
        $this->messageQueue[] = [
            'message' => $msg,
            'type'    => $type
        ];
    }

    public function getMessageQueue() {
        return $this->messageQueue;
    }

    public function getTemplate($params = false) {
        return 'isis';
    }

    public function getClientId() {
        return 1;
    }

    public function getName() {
        return 'administrator';
    }

    public function isClient($name) {
        return $name === 'administrator' || $name === 1 || $name === 'admin';
    }

    public function getUserState($key, $default = null) {
        return $default;
    }

    public function setUserState($key, $value) {
        return $value;
    }

    public function setHeader($name, $value, $replace = false) {
        return $this;
    }

    public function getHeader($name = null) {
        return null;
    }

    public function getMenu() {
        return null;
    }

    public function getRouter() {
        return null;
    }

    public function getParams() {
        return new JRegistry();
    }

    public function doExecute() {
        global $actionFile, $args;
        \JFactory::$application = $this;

        // Inicia a sessao do Joomla antes de qualquer output CLI
        $session = \JFactory::getSession();
        if ($session->getState() !== 'active') {
            $session->start();
        }

        // Eleva para usuario Super Admin ID 1
        $session->set('user', \JUser::getInstance(1));

        // Carrega traducoes do sistema/admin
        $lang = \JFactory::getLanguage();
        $lang->load('lib_joomla', JPATH_ADMINISTRATOR);
        $lang->load('com_installer', JPATH_ADMINISTRATOR);

        $actionArgs = array_slice($args, 2);
        require $actionFile;
    }
}

JApplicationCli::getInstance('joomlaCli')->execute();