# Cache

Both PSR-6 and PSR-16 compliant, with tag support.

## Simple Use

```php
$cache = new FileCache("/path/to/dir"); // default expiration = +1 years
$cache = new PDOCache($pdo, "my_cache_table_name"); // no default expiration
$cache = new ArrayCache(); // no expiration support

$cache->set("the_key", "the value");
$cache->set("the_key", "the value", 3600); // ttl
$cache->setMultiple([
    "the_key" => "a value",
    "another_key" => 123
], 3600);
// the last argument maybe DateInterval, DateTime or int
// when int it is considered a timestamp when > time(), or a TTL otherwise

$cache->has("the_key"); // return false if  not existing or expired

$cache->get("the_key"); // reeturn default if not existing or expired
$cache->get("the_key", "the default value"); // reeturn default if not existing or expired
$values = $cache->getMultiple(["the_key", "another_key]);
/* $values :
[
    "the_key" => "a value",
    "another_key" => 123
]
*/

$cache->delete("the_key");
$cache->deleteMultiple(["the_key", "another_key]);
$cache->clear(); // delete all the keys
```

## Working with Items

Cache items are containers that can have a key, a value, an expiration and tags.

```php
$item = new CacheItem("key", "value", $expiration);
// or
$item->setKey("key");
$item->set("value");
$item->expiresAfter(3600);

$cache->save($item);
$cahce->saveMultiple([$item, $item2]);

$item = $cache->getItem("key");
// always return an object, with at least the key set

$value = $item->get();
$value = $item->get("a default value"); // get default if isHit() === false

$bool = $item->isHit(); // returns false if key wasn't found or is expired. In that case, value is also null
$timestamp = $item->getExpiration(); // null when nothing set (FileCache always has expiration)
```

## Working with tags

Tags are a convenient way to group and retrieve related items together.

```php
$item = new CacheItem(...);

$item->addTag("the_tag");
$item->setTags(["the_tag", "another_tag"]);

$tags = $item->getTags();

// use setKey(), set()...

$cache->save($item);

$cache->hasTag("the_tag");

$items = $cache->getItemsWithTag("a_tag")

$cache->deleteTag("tag");
```
