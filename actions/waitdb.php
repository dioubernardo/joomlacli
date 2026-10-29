<?php

$confFile = JPATH_CONFIGURATION . '/configuration.php';

if (!file_exists($confFile)) {
    echo "Erro: Arquivo de configuracao '$confFile' nao encontrado.\n";
    exit(1);
}

require_once $confFile;

if (!class_exists('JConfig')) {
    echo "Erro: Classe JConfig nao encontrada em '$confFile'.\n";
    exit(1);
}

$config   = new JConfig();

while (true) {
    try {
        $dsn = "mysql:host={$config->host};dbname={$config->db}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2
        ];

        $pdo = new PDO($dsn, $config->user, $config->password, $options);
        $stmt = $pdo->query("SELECT 1");
        if ($stmt && $stmt->fetchColumn() === 1)
            exit(0);
    } catch (\Throwable $e) {
        // Conexao ou banco indisponivel
    }

    echo "Aguardando banco de dados...\n";
    sleep(5);
}