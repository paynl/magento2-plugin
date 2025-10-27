<p align="center">
    <img src="https://www.pay.nl/uploads/1/brands/main_logo.png" />
</p>
<h1 align="center">Magento 2 Plugin</h1>
  
All the payment options your Magento 2 store needs — in one plugin.

For an overview off all features check https://docs.pay.nl/plugins#magento-2

# Requirements
    Minimum PHP 8.1  
    Maximum PHP 8.4  
    Magento minimum version: 2.4.4
    Magento maximum version: 2.4.8  

For manual installation (non-composer), include:

    PHP-SDK: https://github.com/paynl/php-sdk
    Minimum version: 1.0.1

# Installation
#### Installing

In command line, navigate to the installation directory of Magento2

Enter the following commands:

```
composer require paynl/magento2-plugin
php bin/magento setup:upgrade
php bin/magento cache:clean
```

The plugin is now installed


##### Setup

1. Log into the Magento Admin
2. Go to *Stores* / *Configuration*
3. Go to *Sales* / *Payment Methods*
4. Scroll down to find the PAY. Settings
5. Enter the API token and serviceID (these can be found in the PAY. Admin Panel --> https://admin.pay.nl/programs/programs
6. Save the settings
7. Enable the desired payment methods and set allowed countries
8. Save the settings

Go to the *Manage* / *Services* tab in the PAY. Admin Panel to enable extra payment methods.   

#### Update instructions

In command line, navigate to the installation directory of Magento2

Enter the following commands:

```
composer update paynl/magento2-plugin -W
php bin/magento setup:upgrade
php bin/magento cache:clean
```

The plugin has now been updated

# Support
https://www.pay.nl

Contact us: support@pay.nl
