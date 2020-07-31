<?php

require_once realpath(dirname(__FILE__) . '/../app/bootstrap.php');

class Example extends \Stam\Shell\ShellAbstract
{

    public function run()
    {
        // ...
    }
}
$shell = new Example();
$shell->run();
