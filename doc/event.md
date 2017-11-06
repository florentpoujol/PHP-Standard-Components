# Event Emitter

## Listen for event

The emitter holds a list of listener callable per event, with a priority for each to define in which order they are called.

Listeners receive the event name as first argument and the event data.

Listeners may return false to stop the propagation (stops subsequent listener to be called).

```
$emitter = new EventEmitter();

$emitter = new EventEmitter([
    "event.name" => callable1,
    "event.name2" => callable2
]);

// add listener
$emitter->addListener("event.name", function(string $eventName, $data) {
    // do things
    return false; // to stop propagation
});
$emitter->addListener($eventName, $callable, $priority);
// $priority is numeric, the higher the sooner the event will be callled
// default to 0
// for the same priority, listeners are called in order they are added 

$emitter->hasListener($eventName)

$emitter->removeListener($eventName, callable)
$emitter->removeListener(callable)

$emitter->removeListeners($eventName)
$emitter->removeListeners() // remove all
```

## Emit events

$data can be anything  
use an object to share data between listener

```
$emitter->emit("event.name", $data);
```


## Subscriber

A class that has a `getSubscribedEvents()` method  which return a list of event+listener.  
Since we are in the context of an object, listener can be a method name.

```
class subscriber 
{
    public function onSomething($eventName, $data)
    {
        // do something
    }
    
    public function getSubscribedEvents()
    {
        return [
            "event.something" => "onSomething", // assume priority of 0
            "event.somethingelse" => [10 => "onSomethingElse"] // priority of 10
            "event.anotherthing" => [
                -5 => [
                    "anotherthing1",
                    "anotherthing2"
                ]
            ]
        ];        
    }
}

$subscriber = new Subscriber();
// save somewhere...
$emitter->addSubscriber($subscriber);

// event's data can be an object of course
// but the event itself can be an object
```

Event class

Event data can already be a class but a whole event can be represented by a class.

```
class Event {

    function getName(): string;
    
    function stopPropagation(bool $stop);
    
    function isPropagationStopped(): bool;
}

$event = new Event("event.name");

$emitter->emit(event);
```

In that case, the listeners only receive the event.
Call `$event->getName()` for the event name.
