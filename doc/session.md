# Session and Flash mesages

## Usage
```php
$session = new NativeSession();

// or

$pdo = new \PDO(...);
$options = [
    "table_name" => "my_session_table",
    "cookie_name" => "my_session_cookie",
    "cookie_httponly" => true,
    ...
];
$session = new PDOSession($pdo, $options);

$session->start();
$session->destroy();

$session->setId($id); // replace the session id
$session->getId();
$session->regenerateId();
$session->regenerateId(true); // delete old session

$session->set("set", $value);
$session->has("get");

$session->get("key");
$session->get("key", "default");

$session->delete("key");
$session->deleteAll();

$session->addFlashMessage("error", "error message");
$messages = $session->getFlashMessages("error"); // always returns an array, and delete the key from the session
```
