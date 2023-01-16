<p align="center">
    <img src="https://www.pay.nl/uploads/1/brands/main_logo.png" />
</p>
<h1 align="center">PAY. Magento2 plugin</h1>
  
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
iDEAL + QR |Visa | VVV Cadeaukaart | AfterPay (by Riverty) | PayPal |
Bancontact + QR |  Mastercard | Webshop Giftcard | Achteraf betalen via Billink | WeChatPay | 
Giropay |American Express | FashionCheque | Focum AchterafBetalen.nl | AmazonPay |
MyBank | Carte Bancaire | Podium Cadeaukaart | in3 keer betalen, 0% rente | Cashly | 
SOFORT | PostePay | Gezondheidsbon | Klarna | Pay Fixed Price (phone) |
Maestro | Dankort | Fashion Giftcard | SprayPay | Instore Payments (POS) |
Bank Transfer | Cartasi | GivaCard | Biller | Przelewy24 | 
Trustly | Tikkie | De Cadeaukaart |  | Creditclick | 
| Multibanco | Nexi | Paysafecard | | Payconiq
Blik |  | Huis en Tuin Cadeau| | Google Pay |
Online Bankbetaling| | Good4Fun | | Apple Pay
| | | YourGift | | 
| | | Bataviastad Cadeaukaart | | 
| | | Shoes & Sneakers Cadeau | |
| | | Your Green Gift Cadeaukaart| |


# Requirements

    PHP 7.2 or higher
    PHP tested up to 8.1
    Magento tested up to 2.4.5p1


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
