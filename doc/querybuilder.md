# Query Builder

The query builder helps you create SLQ queries in an expressive, fluent way.

The primary use of the query builder is to build a query string suitable to be passed to the PDO `prepare` or `query` method for instance.

But if supplied with a PDO instance, you can run the query directly from the builder object.


In all example below the `$inputParams` variable represent the same argument that would be passe to `PDOStatemeent::Execute()` :
- an associative array: placeholder => value
- a simple array of values: id => value 
- an array of one of those two types


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
// return insert id or ids for insert
// return results for select
```

## QueryBuilding API

First some example of use

 
```php
*insert() // fields taken from input
*insert(string $field)
*insert(string[] $fields)
*insert($inputParams)
*insertOrReplace()

*select() // will default to *
*select(string $field) //  ie: select("field, field2")
*select($fields)
select($inputParams)

*update()
*update($fields)
update($inputParams)

*delete()
delete($inputParams)

*table($table[, $alias])
*fromTable()
*inTable()


*where($statement) // ie where("field = value")
*where($field, $sign, $value) // $value can be placeholder
*where($field, $value) // $sign = equal

*whereNull($field)
*whereNotNull()

*whereBetween($field, $min, $max) // whereNotBetween
*whereNotBetween()

*whereIn($field, $values) // whereNotIn
*whereNotIn()

// same version with OR: orWhere()...

// group where
*where(function($query) {
    
})

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

on($field, $sign, $value)
join($table, $onLeft, $onSign, $onRight[, $joinType = null])
join($table, $onLeft, $onRight[, $joinType = null]) // on sign = '='
innerJoin()
leftJoin()
rightJoin()
join($table, function($query) {
    $query->on($field, $sign, $value);
    $query->on($field, $value);
    $query->orOn();
});

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

