<?php
namespace IsThereAnyDeal\Database;

use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;

final class DbConfig
{
    private string $driver;
    private string $host;
    private int $port;
    private string $password;
    private string $database;
    private string $defaultUser;
    private bool $useCustomUsers;
    private bool $useUserGroups;
    private string $userPrefix;
    private bool $profile;

    private function getSchema(): Schema {
        return Expect::structure([
            "driver" => Expect::anyOf("mysqli")->required(),
            "host" => Expect::string("localhost")->required(),
            "port" => Expect::int()->required(),
            "password" => Expect::string()->required()->before(fn($v) => base64_decode($v)),
            "database" => Expect::string()->required(),
            "user" => Expect::string()->required(),
            "user_custom" => Expect::bool()->required(),
            "user_group" => Expect::bool()->required(),
            "user_prefix" => Expect::string()->required(),
            "profiler" => Expect::bool()->required(),
        ]);
    }

    public function __construct(array $config) {
        $data = (new Processor())->process($this->getSchema(), $config);
        $this->driver = $data['driver'];
        $this->host = $data['host'];
        $this->port = $data['port'];
        $this->password = base64_decode($data['password']);
        $this->database = $data['database'];
        $this->defaultUser = $data['user'];
        $this->useCustomUsers = $data['user_custom'];
        $this->useUserGroups = $data['user_group'];
        $this->userPrefix = $data['user_prefix'];
        $this->profile = $data['profiler'];
    }

    public function getDriver(): string {
        return $this->driver;
    }

    public function getHost(): string {
        return $this->host;
    }

    public function getPort(): int {
        return $this->port;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function getDatabase(): string {
        return $this->database;
    }

    public function getDefaultUser(): string {
        return $this->defaultUser;
    }

    public function useCustomUsers(): bool {
        return $this->useCustomUsers;
    }

    public function useUserGroups(): bool {
        return $this->useUserGroups;
    }

    public function getUserPrefix(): string {
        return $this->userPrefix;
    }

    public function isProfile(): bool {
        return $this->profile;
    }
}
