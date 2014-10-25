<?php

class Codex_Basictests_Test_Selenium_CheckoutTest extends Codex_Xtest_Xtest_Selenium_TestCase
{

    protected static $_customerEmail;
    protected static $_customerPassword;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $customerConfig = self::getSeleniumConfig('checkout/customer');
        self::$_customerEmail = $customerConfig['email'];

        // Testkunde löschen, dann neuen anlegen
        $customerCol = Mage::getModel('customer/customer')->getCollection();
        $customerCol->addFieldToFilter('email', self::$_customerEmail );
        $customerCol->walk('delete');

        // Neuen Testkunden erstellen
        $customer = Mage::getModel('customer/customer');
        $customer->setData($customerConfig);
        self::$_customerPassword = $customer->generatePassword();
        $customer->setStore( current( Mage::app()->getStores() ) ); // TODO
        $customer->setPassword( self::$_customerPassword );
        $customer->validate();
        $customer->save();
    }


    protected function setUp()
    {
        parent::setUp();
        $this->enableSessionSharing();
    }

    public function testAddToCart()
    {

        $cartConfig = $this->getSeleniumConfig('checkout/addtocart');
        foreach( $cartConfig AS $_productData )
        {

            /** @var $productPageObject Codex_Xtest_Xtest_Pageobject_Frontend_Product */
            $productPageObject = $this->getPageObject('xtest/pageobject_frontend_product');

            $productPageObject->openBySku( $_productData['sku'] );
            $productPageObject->setQty( $_productData['qty'] );

            $productPageObject->pressAddToCart();
            $productPageObject->assertAddToCartMessageAppears();

        }

        /** @var $cartPageObject Codex_Xtest_Xtest_Pageobject_Frontend_Cart */
        $cartPageObject = $this->getPageObject('xtest/pageobject_frontend_cart');
        $cartPageObject->open();

        $cartPageObject->takeResponsiveScreenshots('products in cart');

        $this->assertEquals( count($cartConfig), count( $cartPageObject->getItems() ), 'cart is missing some items' );

        $cartPageObject->clickProceedCheckout();
        $this->assertContains('checkout/onepage/', $this->url() );

        // ---

        /** @var $checkoutPageObject Codex_Xtest_Xtest_Pageobject_Frontend_Checkout */
        $checkoutPageObject = $this->getPageObject('xtest/pageobject_frontend_checkout');

        $checkoutPageObject->takeResponsiveScreenshots('login');
        $checkoutPageObject->login( self::$_customerEmail, self::$_customerPassword );
        $checkoutPageObject->assertStepIsActive('billing');

        // ---

        $checkoutPageObject->setBillingAddress();
        $checkoutPageObject->takeResponsiveScreenshots('billing address');
        $checkoutPageObject->nextStep();

        // ---

        // TODO: Shipping Address

        // ---

        $checkoutPageObject->assertStepIsActive('shipping_method');
        $checkoutPageObject->setShippingMethod();
        $checkoutPageObject->takeResponsiveScreenshots('shipping method');
        $checkoutPageObject->nextStep();

        // ---

        $checkoutPageObject->assertStepIsActive('payment');
        $checkoutPageObject->setPaymentMethod();
        $checkoutPageObject->takeResponsiveScreenshots('payment method');
        $checkoutPageObject->nextStep();

        // ---

        $checkoutPageObject->assertStepIsActive('review');
        $checkoutPageObject->acceptAgreements();
        $checkoutPageObject->takeResponsiveScreenshots('review');
        $checkoutPageObject->nextStep();

        // ---

        $checkoutPageObject->takeResponsiveScreenshots();
        $checkoutPageObject->assertIsSuccessPage();



    }


}