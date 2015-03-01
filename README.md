# Orm

This is a lightweight standalone ORM in PHP. It was primarly made to use for simple REST APIs.

``It's still a WIP so don't trust everything you see until this message goes away``

## What it does

* Uses PDO as interface
* Provide Model helpers for CRUD
* Provide helpers to access relations
* Use PHPDoc as documentation
* Based on PHP, not in configuration.

## What is not supported and won't be

This library is willingly simplified.

If you want to use these features, there is plenty other ORM available that do it probably better ! 

* Transactions
* Complexes query. Queries are simple, you can't mix "OR" and "AND" in the ORM, even if you can "bypass" that.
* Relations. While there is some support for accessing or creatin model relations, it won't be near what other ORM can provide.
* Multiple databases.

## Installation

Clone the depot and add the Orm namespace in your autoloader.

Then put database informations in your application bootstrap.

```php
$dsn      = 'mysql:dbname=YOUR_DB;host=YOUR_HOST';
$user     = 'YOUR USER';
$password = 'YOUR_PASSWORD';
\Orm\DB::init($dsn, $user, $password);
```

## Documentation


#### Using

## License

Everything here is public domain.
