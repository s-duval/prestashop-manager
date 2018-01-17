<?php

namespace SDuval\Prestashop\Manager\Test\Scenario\Payment;

use SDuval\Prestashop\Manager\Test\Scenario\Payment;
use SDuval\Prestashop\Manager\Test\TestCase;

class Check extends Payment
{

    public function getModuleName()
    {
        return 'ps_checkpayment';
    }

    public function afterConfirmation(TestCase $test)
    {
        $this->assertContains('Veuillez nous envoyer un chèque avec', $test->byCssSelector('#content-hook_payment_return')->text());
    }

}