<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\Sql\Exceptions\MissingParameterException;

class Params
{
    private string $query;
    private array $params;
    private array $counts;

    /**
     * @throws MissingParameterException
     */
    public function __construct(string $query, array ...$maps) {
        $this->query = $query;

        preg_match_all("#:\w+#", $query, $m);
        $keys = $m[0];

        $map = array_merge(...$maps);

        $this->counts = [];
        $this->params = [];
        foreach($keys as $key) {
            if (!array_key_exists($key, $map)) {
                throw new MissingParameterException("Missing parameter '$key'");
            } else {
                $value = $map[$key];
                if (is_array($value)) {
                    $this->query = preg_replace("#{$key}#", $this->getInTemplate($value), $this->query, 1);
                    $this->params = array_merge($this->params, $this->flattenArray($value));
                    $this->counts[] = count($value);
                } else {
                    $this->query = preg_replace("#{$key}#", "?", $this->query, 1);
                    $this->params[] = $value;
                    $this->counts[] = 1;
                }
            }
        }
    }

    public function getQuery() {
        return $this->query;
    }

    public function getParams(): array {
        return $this->params;
    }

    public function getCounts(): array {
        return $this->counts;
    }

    private function getInTemplate(array $param): string {
        $cnt = count($param);

        if ($cnt > 0 && is_array($param[0])) {
            $template = $this->getInTemplate($param[0]);
        } else {
            $template = "?";
        }

        return "(".implode(",", array_fill(0, count($param), $template)).")";
    }

    private function flattenArray(array $array): array {

        if (count($array) == 0) {
            return $array;
        }

        /*
         * Only checking first element, because we're using this for IN values
         * and all elements must be same format anyway
         */
        if (!is_array($array[0])) {
            return $array;
        }

        $result = [];
        foreach($array as $item) {
            if (is_array($item)) {
                $result = array_merge($result, $this->flattenArray($item));
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }
}
