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
