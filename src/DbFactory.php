<?php
namespace IsThereAnyDeal\Database;

use \PDO;

class DbFactory {

    public function getDatabase(DbConfig $config, string $credentialsKey): DbDriver {
        $credentials = $config->getCredentials($credentialsKey);
        return new DbDriver(new PDO(
            "mysql:host=".$config->getHost().";port=".$config->getPort().";dbname=".$config->getDatabase(),
            $credentials->user,
            $credentials->password
        ));
    }
}
