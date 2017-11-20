<?php

namespace StdCmp\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

class PDOCache implements CacheInterface, CacheItemPoolInterface, TagAwareCache
{
    /**
     * @var \PDO
     */
    protected $pdo;

    private $tableName = "pdo_cache";

    /**
     * @var CacheItemInterface[]
     */
    protected $deferredItems = [];

    public function __construct(\PDO $pdo, string $tableName = null)
    {
        $this->pdo = $pdo;

        if ($tableName !== null) {
            $this->tableName = $tableName;
        }
        $this->createTable();
    }

    // SimpleCache

    public function has($key): bool
    {
        $this->validateKey($key);
        $stmt = "SELECT key FROM $this->tableName WHERE key = ? AND (expiration IS NULL OR expiration > " . time() . ")";
        $query = $this->doQuery($stmt, [$key]);
        return $query->fetch() !== false;
    }

    public function delete($key): bool
    {
        return $this->deleteMultiple([$key]);
    }

    public function deleteMultiple($keys): bool
    {
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

    public function clear(): bool
    {
        return $this->pdo->query("DELETE FROM $this->tableName")->execute();
    }

    public function get($key, $defaultValue = null)
    {
        $values = $this->getMultiple([$key], $defaultValue);
        return $values[$key];
    }

    public function getMultiple($keys, $defaultValue = null): array
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

    public function set($key, $value, $expiration = null): bool
    {
        return $this->setMultiple([$key => $value], $expiration);
    }

    public function setMultiple($values, $expiration = null): bool
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

    // CacheItemPoolInterface

    public function hasItem($key): bool
    {
        return $this->has($key);
    }

    public function deleteItem($key): bool
    {
        return $this->delete($key);
    }

    public function deleteItems(array $keys): bool
    {
        return $this->deleteMultiple($keys);
    }

    // clear() already defined above, as part of Psr\SimpleCache\CacheInterface

    public function getItem($key): CacheItemInterface
    {
        return $this->getItems([$key])[$key];
    }

    public function getItems(array $keys = []): array
    {
        if (empty($keys)) {
            return [];
        }

        foreach ($keys as $key) {
            $this->validateKey($key);
        }

        $items = $this->_getItems($keys, "key");

        // complete values with keys not found in DB or expired
        foreach ($keys as $key) {
            if (!isset($items[$key])) {
                $items[$key] = new CacheItem($key);
            }
        }

        return  $items;
    }

    public function save(CacheItemInterface $item): bool
    {
        return $this->saveMultiple([$item]);
    }

    /**
     * @param array|CacheItemInterface[] $items
     */
    public function saveMultiple(array $items): bool
    {
        if (empty($items)) {
            return false;
        }

        $stmt = "INSERT OR REPLACE INTO $this->tableName (key, value, expiration, tags) VALUES ";
        $data = [];
        foreach ($items as $key => $item) {
            $key = $item->getKey();
            $this->validateKey($key);

            $stmt .= "(?, ?, ?,  ?), ";
            $data[] = $key;
            $data[] = serialize($item->get());

            $expiration = $item->getExpiration();
            if ($expiration !== null) {
                $data[] = $this->expirationToTimestamp($expiration);
            } else {
                $data[] = null;
            }

            $tags = $item->getTags();
            if (! empty($tags)) {
                $data[] = json_encode($tags);
            } else {
                $data[] = null;
            }
        }

        return $this->doQuery(substr($stmt, 0,  -2), $data, true);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferredItems[] = $item;
        return true;
    }

    public function commit(): bool
    {
        $items = $this->deferredItems;
        $this->deferredItems = [];
        return $this->saveMultiple($items);
    }

    // TagAwareCache interface

    /**
     * @return CacheItemInterface[]
     */
    public function getItemsWithTag(string $tag): array
    {
        return $this->_getItems(["%$tag%"], "tags");
    }

    public function hasTag(string $tag): bool
    {
        $stmt = "SELECT key FROM $this->tableName WHERE tags LIKE ?";
        $query = $this->doQuery($stmt, ["%$tag%"]);
        return $query->fetch() !== false;
    }

    public function deleteTag(string $tag): bool
    {
        $stmt = "SELECT key FROM $this->tableName WHERE tags LIKE ?";;
        $query = $this->doQuery($stmt, ["%$tag%"]);
        $entries = $query->fetchAll();
        $keys = array_column($entries, "key");
        return $this->deleteMultiple($keys);
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

    protected function _getItems(array $keysOrTags, string $fieldName): array
    {
        if (empty($keysOrTags)) {
            return [];
        }

        // build and run query
        $stmt = "SELECT * FROM $this->tableName WHERE (expiration IS NULL OR expiration > " . time() . ") AND (";
        $equalOrLike = $fieldName === "tags" ? "LIKE" : "=";
        $stmt .= str_repeat(
            "$fieldName $equalOrLike ? OR ",
            count($keysOrTags)
        );
        $stmt = substr($stmt, 0, -4) . ")";

        $query = $this->doQuery($stmt, $keysOrTags);

        // process result
        $entries = $query->fetchAll();
        $items = [];
        foreach ($entries as $entry) {
            $key = $entry["key"];
            $item = new CacheItem(
                $key,
                unserialize($entry["value"]),
                $entry["expiration"]
            );

            if ($entry["tags"] !== null) {
                $item->setTags(json_decode($entry["tags"], true));
            }

            $items[$key] = $item;
        }

        return $items;
    }
}
