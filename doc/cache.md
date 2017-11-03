

Simple Use
```
$cache = new Cache\File("/path/to/dir"[, defaultttl]);

$cache->setValue("the_key", "the value");
$cache->setValue("the_key", "the value", 3600); // ttl
$cache->setValues([
    "the_key" => "a value",
    "another_key" => 123
], 3600);


$cache->has("the_key"); // return false if  not existing or expired
$hasKeys = $cache->has(["the_key", "another_key]);


$cache->getValue("the_key"); // reeturn default if not existing or expired
$cache->getValue("the_key", "the default value"); // reeturn default if not existing or expired
$values = $cache->getValues(["the_key", "another_key]);
/* $values :
[
    "the_key" => "a value",
    "another_key" => 123
]
*/

$cache->delete("the_key");
$cache->delete(["the_key", "another_key]);
$cache->deleteAll();
```

More complex use (saving defered and/or tags)
```
// itempool
$item = new Cache\Item("key", "value", int|\Datetime|\DateInterval);
// or
$item->setKey("key");
$item->setValue("value");
$item->setExpiration(3600);

$cache->setItem($item);
$cahce->setItems([$tiem, $tiem]);

// set deffered
$cache->setItem($item, true);
$cahce->setItems([$tiem, $tiem], true);
// others...
$cache->commit();

$cache->has("the_key"); // return false if  not existing or expired
$hasKeys = $cache->has(["the_key", "another_key]);

$item = $cache->getItem("key"); // if !isHit() item value is null + isHit() returns false
// always return an object

$value = $item->getValue();
$value = $item->getValue("a default value"); // get default if isHit() === false

$bool = $item->isHit();
$timestamp = $item->getExpiration(); // -1 when nothing set
```

Working with tags
```
$item = new Cache\Item(...);

$item->setTag("the_tag");
$item->setTags(["the_tag", "another_tag"]);

$tags = $item->getTags();

// use normal set item
$cache->setItem($item);

$cache->hasTag("the_tag");
$cache->hasTags(["the_tag", "another_tag"]);

$cache->deleteTag("tag");
$cache->deleteTags(["tag", "tag2"]);
```
