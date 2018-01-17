<?php
namespace SDuval\Prestashop\Manager\Test\Scenario\Payment;


use SDuval\Prestashop\Manager\Test\Scenario\Payment;

class Paypal extends Payment
{
    public function getModuleName()
    {
        return 'paypal';
    }
}