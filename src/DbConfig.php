<?php
namespace IsThereAnyDeal\Database;

use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;

final class DbConfig
{

    private object $config;

    private function getSchema(): Schema {
        return Expect::structure([
            "driver" => Expect::anyOf("mysqli")->required(),
            "host" => Expect::string("localhost")->required(),
            "port" => Expect::int()->required(),
            "password" => Expect::string()->required()->before(fn($v) => base64_decode($v)),
            "database" => Expect::string()->required(),
            "user" => Expect::string()->required(),
            "user_custom" => Expect::bool(false),
            "user_prefix" => Expect::string(),
            "profiler" => Expect::bool(false),
        ]);
    }

    /**
     * @param array{
     *     driver: string,
     *     host: string,
     *     port: int,
     *     password: string,
     *     database: string,
     *     user: string,
     *     user_custom: bool,
     *     user_prefix: string,
     *     profiler: bool
     * } $config
     * */
    public function __construct(array $config) {
         // @phpstan-ignore-next-line
         $this->config = (new Processor())
            ->process($this->getSchema(), $config);
    }

    public function getDriver(): string {
        return $this->config->driver;
    }

    public function getHost(): string {
        return $this->config->host;
    }

    public function getPort(): int {
        return $this->config->port;
    }

    public function getPassword(): string {
        return $this->config->password;
    }

    public function getDatabase(): string {
        return $this->config->database;
    }

    public function getUser(): string {
        return $this->config->user;
    }

    public function useCustomUsers(): bool {
        return $this->config->user_custom;
    }

    public function getUserPrefix(): string {
        return $this->config->user_prefix;
    }

    public function isProfile(): bool {
        return $this->config->profiler;
    }
}
