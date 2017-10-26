<?php

namespace StdCmp\Log\Formatters;


class PDO
{
    /**
     * @var array
     */
    protected $map;
    protected $record;

    /**
     * @param array|null $map
     * @internal param array $config
     */
    public function __construct(array $map = [])
    {
        $this->map = $map;
    }

    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record): array
    {
        if (empty($this->map)) {
            foreach ($record as $key => $value) {
                if (is_array($value)) {
                    $record[$key] = json_encode($value);
                }
            }

            return [
                "query" => $this->buildQuery($record),
                "data" => $record
            ];
        }

        $this->record = $record;
        $data = [];

        foreach ($this->map as $dbField => $recordKey) {
            $value = $this->getRecordValue($recordKey);

            if (is_array($value)) {
                $value = json_encode($value);
            }
            $data[$dbField] = $value;
        }

        return [
            "query" => $this->buildQuery($data),
            "data" => $data,
        ];
    }

    /**
     * @param string $recordKey
     * @return mixed
     */
    protected function getRecordValue(string $recordKey)
    {
        $keys = explode(".", $recordKey);
        $value = $this->record;

        foreach ($keys as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * @param array $data
     * @return string
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
