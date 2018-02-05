# Event Manager

Compliant with the abandoned PSR-14.

## Events

An event is something that happens in your application. It is represented by a class that implements `EventInterface`, and typically extends the base `Event` class.

As such, it usually contains a name, and optionally a target and some parameters.

- The name should be "contained" in the name of the class, for convenience.
- The parameters is an array of arbitrary data.
- The target is expected to be an object relevant to the event, which give it some context.

## Attach listeners to events

The manager holds a list of listener callable (callbacks) per event, with a priority for each to define in which order they are called.

Listeners receive the event object as only argument.

Listeners may call the `stopPropagation(true)` method on the event object to stops subsequent listeners to be called.

Listeners may return non-null data. The manager's `trigger()` method will return the data returned by the last listener.

```php
$manager = new EventManager();

$manager = new EventManager();

// attach a listener
$manager->attach("event.name", function(EventInterface $event) {
    // do things
    $event->stopPropagation();
});

$manager->attach($eventName, $callable, $priority);
// $priority is numeric, the higher the sooner the event will be callled
// default to 0
// for the same priority, listeners are called in order they are added 

// check if an event has at least one listener
$manager->hasListener($eventName)

// remove a listener
$manager->detach($eventName, $callable)

// remove all listeners for the specified event
$manager->clearListeners($eventName)
```

## Attach events in bulk

The manager's `attachEvents()` method and constructor accept an associative array as its `events` argument.

It must contain the event names as key and callables or array of callables as key.


## Trigger events

The event is always represented by an object implementing `EventInterface`, which is passed to all listeners.

The manager's `trigger()` method accept either an event instance, or an event name and optionally a target and parameters.
 
Ie: triggering an event with some params but no target.
```php
$event = new Event();
$event->setName("event.name");
$event->setParams(["data" => "something"]);
$manager->trigger($event);

// or (the event object will be created by the trigger method)
$manager->trigger("event.name", null, ["data" => "something"]);
```

## Subscriber

Subscribers are classes that have a `getSubscribedEvents()` method which return an associative array that must contain the event names as key and callables or array of callables as key.
  
But since we are in the context of an object, values can be standard strings instead of callable. They are then assumed to be a subscriber's method name.

The method receive the event manager instance so that you can directly attach some events with a priority other than the default one.

```php
class subscriber 
{
    public function onSomething(EventInterface $event)
    {
        // do something
    }
    
    public function getSubscribedEvents(EventManagerInterface $manager)
    {
        return [
            "event.something" => "onSomething",
            "event.anotherthing" => [
                "anotherMethod",
                function ($event) { // regular callable
                    //
                }
            ]
        ];        
    }
}

$subscriber = new Subscriber();

$manager->addSubscriber($subscriber);
```
