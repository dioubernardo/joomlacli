<?php

$name     = $actionArgs[0] ?? null;
$username = $actionArgs[1] ?? null;
$email    = $actionArgs[2] ?? null;
$password = $actionArgs[3] ?? null;
$group    = $actionArgs[4] ?? null; // ID (ex: 8) ou Nome do Grupo (ex: "Super Users")

if (!$name || !$username || !$email || !$password || !$group) {
    echo "Erro: Parametros insuficientes.\n";
    echo "Uso: joomlacli <caminho_joomla> createuser <name> <username> <email> <password> <grupo_id_ou_nome>\n";
    echo "Exemplo: joomlacli /var/www/html createuser 'cgti' 'CGTI' 'cgti.servicos@furg.br' 'S3nh4S3gur4' 8\n";
    exit(1);
}

$db = JFactory::getDbo();

// 1. Resolve o ID do Grupo (se for enviado como texto, busca a chave numerica)
$groupId = null;

if (is_numeric($group)) {
    $groupId = (int) $group;
} else {
    $query = $db->getQuery(true)
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__usergroups'))
        ->where($db->quoteName('title') . ' = ' . $db->quote($group));

    $db->setQuery($query);
    $groupId = (int) $db->loadResult();
}

if (!$groupId) {
    echo "Erro: O grupo '$group' nao foi encontrado no banco de dados.\n";
    exit(1);
}

// 2. Gera o hash BCRYPT compativel com o mecanismo do Joomla 3.10
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

// 3. Verifica se o usuario ja existe no banco para decidir entre INSERT ou UPDATE
$query = $db->getQuery(true)
    ->select($db->quoteName('id'))
    ->from($db->quoteName('#__users'))
    ->where($db->quoteName('username') . ' = ' . $db->quote($username));

$db->setQuery($query);
$userId = (int) $db->loadResult();

if ($userId > 0) {
    echo "Usuario '$username' ja existe (ID: $userId). Atualizando dados...\n";

    $query = $db->getQuery(true)
        ->update($db->quoteName('#__users'))
        ->set($db->quoteName('name') . ' = ' . $db->quote($name))
        ->set($db->quoteName('email') . ' = ' . $db->quote($email))
        ->set($db->quoteName('password') . ' = ' . $db->quote($passwordHash))
        ->set($db->quoteName('block') . ' = 0')
        ->where($db->quoteName('id') . ' = ' . $userId);

    $db->setQuery($query)->execute();
} else {
    echo "Inserindo novo usuario '$username' no banco de dados...\n";

    $now = JFactory::getDate()->toSql();

    $userObject = new stdClass();
    $userObject->name         = $name;
    $userObject->username     = $username;
    $userObject->email        = $email;
    $userObject->password     = $passwordHash;
    $userObject->block        = 0;
    $userObject->sendEmail    = 0;
    $userObject->registerDate = $now;
    $userObject->lastvisitDate= '0000-00-00 00:00:00';
    $userObject->activation   = '';
    $userObject->params       = '';

    $db->insertObject('#__users', $userObject, 'id');
    $userId = (int) $userObject->id;
}

// 4. Mapeia o usuario na tabela #__user_usergroup_map
$query = $db->getQuery(true)
    ->select('COUNT(*)')
    ->from($db->quoteName('#__user_usergroup_map'))
    ->where($db->quoteName('user_id') . ' = ' . $userId)
    ->where($db->quoteName('group_id') . ' = ' . $groupId);

$db->setQuery($query);
$mapExists = (int) $db->loadResult();

if (!$mapExists) {
    $mapObject = new stdClass();
    $mapObject->user_id  = $userId;
    $mapObject->group_id = $groupId;

    $db->insertObject('#__user_usergroup_map', $mapObject);
}

echo "OK: Usuario '$username' (ID: $userId) cadastrado e vinculado ao grupo ID $groupId com sucesso!\n";
exit(0);