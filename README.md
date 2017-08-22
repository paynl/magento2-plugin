# Pay.nl Magento2 plugin

---
- [Summary](#summary)
- [Quickstart](#quickstart)
- [Setup](#setup)

---
### Summary

With this plugin by Pay.nl you can easily add all desired payment methods to your Magento 2 webshop. Please refer to https://www.pay.nl (Dutch) for an overview of all features and services. 

#####Available payment methods:

Bank Payments  | Creditcards | Gift cards & Vouchers | Pay by invoice | Others | 
:-----------: | :-----------: | :-----------: | :-----------: | :-----------: |
iDEAL |Visa | YourGift | AfterPay | PayPal |
Bancontact |  Mastercard | Webshop Giftcard | Billink | Pay Fixed Price (phone) | 
Giropay |American Express | FashionCheque |Focum AchterafBetalen.nl | Instore Payments (POS)|
MyBank | Carte Bleue | Podium Cadeaukaart | Capayable achteraf betalen |  | 
SOFORT Banking | PostePay | Gezondheidsbon | Capayable Gespreid betalen | |
Maestro | | Fashion Giftcard |  | | | 
Bank Transfer | | Wijncadeau | | | 
|  | | VVV Giftcard | | | 
| | | Paysafecard |
| | | Gift in a Box |

### Quickstart

#####Installing

In command line, navigate to the installation directory of magento2

Enter the following commands:

```
composer require paynl/magento2-plugin
php bin/magento setup:upgrade
php bin/magento cache:clean
```

The plugin is now installed

#####Setup

1. Log into the Magento Admin
2. Go to *Stores* / *Configuration*
3. Go to *Sales* / *Payment Methods*
4. Scroll down to find the Pay.nl Settings
5. Enter the API token and serviceID (these can be found in the Pay.nl Admin Panel --> https://admin.pay.nl/programs/programs
6. Save the settings
7. Enable the desired payment methods and set allowed countries
8. Save the settings

Go to the *Manage* / *Services* tab in the Pay.nl Admin Panel to enable extra payment methods. 
