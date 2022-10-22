<?php
namespace IsThereAnyDeal\Database\Sql;

use IsThereAnyDeal\Database\Data\ValueMapper;
use IsThereAnyDeal\Database\Exceptions\InvalidValueCountException;
use IsThereAnyDeal\Database\Exceptions\MissingParameterException;
use IsThereAnyDeal\Database\Exceptions\SqlException;

class ParamParser
{
    private string $query;

    /** @var array<scalar> */
    private array $params;

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

                    $template = ValueMapper::getParamTemplate(count($value), $n);
                    $query = preg_replace($keyRegex, $template, $this->query, 1);
                    if (is_null($query)) {
                        throw new SqlException();
                    }

                    $this->query = $query;
                    $this->params = array_merge($this->params, $value);
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
                }
            }
        }
    }

    public function getQuery(): string {
        return $this->query;
    }

    /**
     * @return list<scalar>
     */
    public function getValues(): array {
        return $this->params;
    }
}
