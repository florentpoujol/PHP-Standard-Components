<?php

namespace StdCmp\Log\Formatters;

class PDO
{
    protected $map = [];

    public function __construct(array $map = null)
    {
        if ($map !== null) {
            $this->map = $map;
        }
    }

    public function __invoke(array $record): array
    {
        if (empty($this->map)) {
            foreach ($record as $key => $value) {
                if (is_array($value)) {
                    $record[$key] = json_encode($value);
                }
            }

            return [
                "statement" => $this->buildQuery($record),
                "params" => $record
            ];
        }

        // a map has been supplied
        $params = [];

        foreach ($this->map as $dbField => $recordKey) {
            $value = $this->getRecordValue($record, $recordKey); // recordKey may be composite like "part1.part2"

            if (is_array($value)) {
                $value = json_encode($value);
            }
            $params[$dbField] = $value;
        }

        return [
            "statement" => $this->buildQuery($params),
            "params" => $params,
        ];
    }

    protected function getRecordValue(array $record, string $recordKey)
    {
        $parts = explode(".", $recordKey);
        $value = $record;

        foreach ($parts as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Build part of a SQL INSERT query suitable to be passed to PDO::prepare(), from the supplied data.
     * Ie: "(fieldName) VALUES (:fieldName)"
     */
    protected function buildQuery(array $data): string
    {
        $fields = "";
        $values = "";

        foreach ($data as $fieldName => $v) {
            $fields .= "$fieldName, ";
            $values .= ":$fieldName, ";
        }

        $fields = rtrim($fields, ", ");
        $values = rtrim($values, ", ");

        return "($fields) VALUES ($values)";
    }
}
