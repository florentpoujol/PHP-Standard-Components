

// SimpleCacheInterface

getValue(string $key, mixed $defaultValue): mixed
getValues(string[] $Keys, mixed $defaultValue): mixed[]

setValue(string $key, mixed $value, mixed $expiration): bool
setValues(array $values, mixed $expiration): bool
key => values

$expiration is int DateTime DateInterval
when of type int, it is considered a timestamp when superior or equal to the current timestamp, ttl otherwise

has(string $key): bool
has(array $keys): array
    key => bool

delete(string $key): bool
delete(array $keys): array
    key => bool
deleteExpired(): bool
deleteAll(): bool

// ItemCache

getItem(string $key): Item
getItems(string[] $keys): Item[]

setItem(string $key, Item $value, bool $deferred): bool
setItems(Item[] $items, bool $defered): bool

has(string $key): bool
has(array $keys): array
    key => bool

delete(string $key): bool
delete(array $keys): array
    key => bool
deleteExpired(): bool
deleteAll(): bool


// has delete

// DeferredCache extends ItemCache
setItem(string $key, Item $value, bool $deferred = false): bool
setItems(Item[] $items, bool $defered = false): bool
commit();

// TaggedCache extends ItemCache

getItemWithTag(string) Item
getItemsWithTag(string) Item[]
getItemWithTags(string[] $tags): Item
getItemsWithTags(string[] $tags): Item[]

hasTag(string $tag): bool
hasTags(array $tags): array
    tags => bool
    
deleteTag(string $tag): bool
deleteTags(array $tags): bool

// TaggedItem



>
must use an item to save defered or use tags


// item
getKey(): string
setKey(string $key)
getValue([mixed $defaultValue])
setValue(mixed $value)
isHit(): bool
setExpiration(int|DateTime|DateInterval)
getExpiration(): int // timestamp   -1 when no expiration

setTag(string $tag)
setTags(string[] $tags)
getTags(): string[]



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






// file
garde 1 fichier serialisé contenant un array: "keysByTags"
seulement lu/écrit lorsque l'on ajoute utilise l'une des fonctions de tag


// PDO



*
