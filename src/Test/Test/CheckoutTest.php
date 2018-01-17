<?php
namespace SDuval\Prestashop\Manager\Test\Test;

use SDuval\Prestashop\Manager\Test\CheckoutTestInterface;
use SDuval\Prestashop\Manager\Test\Scenario\Payment\Bankwire;
use SDuval\Prestashop\Manager\Test\Scenario\Payment\Check;
use SDuval\Prestashop\Manager\Test\TestCase;
use SDuval\Prestashop\Manager\Test\Scenario\CheckoutScenario;


class CheckoutTest extends TestCase implements CheckoutTestInterface
{


    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::createProduct();
        self::setConfigurationValue('PS_GIFT_WRAPPING', 1);
        self::setConfigurationValue('PS_GIFT_WRAPPING_PRICE', 5);
    }

    /**
     * @dataProvider scenarioProvider
     *
     */
    public function testCheckout(CheckoutScenario $scenario)
    {

        // add a product to cart in order to enable checkout process
        $this->addSomeProductToCart();

        $url = $this->prestashop['urls']['pages']['order'];
        // go to checkout process
        $this->url($url);

        // Step 1 - Personal Information
        $this->byId("checkout-personal-information-step");
        $this->byId("customer-form");
        $this->byCssSelector('[data-link-action="show-login-form"]');

        if($scenario->customerOrdersAsGuest || $scenario->customerDoesntHaveAnAccount) {
            $this->createAccount($scenario);
        } else {
            $this->loginForm($scenario);
        }

        // 2 - Addresses
        $this->stepAddress($scenario);

        // 3 - Shipping method
        $this->stepShippingMethod($scenario);

        // 4 - Payment
        $this->stepPayment($scenario);

        // User must be logged if not guest
        if(!$scenario->customerOrdersAsGuest) {
            $this->waitUntil(function (){
               return $this->byCssSelector('a.account')->displayed();
            });
        }
    }

    protected function createAccount(CheckoutScenario $scenario)
    {
        // Setup form
        $this->byCssSelector("#customer-form [name=firstname]")->value($scenario->customer->firstname);
        $this->byCssSelector("#customer-form [name=lastname]")->value($scenario->customer->lastname);
        $this->byCssSelector("#customer-form [name=email]")->value($scenario->customer->email);

        if(!$scenario->customerOrdersAsGuest) {
            $this->byCssSelector("#customer-form [name=password]")->value($scenario->customer->password);
        }

        $this->byCssSelector('#customer-form button[data-link-action="register-new-customer"]')->click();
    }

    protected function loginForm(CheckoutScenario $scenario)
    {
        $this->byCssSelector('[data-link-action="show-login-form"]')->click();

        $this->waitUntil(function(){
           return $this->byCssSelector('#login-form')->displayed();
        });

        $this->byCssSelector("#login-form [name=email]")->value($scenario->customer->email);
        $this->byCssSelector("#login-form [name=password]")->value($scenario->customer->password);
        $this->byCssSelector('#login-form button[data-link-action="sign-in"]')->click();
    }

    public function stepAddress(CheckoutScenario $scenario)
    {
        $this->waitUntil(function(){
            return $this->byCssSelector('#checkout-addresses-step.-reachable')->displayed();
        });

        $customer = $scenario->customer;
        if(!$scenario->customerHasAnAddress) {

            $address = $this->generateAddress();

            try {

                $this->byCssSelector('.address-item');
                $this->fail('User should not have any address');

            } catch(\Exception $e) {

            }

            // Form delivery address should be visible
            $this->byCssSelector('form #delivery-address');

            // the delivery address form should have the customer firstname and lastname pre-filled
            $this->assertEquals($customer->firstname,
                $this->byCssSelector('#delivery-address [name=firstname]')->value());
            $this->assertEquals($customer->lastname,
                $this->byCssSelector('#delivery-address [name=lastname]')->value());

            // Fill the address form and submit
            $this->byCssSelector('#delivery-address [name=address1]')->value($address->address1);
            $this->byCssSelector('#delivery-address [name=city]')->value($address->city);
            $this->byCssSelector('#delivery-address [name=postcode]')->value($address->postcode);
            $this->byCssSelector('#delivery-address [name=phone]')->value($address->phone);

            $this->byCssSelector('#delivery-address button')->click();
        } else {

            if(!$this->byCssSelector('[name="id_address_delivery"]')->selected()) {
                $this->fail('should have an existing address pre-selected');
            }

            $this->byCssSelector('#checkout-addresses-step button.continue')->click();
        }

        if(!$scenario->deliveryAddressIsInvoiceAddress) {

            $invoiceAddress = $this->generateAddress();

            $this->byCssSelector('#checkout-addresses-step')->click();
            $this->byCssSelector('[data-link-action="different-invoice-address"]')->click();

            $this->byCssSelector('#invoice-address [name=firstname]')->value($invoiceAddress->firstname);
            $this->byCssSelector('#invoice-address [name=lastname]')->value($invoiceAddress->lastname);
            $this->byCssSelector('#invoice-address [name=address1]')->value($invoiceAddress->address1);
            $this->byCssSelector('#invoice-address [name=postcode]')->value($invoiceAddress->postcode);
            $this->byCssSelector('#invoice-address [name=city]')->value($invoiceAddress->city);

            $this->byCssSelector('#invoice-address button')->click();

            $this->waitUntil(function(){
                return $this->byCssSelector('#checkout-delivery-step.-current')->displayed();
            });
        }

        // Delivery should be done
        $this->waitUntil(function(){
            return $this->byCssSelector('#checkout-addresses-step.-complete')->displayed();
        });
    }

    public function stepShippingMethod(CheckoutScenario $scenario)
    {
        $this->byCssSelector('#checkout-delivery-step.-current');
        $this->byCssSelector('.delivery-options-list');

        $totalPriceSelector = '.cart-summary-line.cart-total .value';
        $shippingPriceSelector = '#cart-subtotal-shipping .value';

        // Current prices
        $totalPrice = $this->byCssSelector($totalPriceSelector)->text();
        $shippingPrice = $this->byCssSelector($shippingPriceSelector)->text();

        // When choosing another carrier delivery price should change and total as well
        $this->byCssSelector('#delivery_option_2')->click();

        $this->waitUntil(function () use($shippingPrice, $shippingPriceSelector){
            if($shippingPrice == $this->byCssSelector($shippingPriceSelector)->text()) {
                return null;
            }
            return true;
        });
        $this->assertNotEquals($shippingPrice, $this->byCssSelector($shippingPriceSelector)->text());
        $this->assertNotEquals($totalPrice, $this->byCssSelector($totalPriceSelector)->text());

        // Test add gift wrapping
        $this->byCssSelector('input.js-gift-checkbox')->click();

        $this->waitUntil(function(){
            return $this->byCssSelector('#cart-subtotal-gift_wrapping')->displayed();
        });

        // Got to next step
        $this->byCssSelector('#checkout-delivery-step button')->click();

        $this->waitUntil(function(){
           return $this->byCssSelector('#checkout-delivery-step.-complete')->displayed();
        });
    }

    public function stepPayment(CheckoutScenario $scenario)
    {
        $this->byCssSelector('#conditions-to-approve');

        if($this->byCssSelector('[name="conditions_to_approve[terms-and-conditions]"]')->selected()) {
            $this->fail('Terms and condition should not be checked');
        }

        // there must be payment options
        $this->byCssSelector('.payment-options .payment-option');

        $confirmationButton = $this->byCssSelector('#payment-confirmation button');
        if($confirmationButton->enabled() ) {
            $this->fail('Confirmation button must be disabled if no payment option checked');
        }

        // Check terms and conditions
        $this->byCssSelector('[name="conditions_to_approve[terms-and-conditions]"]')->click();

        // Choose a payment option
        $payment = $scenario->getPayment();
        if($payment) {
            $this->byCssSelector('.payment-options [data-module-name="'.$payment->getModuleName().'"')->click();
            $payment->afterSelected($this);
        } else {
            $this->byCssSelector('.payment-options .payment-option label')->click();
        }

        // Click on confirmation button
        $confirmationButton->click();

        $this->waitUntil(function(){
            return $this->byCssSelector('.page-order-confirmation')->displayed();
        }, 10000);

        if($payment) {
            $payment->afterConfirmation($this);
        }

    }

    public function scenarioProvider()
    {
        $scenarios = array();

        // Guest checkout (without password)
        $guestCustomer = $this->generateCustomer();
        $scenario = new CheckoutScenario();
        $scenario->customerOrdersAsGuest = true;
        $scenario->customerDoesntHaveAnAccount = true;
        $scenario->customerHasAnAddress = false;
        $scenario->deliveryAddressIsInvoiceAddress = true;
        $scenario->customer = $guestCustomer;
        $scenario->setPayment(new Check());
        $scenarios["Guest checkout"] = array($scenario);

        /*
        // Registration checkout
        $memberCustomer = $this->generateCustomer();
        $scenario = new CheckoutScenario();
        $scenario->customerOrdersAsGuest = false;
        $scenario->customerDoesntHaveAnAccount = true;
        $scenario->customerHasAnAddress = false;
        $scenario->deliveryAddressIsInvoiceAddress = true;
        $scenario->customer = $memberCustomer;
        $scenario->setPayment(new Bankwire());
        $scenarios["New member checkout"] = array($scenario);

        // Member checkout(reuse membercustomer)
        $scenario = new CheckoutScenario();
        $scenario->customerOrdersAsGuest = false;
        $scenario->customerDoesntHaveAnAccount = false;
        $scenario->customerHasAnAddress = true;
        $scenario->deliveryAddressIsInvoiceAddress = false;
        $scenario->customer = $memberCustomer;
        $scenarios["Member checkout"] = array($scenario);*/

        return $scenarios;
    }
}