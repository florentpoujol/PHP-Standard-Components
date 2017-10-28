<?php

namespace StdCmp\Log\Writers;

use StdCmp\Log\Formatters;

class PDO extends Writer
{
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @param \PDO $pdo
     * @param string $tableName
     */
    public function __construct(\PDO $pdo, string $tableName)
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
    }

    /**
     * @param array $record
     * @return bool
     */
    public function __invoke(array $record): bool
    {
        $record = $this->processHelpers($record);
        if ($record === false) {
            return true;
        }

        if ($this->formatter === null) {
            $this->formatter = new Formatters\PDO();
        }

        $formatter = $this->formatter;
        $query = $formatter($record);

        $stmt = $this->pdo->prepare("INSERT INTO $this->tableName $query[statement]");
        $stmt->execute($query["params"]);

        return true;
    }
}
