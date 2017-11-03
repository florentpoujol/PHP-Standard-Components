<?php

namespace StdCmp\Cache;

use StdCmp\Cache\Interfaces\SimpleCache;

class PDOCache implements SimpleCache
{
    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var string
     */
    private $tableName = "pdo_cache";

    public function __construct(\PDO $pdo, string $tableName = null)
    {
        $this->pdo = $pdo;

        if ($tableName !== null) {
            $this->tableName = $tableName;
        }
        $this->createTable();
    }

    // CommonCache

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $this->validateKey($key);
        $stmt = "SELECT key FROM $this->tableName WHERE key = ? AND (expiration IS NULL OR expiration > " . time() . ")";
        $query = $this->doQuery($stmt, [$key]);
        return $query->fetch() !== false;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->deleteAll([$key]);
    }

    /**
     * Delete the keys specified in the array.
     * Delete the whole cache only if no array is provided.
     *
     * @param string[]|null $keys
     * @return bool Returns true only if all of the keys have been properly deleted.
     */
    public function deleteAll(array $keys = null): bool
    {
        if ($keys === null) {
            return $this->pdo->query("DELETE FROM $this->tableName")->execute();
        }
        if (empty($keys)) {
            return true;
        }

        $stmt = "DELETE FROM $this->tableName WHERE ";
        foreach ($keys as $key) {
            $this->validateKey($key);
            $stmt .= "key = ? OR ";
        }
        return $this->doQuery(substr($stmt, 0, -4), $keys, true);
    }

    // SimpleCache

    /**
     * @param string $key
     * @param mixed|null $defaultValue
     * @return mixed
     */
    public function getValue(string $key, $defaultValue = null)
    {
        $values = $this->getValues([$key], $defaultValue);
        return $values[$key];
    }

    /**
     * @param array $keys
     * @param mixed|null $defaultValue
     * @return array An associative array: key => value
     */
    public function getValues(array $keys, $defaultValue = null): array
    {
        if (empty($keys)) {
            return [];
        }

        foreach ($keys as $key) {
            $this->validateKey($key);
        }

        // build and run query
        $stmt = "SELECT key, value FROM $this->tableName WHERE ";
        $stmt .= str_repeat(
            "(KEY = ? AND (expiration IS NULL OR expiration > " . time() . ")) OR ",
            count($keys)
        );
        $query = $this->doQuery(substr($stmt, 0, -4), $keys);

        // process result
        $entries = $query->fetchAll();
        $values = [];
        foreach ($entries as $entry) {
            $values[$entry["key"]] = unserialize($entry["value"]);
        }

        // complete values with keys not found in DB or expired
        foreach ($keys as $key) {
            if (!isset($values[$key])) {
                $values[$key] = $defaultValue;
            }
        }

        return $values;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|\DateInterval|null $expiration
     * @return bool
     */
    public function setValue(string $key, $value, $expiration = null): bool
    {
        return $this->setValues([$key => $value], $expiration);
    }

    /**
     * @param array $values An associative array of key => value
     * @param int|\DateInterval|null $expiration
     * @return bool Returns true only if all the values have properly been saved.
     */
    public function setValues(array $values, $expiration = null): bool
    {
        if (empty($values)) {
            return false;
        }

        $expiration = $this->expirationToTimestamp($expiration);
        $stmt = "INSERT OR REPLACE INTO $this->tableName (key, value, expiration) VALUES ";
        $data = [];
        foreach ($values as $key => $value) {
            $this->validateKey($key);
            $stmt .= "(?, ?, ?), ";
            $data[] = $key;
            $data[] = serialize($value);
            $data[] = $expiration;
        }

        return $this->doQuery(substr($stmt, 0,  -2), $data, true);
    }

    // protected methods

    /**
     * @param string $key
     * @throws \InvalidArgumentException when $key has the wrong character set
     */
    protected function validateKey(string $key)
    {
        if (preg_match("/^[a-zA-Z0-9_\.-]+$/", $key) !== 1) {
            throw new \InvalidArgumentException("Key '$key' has invalid characters. Must be any of these: a-z A-Z 0-9 _ . -");
        }
    }

    /**
     * The expiration is converted to int first, then checked against the current timestamp.
     * Default to the class' default TTL when null  or <= 0.
     * Is considered a TTL when < to the current timestamp.
     *
     * @param int|\DateTime|\DateInterval|null $expiration
     * @return int|null
     */
    protected function expirationToTimestamp($expiration = null)
    {
        if ($expiration instanceof \DateInterval) {
            $expiration = $expiration->format("%s");
        } elseif ($expiration instanceof \DateTime) {
            $expiration = $expiration->getTimestamp();
        }

        $time = time();
        $expiration = (int)$expiration; // for when string or null

        if ($expiration <= 0) {
            $expiration = null;
        } elseif ($expiration < $time) { // is a ttl
            $expiration += $time;
        } // else $expiration > $time (is already a timestamp)

        return $expiration;
    }

    protected function createTable()
    {
        // key string index, value string, expiration int NULLABLE, tags (json)
        $createTable = <<<EOL
        CREATE TABLE IF NOT EXISTS `{$this->tableName}` (
          `key` varchar(255) NOT NULL,
          `value` TEXT,
          `expiration` INT(11),
          `tags` TEXT,
          PRIMARY KEY (`key`)
        )
EOL;
        $this->pdo->query($createTable);
    }

    protected function doQuery(string $stmt, array $data = [], bool $getSuccess = false)
    {
        $query = $this->pdo->prepare($stmt);
        if ($query === false) {
            throw new \Exception("Wrong prepared statement: $stmt");
        }
        $success = $query->execute($data);
        if ($getSuccess) {
            return $success;
        }
        return $query;
    }

    protected function entryExists($field, $value)
    {
        $stmt = "SELECT $field FROM $this->tableName WHERE $field = ?";
        $query = $this->doQuery($stmt, [$value]);
        return $query->fetch() !== false;
    }
}
