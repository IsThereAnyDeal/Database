<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\DbDriver;

abstract class SqlRawQuery extends SqlQuery {

    private string $query;
    private array $keys = [];

    protected array $params = [];
    private array $preparedFor = [];

    public function __construct(DbDriver $db, string $query) {
        parent::__construct($db);
        $this->query = $query;

        preg_match_all("#:\w+#", $query, $m);
        $this->keys = $m[0];

        if (count($this->keys) == 0) {
            $this->statement = $this->db->prepare($query);
        }
    }

    /**
     * @throws \Database\Sql\Exceptions\MissingParameterException
     */
    public function params(array ...$maps): self {
        $params = new Params($this->query, ...$maps);
        $this->params = $params->getParams();

        if ($this->preparedFor != $params->getCounts()) {
            $this->statement = $this->db->prepare($params->getQuery());
            $this->preparedFor = $params->getCounts();
        }
        return $this;
    }

    public function getAffectedRowCount(): int {
        return $this->statement->rowCount();
    }

    public function dump(): array {

        $simulation = null;
        if (isset($this->statement)) {
            $simulation = $this->statement->queryString;
            foreach($this->params as $param) {
                if (is_int($param) || is_float($param)) {
                    $simulation = preg_replace("#\?#", $param, $simulation, 1);
                } else {
                    $simulation = preg_replace("#\?#", $this->db->quote($param), $simulation, 1);
                }
            }
        }

        return [
            $this->query,
            isset($this->statement) ? $this->statement->queryString : null,
            $this->keys,
            $this->params,
            $simulation
        ];
    }
}
