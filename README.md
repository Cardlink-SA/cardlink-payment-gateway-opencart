# Cardlink Checkout Payment Gateway

- Contributors: cardlink
- Tags: payments, payment-gateway
- Requires at least: 3.0.x.x
- Tested up to: 3.0.3.8
- Requires PHP: 7.x - 8.x
- License: GPLv2 or later
- License URI: http://www.gnu.org/licenses/gpl-2.0.html

## Changelog

- **1.1.0**
  - IRIS Support
- **1.0.5**
  - Fix Safari target
- **1.0.4**
  - Fix Shipping details
- **1.0.3**
  - Fix iframe style
- **1.0.2**
  - Add order status field
- **1.0.1**
  - Total installments number
- **1.0.0**
  - Initial release

## Description

Cardlink Payment Gateway allows you to accept payment through various schemes such as Visa, Mastercard, Maestro, American Express, Diners, Discover cards on your website, with or without variable installments.
This module aims to offer new payment solutions to Cardlink merchants for their Opencart 3.0.x online store without having web development knowledge. However, for the initial module installation some technical knowledge will be required.

Merchants with e-shops will be able to integrate the Cardlink Payment Gateway to their checkout page using the CSS layout that they want. Also, they can choose between redirect or IFRAME option for the payment environment. Once the payment is made, the customer is returned to the online store and the order is updated.
Once you have completed the requested tests and any changes to your website, you can activate your account and start accepting payments. 

## Features

1. A dropdown option for instance between Worldline, Nexi και Cardlink.
2. Option to enable test environment (sandbox). All transactions will be re-directed to the endpoint that represents the production environment by default. The endpoint will be different depending on which acquirer has been chosen from instance dropdown option.
3. Choose a custom title for payment method.
4. Ability to define the ranges of the total order amounts and the maximum number of installments for each range.
5. Option for pre-authorization or sale transactions.
6. Option to set different order statuses for transactions with a captured or authorized payment.
7. Option for a user tokenization service. The card token will be stored at the merchant’s e-shop database and will be used by customers to auto-complete future payments. 
8. In-store checkout option: the merchant can configure the payment process to take place inside a pop up with IFRAME to mask the redirection process from the customers.
9. A text field for providing the absolute or relative (to Cardlink Payment Gateway location on server) URL of custom CSS stylesheet, to apply custom CSS styles in the payment page.
10. Translation ready for Greek & English languages.

## Features (IRIS)

1. A dropdown option for instance Nexi (only for now).
2. Option to enable test environment (sandbox). All transactions will be re-directed to the endpoint that represents the production environment by default.
3. Field for Merchand ID.
4. Field for Shared Secret Key.
5. Field for IRIS customer code.
6. Choose a custom title for payment method.
7. Option to set different order statuses for transactions with a captured payment.
8. Translation ready for Greek & English languages.
9. Not supporting iframe. Supporting only redirect method.

## Installation

Firstly, find the plugins in the "INSTALL" folder. 
cardlink-payment-opencart.ocmod.zip
cardlink-payment-opencart-iris.ocmod.zip

From your administration, go to ``Extensions > Installer`` and click on the ``Upload`` button.

![image001](https://developer.cardlink.gr/downloads/cardlink-payment-gateway-opencart-assets/image001.png)

Find it and select it through the popup file browser. Once the module’s ``ocmod.zip`` file has been uploaded and the module installed, you will see the below sucess message.
![image002](https://developer.cardlink.gr/downloads/cardlink-payment-gateway-opencart-assets/image002.png)

After the success uploading go to ``Extensions > Extensions`` and filter the extensions by ``Payment``.
Find the ``Cardlink`` extension and click on the green icon on the right with the plus icon. 


## Screenshots

1. The Cardlink Payment Gateway admin page used to configure the settings (``Extensions > Extensions > Payments > Cardlink > Edit``).
![image003](https://developer.cardlink.gr/downloads/cardlink-payment-gateway-opencart-assets/image003.png) 
1. The Cardlink Payment Gateway IRIS admin page used to configure the settings (``Extensions > Extensions > Payments > Cardlink Iris > Edit``).
![image005](https://developer.cardlink.gr/downloads/cardlink-payment-gateway-opencart-assets/image005.png) 
3. This is the front-end of Cardlink Payment Gateway plugin located in checkout page.
![image005](https://developer.cardlink.gr/downloads/cardlink-payment-gateway-opencart-assets/image004.png)

##  Support tickets

In case that you face any technical issue during the installation process, you can contact the Cardlink e-commerce team at ecommerce_support@cardlink.gr .