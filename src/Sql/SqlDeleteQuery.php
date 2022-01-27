<?php
namespace Database\Sql;

class SqlDeleteQuery extends SqlRawQuery {

    final public function delete(?array $params=null): void {
        if (!is_null($params)) {
            $this->params($params);
        }

        $this->execute($this->params);
    }

}
