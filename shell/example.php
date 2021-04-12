<?php

require_once realpath(dirname(__FILE__) . '/../app/bootstrap.php');

class Example extends \Stam\Shell\ShellAbstract
{

    protected $product;

    // optional
    protected function _construct(
        \Magento\Catalog\Model\Product $product
    ) {
        $this->product = $product;
    }

    public function run()
    {
        // ...
    }
}
$shell = new Example();
$shell->run();
