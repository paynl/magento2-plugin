# Pay.nl Magento2 plugin

---
- [Summary](#summary)
- [Quickstart](#quickstart)
- [Setup](#setup)

---
### Summary

With this plugin by Pay.nl you can easily add all desired payment methods to your Magento 2 webshop. Please refer to https://www.pay.nl (Dutch) for an overview of all features and services. 

#####Available payment methods:

Bank Payments  | Creditcards | Gift cards & Vouchers | Others | 
:-----------: | :-----------: | :-----------: | :-----------: | 
iDEAL |Visa | YourGift | AfterPay | 
Bancontact Mister Cash  |  Mastercard | Webshop Giftcard | Billink | 
Giropay |American Express | FashionCheque |Focum AchterafBetalen | 
MyBank | Carte Bleue | Podium Cadeaukaart | PayPal |  
SOFORT Banking | PostePay | Gezondheidsbon |  Pay Fixed Price (phone) | 
Maestro | | Fashion Giftcard | Paysafecard  |
Bank Transfer | | Wijncadeau | |
 | | VVV Giftcard | | 



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
5. Enter the API token and serviceID (these can be found in the Pay.nl Admin Panel --> https://admin.pay.nl
6. Save the settings
7. Enable the desired payment methods and set allowed countries
8. Save the settings

Go to the *Manage* / *Services* tab in the Pay.nl Admin Panel to enable extra payment methods. 
