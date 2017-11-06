<?php

namespace Tests\Event;

use PHPUnit\Framework\TestCase;
use StdCmp\Event\EventEmitter;
use StdCmp\Event\EventInterface;

class EventEmitterTest extends TestCase
{
    /**
     * @var EventEmitter
     */
    protected static $semitter;

    /**
     * @var EventEmitter
     */
    protected $emitter;

    static function setUpBeforeClass()
    {
        self::$semitter = new EventEmitter();
    }

    function setUp()
    {
        $this->emitter = self::$semitter;
    }

    function onUserLogout($eventName, $user)
    {
        $this->assertEquals("user.logout", $eventName);
        $this->assertEquals("florent", $user->name);
    }

    public static function onUserLogoutStatic($eventName, $user)
    {
        self::assertEquals("user.logout", $eventName);
        self::assertEquals("florent", $user->name);
    }

    function testAddListener()
    {
        $this->emitter->addListener("user.login", function ($eventName, $data) {
            $this->assertEquals("user.login", $eventName);
            $this->assertEquals("florent", $data["name"]);
        });

        $this->emitter->addListener("user.logout", [$this, "onUserLogout"]);
        // $this->emitter->addListener("user.logout", "EventEmitterTest::onUserLogoutStatic");
        $this->emitter->addListener("user.logout", [EventEmitterTest::class, "onUserLogoutStatic"]);

        $prop = new \ReflectionProperty(EventEmitter::class, "listeners");
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
        $this->emitter->addListener("priority", function ($eventName, $data) {
            $data->events[] = $eventName . "10";
        }, 10);

        $this->emitter->addListener("priority", function ($eventName, $data) {
            $data->events[] = $eventName . "-10";
        }, -10);

        $this->emitter->addListener("priority", function ($eventName, $data) {
            $data->events[] = $eventName . "1";
        }, 1);

        $prop = new \ReflectionProperty(EventEmitter::class, "listeners");
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
        $this->emitter->emit("user.login", ["name" => "florent"]);

        $event = new \stdClass;
        $event->name = "florent";
        $this->emitter->emit("user.logout", $event);

        $this->emitter->emit("priority", $this);
        $priority = ["priority10", "priority1", "priority-10"];
        $this->assertEquals($priority, $this->events);
    }

    function testSubscriber()
    {
        $subscriber = new EventSubscriber();
        $this->emitter->addSubscriber($subscriber);

        $this->emitter->emit("sub.prio.multimethod");
        $this->emitter->emit("sub.prio.method");
        $this->emitter->emit("sub.method");

        $data = [
            "sub.prio.multimethod1",
            "sub.prio.multimethod1",
            "sub.prio.multimethod-5",
            "sub.prio.method-10",
            "sub.method0"
        ];
        $this->assertEquals($data, $subscriber->data);
    }

    function testEventObject()
    {
        $this->emitter = new EventEmitter();

        $listenerCalled = false;

        $this->emitter->addListener("event-object.name", function (EventInterface $event) use (&$listenerCalled) {
            $this->assertInstanceOf(EventInterface::class, $event);
            $this->assertInstanceOf(EventSomething::class, $event);
            $this->assertNotEmpty(EventInterface::class, $event->data);
            $this->assertEquals("the event data", $event->data[0]);
            $listenerCalled = true;
        });

        $event = new EventSomething("event-object.name");
        $event->data[0] = "the event data";

        $this->emitter->emit($event);
        $this->assertEquals(true, $listenerCalled);
    }

    function testPropagationStopped()
    {
        $this->emitter = new EventEmitter();

        $firstListenerCalled = false;
        $secondListenerCalled = false;
        $this->emitter->addListener("return.false",
            function (...$arg) use (&$firstListenerCalled) {
                $firstListenerCalled = true;
                return false;
            }
        );
        $this->emitter->addListener("return.false",
            function (...$arg) use (&$secondListenerCalled) {
                $secondListenerCalled = true; // should not be called
            }
        );


        $this->emitter->emit("return.false");
        $this->assertEquals(true, $firstListenerCalled);
        $this->assertEquals(false, $secondListenerCalled);


        $event = new EventSomething("eventobject");
        $firstListenerCalled = false;
        $secondListenerCalled = false;
        $this->emitter->addListener("eventobject",
            function (EventSomething $event) use (&$firstListenerCalled) {
                $firstListenerCalled = true;
                $event->stopPropagation();
            }
        );
        $this->emitter->addListener("eventobject",
            function ($event) use (&$secondListenerCalled) {
                $secondListenerCalled = true; // should not be called
            }
        );

        $this->emitter->emit($event);
        $this->assertEquals(true, $firstListenerCalled);
        $this->assertEquals(false, $secondListenerCalled);
    }

}
