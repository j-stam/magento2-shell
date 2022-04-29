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
The area code defaults to global. 
To use a different area code you can set it by overwriting the `protected $appAreaCode;` in your shell script

The following areas can be set:
* `\Magento\Framework\App\Area::AREA_GLOBAL` or `'global'`
* `\Magento\Framework\App\Area::AREA_FRONTEND` or `'frontend'`
* `\Magento\Framework\App\Area::AREA_ADMINHTML` or `'adminhtml'`
* `\Magento\Framework\App\Area::AREA_DOC` or `'doc'`
* `\Magento\Framework\App\Area::AREA_CRONTAB` or `'crontab'`
* `\Magento\Framework\App\Area::AREA_WEBAPI_REST` or `'webapi_rest'`
* `\Magento\Framework\App\Area::AREA_WEBAPI_SOAP` or `'webapi_soap'`
* `\Magento\Framework\App\Area::AREA_GRAPHQL` or `'graphql'`

### Set Is Secure
Some magento actions for instance removing products are required to be executed in a secure area next to being executed
in the adminhtml area. Use `protected function setIsSecureArea($isSecure)` to set wether the current area is secure or not.

### Instantiate object
* Create a instance: `public function createInstance($type, array $arguments = [])`
* Get a instance: `public function getInstance($type)`
* Get the object manager: `public function getObjectManager()`

**Use dependency injection.**

The dependency injection uses the `public function getInstance($type)` to load your dependency. 
If you want to create a new instance, inject a factory for the object you are trying to create and use its create method 
or use the createInstance method instead of dependency injection

```php
protected function di(
    \Magento\Catalog\Model\Product $product
) {
    $this->product = $product;
}
```

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

### Write output to terminal
Available in the shell is the [symfony console output class](https://symfony.com/doc/current/console.html#console-output).
This can be used directly by accessing the `protected $consoleOutput`. Or if you just want to write (a line) you can use the
public functions `write($messages, $newLine = true, $options = OutputInterface::OUTPUT_NORMAL)` and `writeln($message, $options = OutputInterface::OUTPUT_NORMAL)`
```php
$this->writeln('Your line');
$this->write('Your message')
$this->write(['Your message', 'Your other message'])
```

## Example script
```php
<?php

require_once realpath(dirname(__FILE__) . '/../app/bootstrap.php');

class Example extends \Stam\Shell\ShellAbstract
{
    protected $product;

    /**
     * Optional function, can be used for dependency injection
     */
    protected function di(
        \Magento\Catalog\Model\Product $product
    ) {
        $this->product = $product;
    }

    public function run()
    {
        $this->writeln('Hello world!');
        $this->logger->debug('Hello world!');
    }
}
$shell = new Example();
$shell->run();
```