<?php
namespace Database;

/* JSON Config:
 {
    "driver": "mysqli",
    "host": "localhost",
    "password": <base64 encoded string>,
    "database": <string>,
    "user": <string>,
    "user_custom": <bool>,
    "user_group": <bool,
    "user_prefix": <string>,
    "profiler": <bool>
 }
 */

use Core\IConfig;

final class DbConfig implements IConfig
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

    public function __construct(array $config) {
        $this->driver = $config['driver'];
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->password = base64_decode($config['password']);
        $this->database = $config['database'];
        $this->defaultUser = $config['user'];
        $this->useCustomUsers = $config['user_custom'];
        $this->useUserGroups = $config['user_group'];
        $this->userPrefix = $config['user_prefix'];
        $this->profile = $config['profiler'];
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
