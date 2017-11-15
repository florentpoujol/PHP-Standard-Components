<?php

namespace Session;

use PDO;
use PHPUnit\Framework\TestCase;
use StdCmp\Session\PDOSession;
use StdCmp\Session\Session;

class PDOSessionTest extends TestCase
{
    /**
     * @var \PDO
     */
    protected static $spdo;

    /**
     * @var Session
     */
    protected $session;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        ob_start();
    }

    public static function setUpBeforeClass()
    {

        self::$spdo = new PDO("sqlite::memory:", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    public function setUp()
    {
        $this->session = new PDOSession(self::$spdo);
    }

    function testControlMethods()
    {
        $this->assertSame("", $this->session->getId());
        $this->session->start();

        $id = $this->session->getId();
        $this->assertNotEmpty($id);

        $this->session->regenerateId();
        $id2 = $this->session->getId();
        $this->assertNotSame($id, $id2);

        $this->session->setId("new_id");
        $id3 = $this->session->getId();
        $this->assertSame("new_id", $id3);
        $this->assertNotSame($id, $id2);
        $this->assertNotSame($id2, $id3);
        $this->assertNotSame($id, $id3);

        $this->session->destroy();
        $this->assertSame("", $this->session->getId());
    }

    function testSetGetSessionData()
    {
        $this->session->set("int", 123);
        $this->session->set("string", "a string");
        $this->session->set("array", [
            "int" => 123,
            "string" => "a string",
        ]);

        $this->assertSame(true, $this->session->has("int"));
        $this->assertSame(true, $this->session->has("string"));
        $this->assertSame(true, $this->session->has("array"));
        $this->assertSame(false, $this->session->has("non_existant_key"));

        $this->assertSame(123, $this->session->get("int"));
        $this->assertSame("a string", $this->session->get("string"));
        $array = $this->session->get("array");
        $this->assertSame(123, $array["int"]);
        $this->assertSame("a string", $array["string"]);
        $this->assertSame(null, $this->session->get("non_existant_key"));
        $this->assertSame("default", $this->session->get("non_existant_key", "default"));

        $this->session->delete("int");
        $this->assertSame(false, $this->session->has("int"));
        $this->assertSame(true, $this->session->has("string"));
        $this->assertSame(true, $this->session->has("array"));
        $this->assertSame(false, $this->session->has("non_existant_key"));

        $this->session->deleteAll();
        $this->assertSame(false, $this->session->has("int"));
        $this->assertSame(false, $this->session->has("string"));
        $this->assertSame(false, $this->session->has("array"));
        $this->assertSame(false, $this->session->has("non_existant_key"));
    }

    function testFlashData()
    {
        $this->assertSame(false, $this->session->has("error"));

        $this->session->addFlashMessage("error", "An error msg");
        $this->session->addFlashMessage("error", "Another error msg");

        $array = ["An error msg", "Another error msg"];
        $this->assertSame($array, $this->session->get("error"));
        $this->assertSame(true, $this->session->has("error"));

        $flash = $this->session->getFlashMessages("error");
        $this->assertSame($array, $flash);
        $this->assertSame(false, $this->session->has("error"));

        $this->assertEquals([], $this->session->getFlashMessages("non_existant_key"));
    }
}
