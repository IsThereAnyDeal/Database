<?php
namespace IsThereAnyDeal\Database\Sql;

class SqlUpdateQuery extends SqlRawQuery {

    final public function update(?array $params=null): self {
        if (!is_null($params)) {
            $this->params($params);
        }

        $this->execute($this->params);
        return $this;
    }

}
