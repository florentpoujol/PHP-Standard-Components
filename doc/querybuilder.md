# Query Builder

The query builder helps you create SQL queries in an expressive, fluent way, and if needed to execute them through PDO.

The primary use of the query builder is to build a query string suitable to be passed to the PDO `prepare`, `query` or `exec` methods for instance.

But if supplied with a PDO instance, you can run the query directly from the builder object.


## Query building API

With the exception of the methods to build conditions (`where()`, `having()`, `join()` and `on()` and their derivative), all these methods can be called in any order. 

### Action verbs

__Insert__

```php
$query = new QueryBuilder();
$query->insert("name");
```

This would allow to insert a single field "name"

You can chain calls to `insert()` or supply an array.
```php
$query->insert("name")->insert("email"); // subsequent call add a new field
$query->insert(["name", "email"]); // a call replace all the fields for the query
```

You can also use `insertOrReplace()` instead of `insert()`.

__Update__

```php
$query->update("name");
$query->update("name")->update("email");
$query->update(["name", "email"]);
```

__Select__

```php
$query->select(); 
// same as $query->select("*");

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

You can also use `fromTable()` or `inTable()`.  
Ie: `$query->insert($data)->inTable("the_table");`

### Where

The `where()` method accept one, two or three arguments.   
Values that are not a placeholder (named or `?`) are escaped with `PDO::quote()` (when a PDO instance is supplied to the  query builder, and the PDO driver supports it).

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

You can set the whole expression as a single argument:
```php
$query->where("field = value");
```

Or the single argument can be an associative array of fields and values:
```php
$data = ["theField" => "theValue"];
$query->where($data); // this generate the following query string: theField = :theField
```
In this condition, and if you run the query, you don't need to pass the data again to the `execute()` method. 

You can also use the following helpers:
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
// "WHERE field = value OR otherField IS NULL AND anotherField IN (1, 2, 3)"
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
// "WHERE field = value OR (field = value AND field2 = value) OR anotherField NOT IN (1, 2, 3)"
```

### Join

Use the `join()` and `on()` methods. `on()` works just like `where()`.

```php
$query
    ->join("the_table", "optional_alias")
    ->on("field", ">=", "value");
    ->on("field", "value");
    ->orOn("field LIKE `%John%`");
```

For more join types, you can use `leftJoin()` , `rightJoin()`, `fullJoin()` or pass the join type as the third parameter of `join()`.

```php
$query->join("the_table", null, "LEFT");
// is the same as
$query->leftJoin("the_table");
```

You can add several join clauses. The conditions specified via the `on()` method are linked to the join clause created by the last call to the `join()` method.

```php
$query
    ->join("the_table")->on("field", ">=", "value")
    ->join("the_other_table")->on("field", ">=", "value");
```

### Other select triage methods

__Group By__

```php
$query->groupBy("field")->groupBy("field2");
// or 
$query->groupBy(["field", "field2"]);
```

__Having__

Works just like `where()` and `on()`.

```php
$query
    ->having("field", "=", "value");
    ->orHaving("field", "value");
    ->having("field LIKE '%value%'")
    ->orHaving(function ($query) {
        $query->orHaving("field", "value");
        $query->having("field LIKE '%value%'");
    });
// query string:
// HAVING field = value OR field = value AND field LIKE '%value%' OR (field = value AND field LIKE '%value%')
```

__Order By__

```php
$query->orderBy("field")->orderBy("field2", "desc");
// ORDER BY field ASC, field2 DESC 
```

__Limit and Offset__

```php
$query->limit(10)->offset(5);
// is the same as
$query->limit(10, 5); // note that this the opposite order as MySQL's LIMIT clause which is: LIMIT offset, count
```

## Non query building API

A PDO instance can be passed to the constructor or the `setPdo()` method.

```php
$pdo = new \PDO(...);
$query = new QueryBuilder($pdo)
$query->setPdo($pdo);
$pdo = $query->getPdo();
```

Once a PDO object is set, you can check if the query is actually valid or get a PDO statement that would be returned to a call to `$pdo->prepare()`.

```php
$query = new QueryBuilder($pdo)

$query->select("field")->...

if ($query->isValid()) {
    $statement = $query->prepare(); // PDOStatement
    $statement->bindValue(...);
    ...
}
```

To get the generated query string, call `toString()` or just cast to string.

```php
$query = new QueryBuilder()
$str = $query
    ->select("field")
    ->fromTable("table")
    ->where("field", "value")
    ->toString(); 
// SELECT * FROM table WHERE field = value 
```

## Executing a query directly from the query builder object

Instead of passing the query string to a database object or calling `prepare()`, you can call `execute()` on the query object.

It returns:
- `false` when the query is unsuccessful
- `true` when the query is successful and the action is `INSERT OR REPLACE`, `UPDATE` or `DELETE`.  
- the last inserted id when the action is `INSERT`.  
- the PDOStatement object when the action is `SELECT`.  

```php
$query
    ->delete()
    ->table("table")
    ->execute(); 
// this deletes the whole table content (it doesn't drop the table)
```

As `PDOStatement::execute()`, it accept an array of input parameters which can be
- an associative array of named parameters
- or an in-order array of parameters, when placeholders are ?
 
Unlike `PDOStatement::execute()`, this array can also be an array of these two kinds fo array, which is useful to insert or update several rows with the same query.

```php
$query
    ->insert("field")->insert("field2")
    ->inTable("table")
    ->execute([
        "field" => "value",
        "field2" => "value2",    
    ]); 
// INSERT INTO table (field, field2) VALUES (:field, :field2)

// insert two rows:
$query
    ->insert("field")->insert("field2")
    ->inTable("table")
    ->execute([
        ["value", "value2"],
        ["value", "value2"],
    ]); 
// INSERT INTO table (field, field2) VALUES (?, ?), (?, ?)
```

When all the fields can be inferred from the input params, it is optional to pass them to the action method.

```php
$query
    ->insert()
    ->inTable("table")
    ->execute([
        [
            "field" => "value",
            "field2" => "value2",    
        ],
        [
            "field" => "value",
            "field2" => "value2",    
        ],    
    ]); 
// INSERT INTO table (field, field2) VALUES (?, ?), (?, ?)
```

When that's the case, you can also pass the input param to the action method (and nothing to the execute method)

```php
$data = [
    [
        "field" => "value",
        "field2" => "value2",    
    ],
    [
        "field" => "value",
        "field2" => "value2",    
    ],    
];

$query->insert($data)->inTable("table")->execute();
```

This also works for updates:

```php
$data = [
    "field" => "value",
    "field2" => "value2",    
];

$query->update($data)->inTable("table")->where("id", 1)->execute();
// UPDATE table SET field = :field, field2 = :field2 WHERE id = 1
```
