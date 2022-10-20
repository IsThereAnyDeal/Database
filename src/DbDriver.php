<?php
namespace IsThereAnyDeal\Database;

use IsThereAnyDeal\Database\Data\ObjectBuilder;
use Psr\Log\LoggerInterface;

class DbDriver
{
    private readonly \PDO $db;
    private readonly ObjectBuilder $objectBuilder;
    private ?LoggerInterface $logger = null;
    private bool $profile = false;

    public function __construct(\PDO $db) {
        $this->db = $db;
        $this->objectBuilder = new ObjectBuilder();
    }

    public function getDriver(): \PDO {
        return $this->db;
    }

    public function getObjectBuilder(): ObjectBuilder {
        return $this->objectBuilder;
    }

    public function getLogger(): ?LoggerInterface {
        return $this->logger;
    }

    public function setLogger(?LoggerInterface $logger): void {
        $this->logger = $logger;
    }

    public function begin(): bool {
        return $this->db->beginTransaction();
    }

    public function commit(): bool {
        return $this->db->commit();
    }

    public function rollback(): bool {
        return $this->db->rollBack();
    }

    public function inTransaction(): bool {
        return $this->db->inTransaction();
    }

    public function setIsolationLevel(string $isolationLevel) {
        $this->db->query("SET TRANSACTION ISOLATION LEVEL ".$isolationLevel);
    }

    public function isProfile(): bool {
        return $this->profile;
    }

    public function setProfile(bool $profile): self {
        $this->profile = $profile;
        return $this;
    }
}
