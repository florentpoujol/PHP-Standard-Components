

Simple Use
```
$cache = new FileCache("/path/to/dir"); // default expiration + 1 years
$cache = new PDOCache($pdo, "my_cache_table_name"); // no default expiration
$cache = new ArrayCache(); // no expiration support

$cache->setValue("the_key", "the value");
$cache->setValue("the_key", "the value", 3600); // ttl
$cache->setValues([
    "the_key" => "a value",
    "another_key" => 123
], 3600);
// the last argument maybe DateInterval, DateTime or int
// when int it is considered a timestamp when > time(), or a TTL otherwise

$cache->has("the_key"); // return false if  not existing or expired

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
$cache->deleteAll(["the_key", "another_key]);
$cache->deleteAll(); // delete all the keys
```

Working with Items
```
$item = new Cache\Item("key", "value", $expiration);
// or
$item->setKey("key");
$item->setValue("value");
$item->setExpiration(3600);

$cache->setItem($item);
$cahce->setItems([$item, $item2]);

$item = $cache->getItem("key");
// always return an object, with at least the key set

$value = $item->getValue();
$value = $item->getValue("a default value"); // get default if isHit() === false

$bool = $item->isHit(); // returns false if key wasn't found or is expired. In that case, value is also null
$timestamp = $item->getExpiration(); // null when nothing set (FileCache always has expiration)
```

Working with tags
```
$item = new Cache\Item(...);

$item->addTag("the_tag");
$item->setTags(["the_tag", "another_tag"]);

$tags = $item->getTags();

// use normal set item
$cache->setItem($item);

$cache->hasTag("the_tag");

$items = $cache->getItemsWithTag("a_tag")

$cache->deleteTag("tag");
```
