<p align="center">
    <img src="https://www.pay.nl/uploads/1/brands/main_logo.png" />
</p>
<h1 align="center">Pay. Magento2 plugin</h1>
  
This plugin provides your Magento2 webshop with all the payment methods you need. 

For an overview off all features check https://docs.pay.nl/plugins#magento-2.
## Index
- [Available payment methods](#available-payment-methods)
- [Requirements](#requirements)
- [Installation](#installation)
- [Update instructions](#update-instructions)
- [Usage](#usage)
- [Support](#support)

# Available payment methods

Bank Payments  | Creditcards | Gift cards & Vouchers | Pay by invoice | Others | 
:-----------: | :-----------: | :-----------: | :-----------: | :-----------: |
iDEAL + QR |Visa | VVV Cadeaukaart | Riverty | PayPal |
Bancontact + QR |  Mastercard | Webshop Giftcard | Achteraf betalen via Billink | WeChatPay | 
Giropay |American Express | FashionCheque | Cashly | AmazonPay |
 | Carte Bancaire | Podium Cadeaukaart | in3 keer betalen, 0% rente | Klarna | Pay Fixed Price (phone) |
SOFORT | PostePay | Gezondheidsbon | SprayPay | Instore Payments (POS) |
Maestro | Dankort | Fashion Giftcard | Biller | Przelewy24 |
Bank Transfer | Nexi | GivaCard |  | Creditclick | 
Trustly |  | De Cadeaukaart |  | Payconiq | 
| Multibanco |  | Paysafecard | | Google Pay |
Blik |  | Huis en Tuin Cadeau| | Apple Pay |
Online Bankbetaling| | Good4Fun | | |
| | | YourGift | | 
| | | Bataviastad Cadeaukaart | | 
| | | Shoes & Sneakers Cadeau | |
| | | Your Green Gift Cadeaukaart | |
| | | Bioscoopbon | |
| | | Bloemen Cadeaukaart | |
| | | Boekenbon | |
| | | Dinerbon | |
| | | Festival Cadeaukaart | |
| | | Parfum Cadeaukaart | |
| | | Winkelcheque | |


# Requirements

    PHP 7.2 or higher
    PHP tested up to 8.1
    Magento minimum version: 2.3
    Magento maximum version: Tested up to 2.4.7

For manual installation (non-composer), include:

    Pay. SDK: https://github.com/paynl/sdk
    Minimum version: 1.5.19
    Maximum version: Lower than 2.0.0 


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
composer update paynl/magento2-plugin paynl/sdk
php bin/magento setup:upgrade
php bin/magento cache:clean
```

The plugin has now been updated

# Usage

**More information on this plugin can be found on https://docs.pay.nl/plugins#magento-2**

# Support
https://www.pay.nl

Contact us: support@pay.nl
