<?php
namespace IsThereAnyDeal\Database;

use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;

final class DbConfig
{

    /**
     * @var object{
     *     driver: string,
     *     host: string,
     *     port: int,
     *     database: string,
     *     credentials: array<string, object{
     *         user: string,
     *         password: string
     *     }>
     * } $config
     */
    private object $config;

    private function getSchema(): Schema {
        return Expect::structure([
            "driver" => Expect::anyOf("mysqli")->required(),
            "host" => Expect::string("localhost")->required(),
            "port" => Expect::int()->required(),
            "database" => Expect::string()->required(),
            "credentials" => Expect::arrayOf(
                Expect::structure([
                    "user" => Expect::string()->required(),
                    "password" => Expect::string()->required()->before(fn($v) => base64_decode($v))
                ]),
                Expect::string()->required()
            )
        ]);
    }

    public function __construct(mixed $config) {
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

    public function getDatabase(): string {
        return $this->config->database;
    }

    public function getCredentials(string $key): Credentials {
        if (!isset($this->config->credentials[$key])) {
            throw new \InvalidArgumentException();
        }
        $credentials = $this->config->credentials[$key];
        return new Credentials($credentials->user, $credentials->password);
    }
}
