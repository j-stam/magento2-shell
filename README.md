# Magento 2 Shell
Creating a new console action can be overkill from time to time. Sometimes we just want to run a simple script on our magento application for example to test some code or run a one time action. 

This package allows you to run actions on your magento installation like we did on magento 1.

## Install
Install the package
```
composer require j-stam/magento2-shell:*
```

Copy the shell folder from this package to your magento 2 root

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

### Write output to a log file
You can write output to a log file using monologger. To set your log file name overwrite the `protected $logFileName`.
When this variable is not set, your class name will be converted from PascalCase to lowercase seperated with dashes.

To write output to your log use the `protected $logger`
```php
$this->logger->error('Your error message');
$this->logger->info('Your info message');
...
```
All log files are placed in `{magento_root}/var/log/shell/` by default. To specify your own log file path overwrite the `protected $logFilePath` variable. 
