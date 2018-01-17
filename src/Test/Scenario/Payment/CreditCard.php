<?php

namespace SDuval\Prestashop\Manager\Test\Scenario\Payment;


use SDuval\Prestashop\Manager\Test\Scenario\Payment;

class CreditCard extends Payment
{
    public function getModuleName()
    {
        return 'creditcard';
    }

}