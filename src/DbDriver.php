<?php
namespace IsThereAnyDeal\Database;

use Psr\Log\LoggerInterface;

class DbDriver
{
    private \PDO $db;
    private ?LoggerInterface $logger = null;
    private bool $profile = false;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    public function getDriver(): \PDO {
        return $this->db;
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
