<?php

namespace SDuval\Prestashop\Manager\Test;


interface CustomerTestInterface
{
    public function testRegister();
    public function testLogout();
    public function testLoginFail();
    public function testLogin();
    public function testAddresses();
    public function testIdentity();
}