<?php

namespace SDuval\Prestashop\Manager\Test\Scenario;

use PHPUnit\Framework\Assert;
use SDuval\Prestashop\Manager\Test\TestCase;

abstract class Payment extends Assert
{
    abstract public function getModuleName();
    public function afterSelected(TestCase $test){}
    public function afterConfirmation(TestCase $test){}

}