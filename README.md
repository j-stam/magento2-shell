# Magento 2 Shell
Creating a new console action can be overkill from time to time. Sometimes we just want to run a simple script on our magento application for example to test some code or run a one time action. 

This package allows you to run actions on your magento installation like we did on magento 1.

## Install
Install the package
```
composer require j-stam/magento2-shell:*
```

Copy the shell folder from this package to youre magento 2 root

## Usage

### Set Area code
The area code defaults to global. To set your desired code overwrite the `protected $appAreaCode;` in your shell script

### Instantiate object
* Create a instance: `public function createInstance($type, array $arguments = [])`
* Get a instance: `public function getInstance($type)`
* Get the object manager: `public function getObjectManager()`

### Export data to csv, xml, json
```php 
$this->io->writeJson($data, 'filename.json')
```

### Read data from csv, xml, json
```php 
$this->io->readJson('filename.json')
```

### Excecute sql querys
You can run sql queries with the standard `\Magento\Framework\DB\Adapter\AdapterInterface`
```php
$sql = 'SELECT * FROM your_table LIMIT 1';
$result = $this->connection->fetchAll($sql);
// or
$query = $this->connection->query('SELECT * FROM your_table LIMIT 1');
$result = $query->fetchAll();
```