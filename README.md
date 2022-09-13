Twig Payment Gateway
This extension allows you to use Twig as a payment gateway in your Magento 2 store.

Installation using Composer (Recommended)
composer require twigpay/twigpaymentgateway
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:clean


Manual Setup

In your Magento 2 [ROOT]/app/code/ create folder Magento and  put the extract folder (TwigPaymentGateway).
Open the command line interface and run the following commands :

- Magento setup upgrade: php bin/magento setup:upgrade
- Magento Dependencies Injection Compile: php bin/magento setup:di:compile
- Magento Static Content deployment: php bin/magento setup:static-content:deploy
- Login to Magento Admin and navigate to System/Cache Management
- Flush the cache storage by selecting Flush Cache Storage

Admin Configuration
Login to your Magento Admin
Navigate to Store -> Configuration -> Sales -> Payment Methonds -> TWIG Gateway -> Configure.
Please change the Enable filed to YES and fill Merchant Api Key,Twig Client ID provided by Twig Dashboard.

After you save the configuration the module will be enabled in your Magento 2 Store.


For testing purposes you can use the following keys:
Mechat Api Key >  live_4F5ABA4745DED27AAECCE80CDF30450FAE532773F5D8AEA1BB822B4B13354FEE
Twig Client ID  >    25e31df8-33f6-429c-adcd-e24c1cd8cea4
