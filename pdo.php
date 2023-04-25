<?php
include("config.php");
if (!(isset($host) && isset($db) && isset($user) && isset($pass) && isset($port) && isset($charset) && isset($email_sender) && isset($email_recipient))) {
    echo "ERROR: Configuration variables not found!";
    exit();
}


/**
 * Get the PDO object
 *
 * @return \PDO
 */
function getPDO()
{
    global $host;
    global $db;
    global $charset;
    global $port;
    global $user;
    global $pass;

    $options = [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset;port=$port";
    $pdo = null;
    try {
        $pdo = new \PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
    return $pdo;
}
