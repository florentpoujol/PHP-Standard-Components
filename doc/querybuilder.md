# Query Builder

The query builder helps you create SQL queries in an expressive, fluent way, and if needed to execute them through PDO.

The primary use of the query builder is to build a query string suitable to be passed to the PDO `prepare` or `query` method for instance.

But if supplied with a PDO instance, you can run the query directly from the builder object.


In all example below the `$inputParams` variable represent the same argument that would be passe to `PDOStatemeent::Execute()` :
- an associative array: placeholder => value
- a simple array of values: id => value 
- an array of one of those two types


## Query building API

With the exception of the methods to build conditions (`where()`, `having()`, `join()` and `on()` and their derivative), all these methods can be called in any order. 

Create an instance like so:
```php
$query = new QueryBuilder();
```

### Action verb

__Insert__

```php
$query->insert("name");
```

This would allow to insert a single field "name"

You can chain calls to insert or supply an array to insert()
```php
$query->insert("name")->insert("email"); // subsequent call add a new field
$query->insert(["name", "email"]); // a call replace all the fields for the query
```

You can also use `insertOrReplace()` instead of `insert()`;

__Update__

```php
$query->update("name");
$query->update("name")->update("email");
$query->update(["name", "email"]);
```

__Select__

```php
$query->select("name");
// or with an alias
$query->select("name", "alias");
$query->select("name as alias");

$query->select("name")->select("email");
$query->select(["name as alias", "email"]);
```

__Delete__

```php
$query->delete();
```

### Table

Set the query's main table.
 
```php
$query->table("the_table");
$query->table("the_table", "alias");
$query->table("the_table as alias");
```

For more expressiveness, you can also use `fromTable()` or `inTable()`.  
Ie: `$query->insert($data)->inTable("the_table");`

### Where

The where() method accept one, two or three arguments:

```php
$query->where("field", "=", "value");
$query->where("field", ">=", "value");
$query->where("field", "LIKE", "%John%");
```

When there is only two arguments, the equal sign is supposed:
```php
$query->where("field", "value");
// is the same as
$query->where("field", "=", "value");
```

You can set the whole expression as a single argument
```php
$query->where("field = value");
```

For convenience, you can also use the following helpers:
- `whereNull($field)` / `whereNotNull($field)`
- `whereBetween($field, $min, $max)` / `whereNotBetween($field, $min, $max)` 
- `whereIn($field, $values)` / `whereNotIn($field, $values)`

All these method also have an equivalent prefixed with "or" (`orWhere`, `orWhereNotNull`, `orWhereIn`, ...).

```php
$query
    ->where("field = value")
    ->orWhereNull("$otherField")
    ->whereIn("anotherField", [1, 2, 3]);

// this would generate the following query string
WHERE field = value OR otherField IS NULL AND anotherField IN (1, 2, 3)
```

__Nested conditions__

To nest conditions, you must pass a callable to the `where()`method.  
The callable receive the query builder object as only parameter. 

```php
$query
    ->where("field = value")
    ->orWhere(function ($query) {
        $query
            ->where("field", "value")
            ->where("field2", "value2");
    })
    ->orWhereNotIn("anotherField", [1, 2, 3]);

// this would generate the following query string
WHERE field = value OR (field = value AND field2 = value) OR anotherField NOT IN (1, 2, 3)
```

### Join

Use the `join()` and `on()` methods.   
`on()` works like `where()`, it also accept a callable to create nested conditions.

```php
$query
    ->join("the_table", "optional_alias")
    ->on("field", ">=", "value");
    ->on("field", "value");
    ->orOn("field LIKE `%John%`");
```

For more join types, you can use `leftJoin()` , `leftJoin()`, `leftJoin()` or pass the join type as the third parameter of `join()`.

```php
$query->join("the_table", null, "LEFT");
// is the same as
$query->leftJoin("the_table");
```

You can of course add several join clauses. The conditions specified via the `on()` method are linked to the join clauses created by the last call to the `join()` method.

```php
$query
    ->join("the_table")->on("field", ">=", "value")
    ->join("the_other_table")->on("field", ">=", "value");
```

*orderBy($field, $dir = "ASC")
newest($field = "created_at") // same as orderBy("creted_at", "DESC")
oldest()                      // same as orderBy("created_at", "ASC")
last($field = "id") // same as ORDER BY $field DESC LIMIT 1
first($field = "id") // same as ORDER BY $field ASC LIMIT 1

*groupBy(string $field)
*groupBy(string[] $fields)

*having($field, $sign, $value)
*orHaving()

*limit($limit, $offset = null)
*offset($value)





## Non query building API

```php
$query = new QueryBuilder([$pdo])
setPdo($pdo)
getPdo(): PDO

setData($inputParams)
getData(): array

setRaw(string $query)
toString(): string
isValid(): bool   returns if the query is correct (needs PDO)

prepare(): PDOStatement
execute(array $inputParams) 
// return success for update and delete
// return update id or ids for insert
// return results for select
```



## INSERT

```php
$query->insert($inputParams)->inTable("posts")->execute(); // returns false on error, or the last inserted id
// field name (and placeholder or ?) is inferred from data content

$query->insert($fields)->inTable("posts")->execute($inputParams); // allows data to be non associative
// update the query placholders based on data content 

// insertOrReplace()


insert()->table($table)->fields($fields)
insert($fields)->table($table)
insert($inputParams)->inTable($table)
```

## SELECT

```php
$query->table($table)->where(...)->select($inputParams); // get()

$query->selectFrom($table)->fields()->where()->execute($inputParams);

select($conds)->inTable($table)->where($conds)->execute
select($fields)->inTable($table)->where($conds)->execute($inputParams)
```

## UPDATE

```php
update($inputParams)->inTable($table)->where()->execute();
update($fields)->inTable($table)->where()->execute($inputParams);

table()->where()->update($inputParams)
```

## DELETE

```php
$query->deleteFrom($table)->where($inputParams)->execute();
$query->deleteFrom($table)->orWhere($inputParams)->execute();
// $inputParams must be an assoc array
$query->deleteWhere($cond)->inTable($table)->execute();

table()->where("id", "?")->delete($cond)
delete()->table()->where("id", "?")->execute()
```

