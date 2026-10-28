<?php

$url = $actionArgs[0] ?? null;

if (!$url) {
    echo "Erro: Informe a URL da extensao.\n";
    echo "Uso: joomlacli <caminho_joomla> install-url <url>\n";
    exit(1);
}

// Carregar classes necessarias do instalador
JLoader::import('joomla.installer.installer');
JLoader::import('joomla.installer.helper');

// Coletor de mensagens/erros disparados pelo Joomla (JLog)
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

// 1. Download do pacote
echo "Baixando pacote: $url ...\n";
$package = JInstallerHelper::downloadPackage($url);

if (!$package) {
    echo "Erro: Nao foi possivel baixar o arquivo da URL.\n";
    foreach ($errors as $err) {
        echo "Detalhe: $err\n";
    }
    exit(1);
}

$config = JFactory::getConfig();
$packagePath = $config->get('tmp_path') . '/' . $package;

// 2. Descompactacao do pacote
echo "Descompactando pacote...\n";
$extractDir = JInstallerHelper::unpack($packagePath, true);

if (!$extractDir || empty($extractDir['type']) || empty($extractDir['dir'])) {
    echo "Erro: Nao foi possivel descompactar ou identificar o tipo do pacote Joomla.\n";
    if (isset($extractDir['extractdir'])) {
        JInstallerHelper::cleanupInstall($packagePath, $extractDir['extractdir']);
    } elseif (file_exists($packagePath)) {
        @unlink($packagePath);
    }
    exit(1);
}

// 3. Instalacao
$installer = JInstaller::getInstance();
$installer->setPath('source', $extractDir['dir']);
$installer->setOverwrite(true);
$installer->setUpgrade(true);

$installed = false;
try {
    $installed = $installer->install($extractDir['dir']);
} catch (\Throwable $e) {
    $errors[] = $e->getMessage();
}

// 4. Limpeza dos arquivos temporarios
$packageFile = $extractDir['packagefile'] ?? $packagePath;
$extractDirPath = $extractDir['extractdir'] ?? null;
JInstallerHelper::cleanupInstall($packageFile, $extractDirPath);

if (file_exists($packagePath)) {
    @unlink($packagePath);
}

// 5. Exibicao do resultado
if ($installed) {
    echo "OK: Extensao instalada com sucesso!\n";
    if (!empty($installer->message)) {
        echo strip_tags($installer->message) . "\n";
    }
    exit(0);
} else {
    echo "Erro: Falha ao instalar a extensao.\n";

    if (!empty($errors)) {
        foreach (array_unique($errors) as $err) {
            echo "Detalhe: $err\n";
        }
    }

    if (!empty($installer->message)) {
        echo "Detalhe: " . strip_tags($installer->message) . "\n";
    }

    exit(1);
}
