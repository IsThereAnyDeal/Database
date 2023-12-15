<?php
namespace IsThereAnyDeal\Database;

use \PDO;

class DbFactory {

    private static ?DbFactory $instance = null;

    private static function getInstance(): DbFactory {
        if (is_null(self::$instance)) {
            self::$instance = new DbFactory();
        }
        return self::$instance;
    }

    private function __construct() {
        // singleton factory
    }

    /**
     * @var array<string, DbDriver>
     */
    private array $connections = [];

    public static function getDatabase(DbConfig $config, ?string $user=null): DbDriver {
        $inst = self::getInstance();

        $username = is_null($user) || !$config->useCustomUsers()
            ? $config->getUser()
            : $config->getUserPrefix().$user;

        if (!isset($inst->connections[$username])) {
            $inst->connections[$username] = new DbDriver(new PDO(
                "mysql:host=".$config->getHost().";port=".$config->getPort().";dbname=".$config->getDatabase(),
                $username,
                $config->getPassword()
            ));
        }

        return $inst->connections[$username];
    }
}
