<?php

namespace StdCmp\Session;

class PDOSession implements Session
{
    /**
     * @var \PDO
     */
    protected $pdo;

    protected $options = [];
    protected $tableName = "pdo_sessions";

    protected $id = "";
    protected $data = [];

    public function __construct(\PDO $pdo, array $options = null)
    {
        $this->pdo = $pdo;

        $this->options = [
            "cookie_name" => ini_get("session.name"),
            "cookie_path" => ini_get("session.cookie_path"),
            "cookie_domain" => ini_get("session.cookie_domain"),
            "cookie_lifetime" => ini_get("session.cookie_lifetime"),
            "cookie_httponly" => ini_get("session.cookie_httponly"),
            "cookie_secure" => ini_get("session.cookie_secure"),
            "gc_divisor" => ini_get("session.gc_divisor"),
            "gc_probability" => ini_get("session.gc_probability"),
            "gc_maxlifetime" => ini_get("session.gc_maxlifetime"),
        ];
        // act like use_cookie and use_only_cookies = 1

        if (isset($options["table_name"])) {
            $this->tableName = $options["table_name"];
            unset($options["table_name"]);
        }

        if ($options !== null) {
            $this->options = array_merge($this->options, $options);
        }
    }

    public function start(): bool
    {
        // check that the table exists, if not create it
        $this->createTableInDB();

        $cookieName = $this->options["cookie_name"];

        if (isset($_COOKIE[$cookieName])) {
            $this->id = $_COOKIE[$cookieName];
            $this->data = $this->getDataFromDB(); // returns empty array in case or DB error
        } else {
            $this->id = $this->getRandomString();
            $this->insertNewIdInDB();
        }

        setcookie(
            $cookieName,
            $this->id,
            time() + $this->options["cookie_lifetime"],
            $this->options["cookie_path"],
            $this->options["cookie_domain"],
            $this->options["cookie_secure"],
            $this->options["cookie_httponly"]
        );

        // check if it is time to remove expired session entries from the DB
        $probability = $this->options["gc_probability"];
        $divisor = $this->options["gc_divisor"];
        if (mt_rand(1, $divisor) <= $probability) {
            $this->deleteExpiredInDB($this->options["gc_maxlifetime"]);
        }

        return true;
    }

    public function destroy(): bool
    {
        $this->deleteInDB($this->id);
        unset($_COOKIE[$this->options["cookie_name"]]);
        $this->id = "";
        $this->data = [];
        return true;
    }

    public function setId(string $newId): bool
    {
        $this->updateIdInDB($this->id, $newId);
        $this->id = $newId;
        return true;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function regenerateId(bool $deleteOldSession = false): string
    {
        if ($deleteOldSession) {
            $this->destroy();
            $this->start();
            return $this->id;
        }

        $this->setId($this->getRandomString());
        return $this->id;
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value)
    {
        $this->data[$key] = $value;
        $this->updateDataInDB();
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * @param mixed $defaultValue
     * @return mixed
     */
    public function get(string $key, $defaultValue = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $defaultValue;
    }

    public function delete(string $key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
            $this->updateDataInDB();
            return true;
        }
        return false;
    }

    public function deleteAll()
    {
        $this->data = [];
        $this->updateDataInDB();
    }

    public function addFlashMessage(string $key, string $message)
    {
        if (! isset($this->data[$key])) {
            $this->data[$key] = [];
        }
        $this->data[$key][] = $message;
        $this->updateDataInDB();
    }

    public function getFlashMessages(string $key): array
    {
        $messages = isset($this->data[$key]) ? $this->data[$key] : [];
        $this->delete($key);
        return $messages;
    }

    protected function createTableInDB()
    {
        $query = "CREATE TABLE IF NOT EXISTS $this->tableName (" .
          "`id` varchar(255) NOT NULL PRIMARY KEY," .
          "`data` TEXT," .
          "`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP" .
        ")";

        if (!$this->pdo->query($query)) {
            throw new \Exception("Could not create table with name '$this->tableName'");
        }
        return true;
    }

    protected function getRandomString(): string
    {
        $string = bin2hex(random_bytes(20)); // should produce a string of about 40 chars
        // randomly set some letters (hopefully) to uppercase
        $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            if (ctype_alpha($string[$i]) && mt_rand(0, 1) === 0) {
                $string = substr_replace($string, strtoupper($string[$i]), $i, 1);
            }
        }
        return $string;
    }

    protected function insertNewIdInDB(): bool
    {
        $query = "INSERT INTO $this->tableName (id) VALUES (:id)";
        $stmt = $this->pdo->prepare($query);
        if (! $stmt->execute(["id" => $this->id])) {
            throw new \PDOException("Could not insert new session entry with id '$this->id' in table '$this->tableName'.");
        }
        return true;
    }

    protected function updateIdInDB(string $oldId, string $newId): bool
    {
        $query = "UPDATE $this->tableName set id = :newId WHERE id = :oldId";
        $stmt = $this->pdo->prepare($query);
        if (! $stmt->execute(compact("oldId", "newId"))) {
            throw new \PDOException("Could not update session id in table '$this->tableName'. Old id is '$oldId'. New id is '$newId'.");
        }
        return true;
    }

    protected function getDataFromDB(): array
    {
        $query = "SELECT data FROM $this->tableName WHERE id = :id";
        $stmt = $this->pdo->prepare($query);

        if ($stmt->execute(["id" => $this->id])) {
            return [];
        }

        $entry = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($entry === false) {
            return [];
        }

        $data = $entry["data"];
        if ($entry !== "") {
            return unserialize($data);
        }
        return [];
    }

    protected function updateDataInDB(): bool
    {
        $query = "UPDATE $this->tableName SET data = :data, updated_at = DATETIME() WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $data = serialize( $this->data );
        if (! $stmt->execute(compact("data"))) {
            throw new \PDOException("Could not update session data in table '$this->tableName' with id '$this->id'.");
        }
        return true;
    }

    protected function deleteExpiredInDB(int $lifetime): bool
    {
        $query = "DELETE FROM $this->tableName WHERE updated_at < (DATETIME() - INTERVAL :lifetime SECOND)";
        $stmt = $this->pdo->prepare($query);
        if (! $stmt->execute(compact("lifetime"))) {
            throw new \PDOException("Could not delete expired session entries in table '$this->tableName'.");
        }
        return true;
    }

    protected function deleteInDB(string $id): bool
    {
        $query = "DELETE FROM $this->tableName WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute(compact("id"));
    }
}
