<?php


use StdCmp\Log\Writers;
use PHPUnit\Framework\TestCase;

class PDOWriterTest extends TestCase
{
    protected $tableName = "pdoWriterTest";

    /**
     * @var \PDO
     */
    protected $pdo;

    public function setUp()
    {
        $pdo = new \PDO("sqlite::memory:");
        $pdo->query("CREATE TABLE $this->tableName (priority INT, priority_name LINESTRING, message MULTILINESTRING, context MULTILINESTRING, timestamp LINESTRING, datetime DATETIME, extra  MULTILINESTRING)");

        $this->pdo = $pdo;
    }

    public function testWithDefaultFormatter()
    {
        $record = [
            "priority" => 0,
            "priority_name" => "emergency",
            "message" => "Julie, do the thing !",
            "context" => ["some" => "context"],
            "timestamp" => 123456789, // Thu, 29 Nov 1973 21:33:09 GMT
            "extra" => [],
        ];

        $writer = new Writers\PDO($this->pdo, $this->tableName);
        // the writer instanciate itself a PDO formatter
        $writer($record);

        // change a few things in the record
        $record["timestamp"] = "Thu, 29 Nov 1973 21:33:09 GMT";
        unset($record["extra"]);
        unset($record["context"]);
        $writer($record);

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM $this->tableName");
        $this->assertEquals(2, $stmt->fetch()["COUNT(*)"]);

        $stmt = $this->pdo->query("SELECT * FROM $this->tableName");

        $line = $stmt->fetch();
        $this->assertEquals(0, (int)$line["priority"]);
        $this->assertEquals("emergency", $line["priority_name"]);
        $this->assertEquals($record["message"], $line["message"]);
        $this->assertEquals('{"some":"context"}', $line["context"]);
        $this->assertEquals(123456789, (int)$line["timestamp"]);
        $this->assertEquals("[]", $line["extra"]);

        $line = $stmt->fetch();
        $this->assertEquals(0, (int)$line["priority"]);
        $this->assertEquals("emergency", $line["priority_name"]);
        $this->assertEquals($record["message"], $line["message"]);
        $this->assertNull($line["context"]);
        $this->assertEquals($record["timestamp"], $line["timestamp"]);
        $this->assertNull($line["extra"]);
    }

    public function testWithDatetimeAndMap()
    {
        $record = [
            "priority" => 0,
            "priority_name" => "emergency",
            "message" => "Julie, do the thing !",
            "context" => ["some" => "context"],
            "timestamp" => 123456789, // Thu, 29 Nov 1973 21:33:09 GMT
            "extra" => [],
        ];

        $writer = new Writers\PDO($this->pdo, $this->tableName);

        $writer->addHelper(new \StdCmp\Log\Helpers\Datetime("Y-m-d"));

        $map = [
            // db field => record field
            "priority" => "priority_name",
            "message" => "context.some",
            "datetime" => "timestamp"
        ];
        $formatter = new StdCmp\Log\Formatters\PDO($map);
        $writer->setFormatter($formatter);

        $writer($record);

        $stmt = $this->pdo->query("SELECT * FROM $this->tableName WHERE datetime IS NOT NULL");

        $line = $stmt->fetch();
        $this->assertEquals("emergency", $line["priority"]);
        $this->assertEquals("context", $line["message"]);
        $this->assertEquals("1973-11-29", $line["datetime"]);
    }
}
