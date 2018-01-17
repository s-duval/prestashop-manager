<?php
namespace SDuval\Prestashop\Manager\Test;

interface CartTestInterface
{
    public function testAddProductToCart();
    public function testIncreaseProductQuantity();
    public function testDecreaseProductQuantity();
    public function testRemoveProduct();
}