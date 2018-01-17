<?php
namespace SDuval\Prestashop\Manager\Test\Test;

use SDuval\Prestashop\Manager\Test\HomeTestInterface;
use SDuval\Prestashop\Manager\Test\TestCase;

class HomeTest extends TestCase implements HomeTestInterface
{

    public function testTitle()
    {
        $this->url('/');
        $this->byTag('h1');
    }

}