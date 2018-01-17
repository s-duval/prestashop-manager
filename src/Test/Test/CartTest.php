<?php
namespace SDuval\Prestashop\Manager\Test\Test;

use SDuval\Prestashop\Manager\Test\CartTestInterface;
use SDuval\Prestashop\Manager\Test\TestCase;

class CartTest extends TestCase implements CartTestInterface
{

    protected $selectors = array(
        'cartSelector' => '.js-cart',
        'cartProductCountSelector' => '.js-subtotal',
        'increaseProductQuantitySelector' => '.js-increase-product-quantity',
        'decreaseProductQuantitySelector' => '.js-decrease-product-quantity',
        'cartItemSelector' => '.cart-item',
        'removeProductFromCartSelector' => '.cart-item .remove-from-cart'
    );


    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::shareSession(true);
    }

    public function testAddProductToCart()
    {
        $this->addSomeProductToCart();

        $url = $this->prestashop['urls']['pages']['cart'];
        $this->url($url);

        $this->assertEquals($this->getProductQuantityText(1), $this->byCssSelector($this->selectors['cartProductCountSelector'])->text());
    }

    /**
     * @depends testAddProductToCart
     */
    public function testIncreaseProductQuantity()
    {
        $url = $this->prestashop['urls']['pages']['cart'];
        $this->url($url);

        $this->increaseProductQuantity();
        $this->assertEquals($this->getProductQuantityText(2), $this->byCssSelector($this->selectors['cartProductCountSelector'])->text());
    }

    /**
     * @depends testIncreaseProductQuantity
     */
    public function testDecreaseProductQuantity()
    {
        $url = $this->prestashop['urls']['pages']['cart'];
        $this->url($url);

        $this->decreaseProductQuantity();
        $this->assertEquals($this->getProductQuantityText(1), $this->byCssSelector($this->selectors['cartProductCountSelector'])->text());
    }

    /**
     * @depends testAddProductToCart
     */
    public function testRemoveProduct()
    {
        $url = $this->prestashop['urls']['pages']['cart'];
        $this->url($url);

        $this->byCssSelector($this->selectors['removeProductFromCartSelector'])->click();
        $this->waitUntil(function(){
            try {
                $this->byCssSelector($this->selectors['cartItemSelector']);
                return null;
            } catch (\Exception $e) {
            }

            return true;
        });
    }

    public function increaseProductQuantity()
    {
        $oldQuantity = $this->byCssSelector($this->selectors['cartProductCountSelector'])->text();
        $this->byCssSelector($this->selectors['increaseProductQuantitySelector'])->click();
        $this->waitUntil(function() use($oldQuantity){

            if($oldQuantity == $this->byCssSelector($this->selectors['cartProductCountSelector'])->text()) {
                return null;
            }

            return true;
        });
    }

    public function decreaseProductQuantity()
    {
        $oldQuantity = $this->byCssSelector($this->selectors['cartProductCountSelector'])->text();
        $this->byCssSelector($this->selectors['decreaseProductQuantitySelector'])->click();
        $this->waitUntil(function() use($oldQuantity){

            if($oldQuantity == $this->byCssSelector($this->selectors['cartProductCountSelector'])->text()) {
                return null;
            }

            return true;
        });
    }

    public function getProductQuantityText($quantity)
    {
        $value = $quantity > 1 ? $quantity.' articles' : $quantity.' article';
        return $value;
    }
}