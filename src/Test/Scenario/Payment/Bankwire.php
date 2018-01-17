<?php
namespace SDuval\Prestashop\Manager\Test\Scenario\Payment;

use SDuval\Prestashop\Manager\Test\Scenario\Payment;

class Bankwire extends Payment
{
    function getModuleName()
    {
        return 'ps_wirepayment';
    }

}