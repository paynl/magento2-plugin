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
iDEAL |Visa | PayPal | Paysafecard | AfterPay | 
Bancontact Mister Cash  |  Mastercard | Webshop Giftcard | Billink | 
Giropay |American Express | FashionCheque | Pay Fixed Price (phone) | 
MyBank | Carte Bleue | Podium Cadeaukaart | |  
SOFORT Banking | PostePay | Gezondheidsbon | | 
Maestro | | Fashion Giftcard | |
Bank Transfer | | Wijncadeau | |
| | | YourGift | | 



### Quickstart

#####Installing

1. In command line, navigate to the installation directory of magento2
2. Enter the following commands:

	$ composer require paynl/magento2-plugin
	
	$ php bin/magento setup:upgrade
	
	$ php bin/magento cache:clean
  
3. The plugin is now installed

#####Setup

1. Log into the Magento Admin
2. Go to *Stores* / *Configuration*
3. Go to *Sales* / *Payment Methods*
4. Scroll down to find the Pay.nl Settings
5. Enter the API token and serviceID (these can be found in the Pay.nl Admin Panel --> https://admin.pay.nl
6. Enable the desired payment methods and set allowed countries

Go to the *Manage* / *Services* tab in the Pay.nl Admin Panel to enable extra payment methods. 
