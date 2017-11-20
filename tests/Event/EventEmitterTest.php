<?php

namespace Tests\Event;

use PHPUnit\Framework\TestCase;
use StdCmp\Event\Event;
use StdCmp\Event\EventManager;
use StdCmp\Event\EventInterface;

class EventEmitterTest extends TestCase
{
    protected static $semitter;

    /**
     * @var EventManager
     */
    protected $emitter;

    static function setUpBeforeClass()
    {
        self::$semitter = new EventManager();
    }

    function setUp()
    {
        $this->emitter = self::$semitter;
    }

    function onUserLogout(EventInterface $event)
    {
        $this->assertEquals("user.logout", $event->getName());
        $this->assertEquals("florent", $event->getTarget()->name);
    }

    public static function onUserLogoutStatic($event)
    {
        self::assertEquals("user.logout", $event->getName());
        self::assertEquals("florent", $event->getTarget()->name);
    }

    function testAddListener()
    {
        $this->emitter->attach("user.login", function ($event) {
            $this->assertEquals("user.login", $event->getName());
            $this->assertEquals("florent", $event->getParams()["aParam"]);
        });

        $this->emitter->attach("user.logout", [$this, "onUserLogout"]);
        $this->emitter->attach("user.logout", [EventEmitterTest::class, "onUserLogoutStatic"]);

        $prop = new \ReflectionProperty(EventManager::class, "listeners");
        $prop->setAccessible(true);
        $listeners = $prop->getValue($this->emitter);

        $this->assertArrayHasKey("user.login", $listeners);
        $this->assertArrayHasKey("user.logout", $listeners);
        $this->assertArrayHasKey(0, $listeners["user.login"]);
        $this->assertArrayHasKey(0, $listeners["user.logout"]);
        $this->assertContains([$this, "onUserLogout"], $listeners["user.logout"][0]);
        $this->assertContains([EventEmitterTest::class, "onUserLogoutStatic"], $listeners["user.logout"][0]);
    }

    protected $events = [];

    function testAddListenerWithPriority()
    {
        $this->emitter->attach("priority", function ($event) {
            $event->getTarget()->events[] = $event->getName(). "10";
        }, 10);

        $this->emitter->attach("priority", function ($event) {
            $event->getTarget()->events[] = $event->getName(). "-10";
        }, -10);

        $this->emitter->attach("priority", function ($event) {
            $event->getTarget()->events[] = $event->getName(). "1";
        }, 1);

        $prop = new \ReflectionProperty(EventManager::class, "listeners");
        $prop->setAccessible(true);
        $listeners = $prop->getValue($this->emitter);

        $this->assertArrayHasKey("priority", $listeners);
        $this->assertArrayHasKey(-10, $listeners["priority"]);
        $this->assertArrayHasKey(1, $listeners["priority"]);
        $this->assertArrayHasKey(10, $listeners["priority"]);
    }

    function testHasListener()
    {
        $has = $this->emitter->hasListener("user.login");
        $this->assertEquals(true, $has);

        $has = $this->emitter->hasListener("user.logout");
        $this->assertEquals(true, $has);

        $has = $this->emitter->hasListener("priority");
        $this->assertEquals(true, $has);

        $has = $this->emitter->hasListener("non_existant_event");
        $this->assertEquals(false, $has);
    }

    function testEmit()
    {
        $this->emitter->trigger("user.login", null, ["aParam" => "florent"]);

        $event = new Event();
        $event->setName("user.logout");
        $object = new \StdClass();
        $event->setTarget($object);
        $object->name = "florent";
        $this->emitter->trigger($event);

        $event = new Event();
        $event->setName("priority");
        $event->setTarget($this);
        $this->emitter->trigger("priority", $this);
        $priority = ["priority10", "priority1", "priority-10"];
        $this->assertEquals($priority, $this->events);
    }

    function testSubscriber()
    {
        $subscriber = new EventSubscriber();
        $this->emitter->addSubscriber($subscriber);

        $this->emitter->trigger("sub.method");

        $data = [
            "sub.method0",
            "sub.method-10",
        ];
        $this->assertEquals($data, $subscriber->data);
    }

    function testEventObject()
    {
        $this->emitter = new EventManager();

        $listenerCalled = false;

        $this->emitter->attach("event-object.name", function (EventInterface $event) use (&$listenerCalled) {
            $this->assertInstanceOf(EventInterface::class, $event);
            $this->assertInstanceOf(EventSomething::class, $event);
            $this->assertNotEmpty(EventInterface::class, $event->getParams());
            $this->assertEquals("the event data", $event->getParams()[0]);
            $listenerCalled = true;
        });

        $event = new EventSomething("event-object.name");
        $event->setParams(["the event data"]);

        $this->emitter->trigger($event);
        $this->assertEquals(true, $listenerCalled);
    }

    function testPropagationStopped()
    {
        $this->emitter = new EventManager();

        $firstListenerCalled = false;
        $secondListenerCalled = false;
        $this->emitter->attach("return.false",
            function ($event) use (&$firstListenerCalled) {
                $firstListenerCalled = true;
                $event->stopPropagation();
            }
        );
        $this->emitter->attach("return.false",
            function (EventInterface $event) use (&$secondListenerCalled) {
                $secondListenerCalled = true; // should not be called
            }
        );


        $this->emitter->trigger("return.false");
        $this->assertEquals(true, $firstListenerCalled);
        $this->assertEquals(false, $secondListenerCalled);


        $event = new EventSomething("eventobject");
        $firstListenerCalled = false;
        $secondListenerCalled = false;
        $this->emitter->attach("eventobject",
            function (EventSomething $event) use (&$firstListenerCalled) {
                $firstListenerCalled = true;
                $event->stopPropagation(true);
            }
        );
        $this->emitter->attach("eventobject",
            function ($event) use (&$secondListenerCalled) {
                $secondListenerCalled = true; // should not be called
            }
        );

        $this->emitter->trigger($event);
        $this->assertEquals(true, $firstListenerCalled);
        $this->assertEquals(false, $secondListenerCalled);
    }

}
