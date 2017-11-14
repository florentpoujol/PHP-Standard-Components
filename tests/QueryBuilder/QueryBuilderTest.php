<?php

namespace QueryBuilder;

use PDO;
use PHPUnit\Framework\TestCase;
use StdCmp\QueryBuilder\QueryBuilder;

class QueryBuilderTest extends TestCase
{
    /**
     * @var PDO
     */
    protected static $spdo;

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var QueryBuilder
     */
    protected $builder;

    public static function setUpBeforeClass()
    {
        $pdo = new PDO("sqlite::memory:", null, null, [
            PDO::ERRMODE_EXCEPTION => true,
        ]);
        self::$spdo = $pdo;

        $createTable =
        "CREATE TABLE IF NOT EXISTS `test` (
          `id` INTEGER PRIMARY KEY AUTOINCREMENT,
          `name` TEXT NOT NULL,
          `email` TEXT,
          `created_at` DATETIME
        )";

        $pdo->query($createTable);
    }

    public function setUp()
    {
        $this->pdo = self::$spdo;
        $this->builder = new QueryBuilder($this->pdo);
    }

    public function testInsert()
    {
        $query = new QueryBuilder($this->pdo);
        $query
            ->insert(["name", "email", "created_at"])
            ->inTable("test");

        $expected = "INSERT INTO test (name, email, created_at) VALUES (:name, :email, :created_at)";
        $this->assertSame($expected, $query->toString());

        $data = [
            "name" => "Florent",
            "email" => "flo@flo.fr",
            "created_at" => "NOW()"
        ];
        $id = $query->execute($data);
        $this->assertSame("1", $id);

        $entry = $this->pdo->query("SELECT * FROM test")->fetch();
        $this->assertSame('1', $entry["id"]);
        $this->assertSame("Florent", $entry["name"]);

        // data passed to insert()
        $query = new QueryBuilder($this->pdo);
        $data = [
            "name" => "Florent2",
            "created_at" => "NOW()"
        ];
        $query->insert($data)->inTable("test");

        $expected = "INSERT INTO test (name, created_at) VALUES (:name, :created_at)";
        $this->assertSame($expected, $query->toString());

        $id = $query->execute();
        $this->assertSame("2", $id);

        $entry = $this->pdo->query("SELECT * FROM test WHERE id = $id")->fetch();
        $this->assertSame('2', $entry["id"]);
        $this->assertSame("Florent2", $entry["name"]);

        // data passed to execute()
        $query = new QueryBuilder($this->pdo);
        $query->insert(["name", "email", "created_at"])->inTable("test");

        $expected = "INSERT INTO test (name, email, created_at) VALUES (:name, :email, :created_at)";
        $this->assertSame($expected, $query->toString());

        $data = [
            "Florent3",
            "email@email.com",
            "NOW()"
        ];
        $id = $query->execute($data);

        $expected = "INSERT INTO test (name, email, created_at) VALUES (?, ?, ?)";
        $this->assertSame($expected, $query->toString());

        $this->assertSame("3", $id);

        $entry = $this->pdo->query("SELECT * FROM test WHERE id = $id")->fetch();
        $this->assertSame('3', $entry["id"]);
        $this->assertSame("Florent3", $entry["name"]);
    }

    public function testMultiInsert()
    {
        $data = [
            [
                "name" => "Florent4",
                "email" => "flo@flo.fr",
            ],
            [
                "name" => "Florent5",
                "email" => "flo@flo.fr",
            ]
        ];
        $query = new QueryBuilder($this->pdo);
        $id = $query
            ->insert($data)
            ->inTable("test")
            ->execute();

        $expected = "INSERT INTO test (name, email) VALUES (?, ?), (?, ?)";
        $this->assertSame($expected, $query->toString());

        // $id = $query->execute($data);
        $this->assertSame("5", $id);

        $entries = $this->pdo->query("SELECT * FROM test WHERE id >= 4");
        $entry = $entries->fetch();
        $this->assertSame("4", $entry["id"]);
        $this->assertSame("Florent4", $entry["name"]);
        $entry = $entries->fetch();
        $this->assertSame("5", $entry["id"]);
        $this->assertSame("Florent5", $entry["name"]);

        // data pass
        $data = [
            "Florent6",
            "Florent7",
            "Florent8",
        ];
        $query = new QueryBuilder($this->pdo);
        $id = $query
            ->insert("name")
            ->inTable("test")
            ->execute($data);

        $expected = "INSERT INTO test (name) VALUES (?), (?), (?)";
        $this->assertSame($expected, $query->toString());

        $this->assertSame("8", $id);
    }

    function testWhere()
    {
        $query = new QueryBuilder();
        $query->delete()
            ->table("test")
            ->where("name = stuff")
            ->where("email", ":email")
            ->where("id", ">=", 5);

        $expected = "DELETE FROM test WHERE name = stuff AND email = :email AND id >= 5";
        $this->assertSame($expected, $query->toString());

        // with nested clauses
        $query = new QueryBuilder();
        $query->delete()
            ->table("test")
            ->where("name = stuff")
            ->where(function ($query) {
                $query->where("other = stuff")
                ->where("email", ":email");
            } )
            ->where("id", ">=", 5);

        $expected = "DELETE FROM test WHERE name = stuff AND (other = stuff AND email = :email) AND id >= 5";
        $this->assertSame($expected, $query->toString());
    }

    function testOrWhere()
    {
        $query = new QueryBuilder();
        $query->delete()
            ->table("test")
            ->orWhereNull("name")
            ->orWhere("email", ":email")
            ->where("id", ">=", 5);

        $expected = "DELETE FROM test WHERE name IS NULL OR email = :email AND id >= 5";
        $this->assertSame($expected, $query->toString());

        // with nested clauses
        $query = new QueryBuilder();
        $query->delete()
            ->table("test")
            ->whereNull("name")
            ->orWhere(function ($query) {
                $query->where("other = stuff")
                    ->orWhereNotNull("email");
            } )
            ->where("id", ">=", 5);

        $expected = "DELETE FROM test WHERE name IS NULL OR (other = stuff OR email IS NOT NULL) AND id >= 5";
        $this->assertSame($expected, $query->toString());
    }

    function testJoin()
    {
        $query = new QueryBuilder();
        $query->select()
            ->table("test")
            ->join("otherTable")
            ->on("field", "value")
            ->orOn("field2", ">", "value2");

        $expected = "SELECT * FROM test JOIN otherTable ON field = value OR field2 > value2";
        $this->assertSame($expected, $query->toString());

        // with nested clauses
        $query = new QueryBuilder();
        $query->select()
            ->table("test")
            ->join("otherTable")
            ->on("field", "value")
            ->on(function ($q) {
                $q->orOn("field", "value");
                $q->on("field3", "value3");
            })
            ->orOn("field2", ">", "value2");

        $expected = "SELECT * FROM test JOIN otherTable ON field = value AND (field = value AND field3 = value3) OR field2 > value2";
        $this->assertSame($expected, $query->toString());

        // with multiple join  clauses
        $query = new QueryBuilder();
        $query->select()
            ->table("test")

            ->join("otherTable")
            ->on("field", "value")
            ->on(function ($q) {
                $q->orOn("field", "value");
                $q->on("field3", "value3");
            })
            ->orOn("field2", ">", "value2")

            ->join("yetAnotherTable")
            ->on("field", "value")
            ->on("field2", ">", "value2");

        $expected = "SELECT * FROM test "
            ."JOIN otherTable ON field = value AND (field = value AND field3 = value3) OR field2 > value2 "
            ."JOIN yetAnotherTable ON field = value AND field2 > value2";
        $this->assertSame($expected, $query->toString());

        // no on clause
        $query = new QueryBuilder();
        $query->select()
            ->table("test")
            ->join("otherTable");

        $this->expectException(\Exception::class);
        $query->toString();
    }

    function testAllOther()
    {
        $query = new QueryBuilder($this->pdo);
        $query->select("field as field2")
            ->select("field", "field3")
            ->select("otherField")
            ->inTable("test")
            ->join("otherTable")->on("field", "value")
            ->where("field", "LIKE", "%value")
            ->orWhereNotNull("field")
            ->groupBy("field")
            ->having("field", "value")
            ->orHaving("field2", "value2")
            ->orderBy("field", "DESC")
            ->limit(10, 0)
            ->offset(5);

        $expected = "SELECT field as field2, field as field3, otherField FROM test "
            ."JOIN otherTable ON field = 'value' "
            ."WHERE field LIKE '%value' OR field IS NOT NULL "
            ."GROUP BY field "
            ."HAVING field = 'value' OR field2 = 'value2' "
            ."ORDER BY field DESC LIMIT 10 OFFSET 5";
        $this->assertSame($expected, $query->toString());
    }
}
