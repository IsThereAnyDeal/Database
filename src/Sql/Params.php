<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\Sql\Exceptions\InvalidValueCountException;
use IsThereAnyDeal\Database\Sql\Exceptions\MissingParameterException;
use IsThereAnyDeal\Database\Sql\Exceptions\SqlException;

class Params
{
    private string $query;

    /** @var array<scalar> */
    private array $params;

    /** @var array<int> */
    private array $counts;

    /**
     * @param string $query
     * @param array<string, scalar|scalar[]> ...$maps
     * @throws MissingParameterException
     * @throws SqlException
     */
    public function __construct(string $query, array ...$maps) {
        $this->query = $query;

        preg_match_all("#(:\w+)(?:\((\d+)\))?#", $query, $m);
        $map = array_merge(...$maps);

        $this->params = [];
        $this->counts = [];
        foreach($m[1] as $index => $key) {
            $keyRegex = "#{$key}(\(\d+\))?#";
            $n = empty($m[2][$index]) ? 1 : (int)$m[2][$index];

            if (!array_key_exists($key, $map)) {
                throw new MissingParameterException("Missing parameter '$key'");
            } else {
                $value = $map[$key];
                if (is_array($value)) {
                    if (count($value) % $n != 0) {
                        throw new InvalidValueCountException();
                    }

                    $query = preg_replace($keyRegex, $this->getInTemplate($value, $n), $this->query, 1);
                    if (is_null($query)) {
                        throw new SqlException();
                    }

                    $this->query = $query;
                    $this->params = array_merge($this->params, $this->flattenArray($value));
                    $this->counts[] = count($value);
                } else {
                    if ($n !== 1) {
                        throw new InvalidValueCountException();
                    }

                    $query = preg_replace($keyRegex, "?", $this->query, 1);
                    if (is_null($query)) {
                        throw new SqlException();
                    }

                    $this->query = $query;
                    $this->params[] = $value;
                    $this->counts[] = 1;
                }
            }
        }
    }

    public function getQuery(): string {
        return $this->query;
    }

    /**
     * @return array<scalar>
     */
    public function getParams(): array {
        return $this->params;
    }

    /**
     * @return array<int>
     */
    public function getCounts(): array {
        return $this->counts;
    }

    /**
     * @param array<scalar> $values
     * @param int $size
     * @return string
     */
    private function getInTemplate(array $values, int $size=1): string {
        $template = $size === 1
            ? "?"
            : "(".implode(",", array_fill(0, $size, "?")).")";

        return "(".implode(",", array_fill(0, count($values) / $size, $template)).")";
    }

    /**
     * @param array<scalar|scalar[]> $array
     * @return array<scalar>
     */
    private function flattenArray(array $array): array {
        return iterator_to_array( // @phpstan-ignore-line
            new \RecursiveIteratorIterator(
                new \RecursiveArrayIterator($array)
            ), false
        );
    }
}
