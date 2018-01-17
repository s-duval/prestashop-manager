<?php
namespace SDuval\Prestashop\Manager\Test;

use Faker\Factory;
use PHPUnit\Exception;

abstract class TestCase extends \PHPUnit_Extensions_Selenium2TestCase
{
    /**
     * @var \PHPUnit_Extensions_Selenium2TestCase_ScreenshotListener
     */
    protected static $screenshotListener;

    protected static $lang = 'en';
    protected static $base_url = PS_URL;
    protected static $id_shop = null;

    // categories for test
    protected static $categories = array();

    // products for test
    protected static $products = array();

    // ps configuration to restore settings after tests
    protected static $psConfiguration = array();

    protected static $faker;

    protected $prestashop;

    public function setUp()
    {
        $this->setDesiredCapabilities([
            'plateform' => 'Windows 10',
            'version' => '63',
            'screenResolution' => '1600x1200',
            'chromeOptions' => ['args' => ['--headless', '--disable-gpu', '--window-size=1600,1200']]
            //'chromeOptions' => ['args' => ['--window-size=1600,1200']]
        ]);

        $this->setBrowser('chrome');

        $browserUrl = self::$base_url;
        /*
        if(self::$lang) {
            $browserUrl .= self::$lang.'/';
        }*/

        $this->setBrowserUrl($browserUrl);
    }


    public static function setUpBeforeClass()
    {
        $dir = SCREENSHOT_DIR;
        if(!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        self::$faker = Factory::create('fr_FR');
        self::setDefaultWaitUntilTimeout(20000);
    }

    public static function tearDownAfterClass()
    {
        // Delete every categories created
        if(isset(static::$categories[get_class()])) {
            foreach (static::$categories[get_class()] as $category) {
                $category->delete();
            }
        }

        // Delete every products created
        if(isset(static::$products[get_class()])) {
            foreach (static::$products[get_class()] as $product) {
                $product->delete();
            }
        }

        // Restore settings
        if(isset(static::$psConfiguration)) {
            foreach (static::$psConfiguration as $key => $value) {
                static::setConfigurationValue($key, $value);
            }
        }
    }

    public function setUpPage()
    {
        // Go home to get urls from javascript
        $this->url('/');
        $this->initPrestashopObject();
    }

    public function onNotSuccessfulTest(\Throwable $e)
    {

        $file = SCREENSHOT_DIR . '/' . get_class($this) . '__' . date('Y-m-d\TH-i-s') . '.png';
        file_put_contents($file, $this->currentScreenshot());

        parent::onNotSuccessfulTest($e);
    }

    public function addSomeProductToCart()
    {
        $product = static::getProduct();

        //Get category with product
        $category = static::getCategory();

        $this->url($category->getLink());
        $this->byCssSelector('.product-miniature:nth-of-type(1) a.product-thumbnail')->click();
        $this->byCssSelector('[data-button-action="add-to-cart"]')->click();
        $this->waitUntil(function() {
            return $this->byCssSelector('#blockcart-modal');
        });
    }

    public function initPrestashopObject()
    {
        try {
            $this->prestashop = $this->execute(array("script" => "return prestashop", "args" => array()));
        } catch (Exception $e) {
        }

        return $this->prestashop;
    }


    public function generateAddress()
    {
        $faker = Factory::create('fr_FR');

        $address = new \stdClass();
        $address->firstname = $faker->firstName;
        $address->lastname = $faker->lastName;
        $address->address1 = $faker->streetAddress;
        $address->postcode = '91000';
        $address->city = $faker->city;
        $address->phone = $faker->phoneNumber;

        return $address;
    }

    public function generateCustomer()
    {
        $faker = Factory::create('fr_FR');

        $customer = new \stdClass();
        $customer->email = $faker->email;
        $customer->firstname = $faker->firstName;
        $customer->lastname = $faker->lastName;
        $customer->password = '111111';

        return $customer;
    }

    /**
     * Convert a price to a float
     * @param $s
     * @return float
     */
    public function priceToFloat($s)
    {
        // convert "," to "."
        $s = str_replace(',', '.', $s);
        // remove all but numbers "."
        $s = preg_replace("/[^0-9\.]/", "", $s);

        // check for cents
        $hasCents = (substr($s, -3, 1) == '.');
        // remove all seperators
        $s = str_replace('.', '', $s);
        // insert cent seperator
        if ($hasCents)
        {
            $s = substr($s, 0, -2) . '.' . substr($s, -2);
        }
        // return float
        return (float) $s;
    }

    public static function getConfigurationValue($key, $id_lang = null)
    {
        return \Configuration::get($key, $id_lang, self::$id_shop);
    }

    public static function setConfigurationValue($key, $value)
    {
        // store current value in order to restore later
        self::$psConfiguration[$key] = self::getConfigurationValue($key);

        \Configuration::updateValue($key, $value);
    }

    public static function getCategory($createIfNoneExists = true)
    {
        if(!isset(static::$categories[get_class()]) || count(static::$categories) == 0 && $createIfNoneExists) {
            return static::createCategory();
        }

        $key = array_rand(static::$categories[get_class()]);
        return static::$categories[get_class()][$key];
    }

    public static function createCategory()
    {
        $languages = \Language::getLanguages();

        // Create a new category test
        $category = new \Category();
        $category->id_parent = 1;
        foreach ($languages as $language) {
            $id_lang = $language['id_lang'];
            $category->name[$id_lang] = '[TEST]'.self::$faker->word();
            $category->link_rewrite[$id_lang] = self::$faker->slug();
        }
        $category->add();

        static::$categories[get_class()][] = $category;

        return $category;
    }

    public static function getProduct($createIfNoneExists = true)
    {
        if(!isset(static::$products[get_class()]) || count(static::$products[get_class()]) == 0 && $createIfNoneExists) {
            return static::createProduct();
        }

        $key = array_rand(static::$products[get_class()]);
        return static::$products[get_class()][$key];
    }

    public static function createProduct()
    {
        $languages = \Language::getLanguages();

        $category = static::getCategory();
        $product = new \Product();
        foreach($languages as $language) {
            $id_lang = $language['id_lang'];
            $product->name = self::$faker->word;
            $product->price = self::$faker->randomNumber(2);
            $product->id_category_default = $category->id_category;
        }
        $product->add();

        // Add stock to it so its available
        \StockAvailable::setQuantity($product->id, 0, rand(1, 20));

        // Add product to a category
        $product->addToCategories($category->id);

        static::$products[get_class()][] = $product;

        return $product;
    }

}