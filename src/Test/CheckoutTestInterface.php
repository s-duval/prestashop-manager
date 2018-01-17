<?php
namespace SDuval\Prestashop\Manager\Test;

use SDuval\Prestashop\Manager\Test\Scenario\CheckoutScenario;

interface CheckoutTestInterface
{
    public function testCheckout(CheckoutScenario $scenario);
}