<?php
namespace SDuval\Prestashop\Manager\Test\Test;

use SDuval\Prestashop\Manager\Test\CategoryTestInterface;
use SDuval\Prestashop\Manager\Test\TestCase;

class CategoryTest extends TestCase implements CategoryTestInterface
{

    protected static $nbPages = 2;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // Create self::$nbPages of products in test category
        $productsPerPage = self::getConfigurationValue('PS_PRODUCTS_PER_PAGE');
        $nbProducts = rand($productsPerPage*(self::$nbPages-1)+1, $productsPerPage*self::$nbPages);
        for($i = 0; $i < $nbProducts; $i++) {
            static::createProduct();
        }

    }


    public function testSortByPrice()
    {
        $category = self::getCategory();
        $categoryUrl = $category->getLink();

        // Order products by price asc
        $url = $categoryUrl.'?order=product.price.asc';
        $this->url($url);

        $currentPrice = false;
        for($currentPage = 1; $currentPage < self::$nbPages; $currentPage++) {
            $products = $this->getCategoryProductElements();
            foreach ($products as $product) {
                $price = $this->priceToFloat($product->byCssSelector('.price')->text());

                if ($currentPrice === false) {
                    $currentPrice = $price;
                    continue;
                }

                $this->assertTrue($price >= $currentPrice, 'Product arent order properly :' . $price . ' should be superior to' . $currentPrice);
                $currentPrice = $price;
            }

            // Go to next page
            $this->byCssSelector('a.next')->click();
        }

        // Order products by price desc
        $url = $categoryUrl.'?order=product.price.desc';
        $this->url($url);

        $products = $this->getCategoryProductElements();
        $currentPrice = false;
        foreach ($products as $product) {
            $price = $this->priceToFloat($product->byCssSelector('.price')->text());

            if($currentPrice === false) {
                $currentPrice = $price;
                continue;
            }

            $this->assertTrue($price <= $currentPrice, 'Product arent order properly :'.$price.' should be inferior to'.$currentPrice);
            $currentPrice = $price;
        }

        return true;
    }

    public function testSortByName()
    {
        $category = self::getCategory();
        $categoryUrl = $category->getLink();

        // Order products by name asc
        $url = $categoryUrl.'?order=product.name.asc';
        $this->url($url);

        $products = $this->getCategoryProductElements();
        $currentName = false;
        for($currentPage = 1; $currentPage < self::$nbPages; $currentPage++) {
            foreach ($products as $product) {
                $name = $product->byCssSelector('.product-title a')->text();

                if ($currentName === false) {
                    $currentName = $name;
                    continue;
                }

                $this->assertTrue($name >= $currentName, 'Product arent order properly :' . $name . ' should be before to' . $currentName);
                $currentName = $name;
            }
            // Go to next page
            $this->byCssSelector('a.next')->click();
        }

        // Order products by name asc
        $url = $categoryUrl.'&order=product.name.desc';
        $this->url($url);

        $products = $this->getCategoryProductElements();
        $currentName = false;
        foreach ($products as $product) {
            $name = $product->byCssSelector('.product-title a')->text();

            if($currentName === false) {
                $currentName = $name;
                continue;
            }

            $this->assertTrue($name <= $currentName, 'Product arent order properly :'.$name.' should be after to'.$currentName);
            $currentName = $name;
        }
    }


    /**
     * @return PHPUnit_Extensions_Selenium2TestCase_Element[]
     */
    public function getCategoryProductElements()
    {
        return $this->elements($this->using('css selector')->value('article.product-miniature'));
    }
}