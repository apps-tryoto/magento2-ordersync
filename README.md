<a href="https://www.tryoto.com" title="Oto Logo" ><img src="https://i.postimg.cc/Nfyb8YwL/OTO-logo.jpg" width="100" align="right" title="tryoto.com" /></a>

# OTO Order Integration for Magento2
#### For Magento / Adobe Commerce 2.3.x and 2.4.x

### OTO OrderSync Extension
This extension is connects your Magento2 web store and sends new orders to OTO api system.
You can trigger order synchronization with command line manually or with cron.
It also cancels canceled orders on OTO side.

#### What is OTO
MENA's #1 shipping gateway for e-commerce stores and retailers to ship, manage, track, analyze and return orders with 200+ carriers from a single dashboard.
<br />
<img src="https://i.postimg.cc/C1QkdB57/Component-79-1-1.png"              align="left" title="tryoto.com" style="max-width:100%;"/><br />
<img src="https://i.postimg.cc/0N9p41hY/Component-80-1-1-2048x1420.png"    align="left" title="tryoto.com" style="max-width:100%;"/><br />
<img src="https://i.postimg.cc/bNdV6k1y/Component-81-1-1-1-2048x1281.webp" align="left" title="tryoto.com" style="max-width:100%;"/><br />
<img src="https://i.postimg.cc/PJvZDX8j/Component-89-1.png"                align="left" title="tryoto.com" style="max-width:100%;"/><br />
<img src="https://i.postimg.cc/GhpVZZn1/360-degree.png"                    align="left" title="tryoto.com" style="max-width:100%;"/><br />

### Features
* Connects and authorizes your Magento2 store to OTO System.
* Synchronizes your new orders to OTO.
* Cancels orders in OTO if canceled in Magento.
* You can send individual order data to OTO with command line

### How to Install OTO OrderSync Extension

##### Using Composer (recommended)

```sh
composer require tryoto/mage2-ordersync;
php bin/magento maintenance:enable;
php bin/magento module:enable Oto_OrderSync;
php bin/magento setup:upgrade;
php bin/magento setup:di:compile;
php bin/magento setup:static:deploy -f;
php bin/magento maintenance:disable;
php bin/magento cache:flush;
```

##### Using git clone

```sh
cd your_website_path;
git clone https://github.com/apps-tryoto/magento2-ordersync app/code/Oto/OrderSync;
php bin/magento maintenance:enable;
php bin/magento module:enable Oto_OrderSync;
php bin/magento setup:upgrade;
php bin/magento setup:di:compile;
php bin/magento setup:static:deploy -f;
php bin/magento maintenance:disable;
php bin/magento cache:flush;
```

##### Using manual download and FTP Upload

Go to [https://github.com/oto/mage2-ordersync/releases](https://github.com/oto/mage2-ordersync/releases) and download latest version.
Open zipfile and upload OrderSync directory as app/code/Oto/OrderSync

```sh
cd your_website_path;
php bin/magento maintenance:enable;
php bin/magento module:enable Oto_OrderSync;
php bin/magento setup:upgrade;
php bin/magento setup:di:compile;
php bin/magento setup:static:deploy -f;
php bin/magento maintenance:disable;
php bin/magento cache:flush;
```

##### After module setup

Add this cron command to cron to sync your orders with oto in period which you want;

```sh
*/2 * * * * cd your_website_path; php bin/magento oto:new_order_sync >> var/log/oto_ordersync_new_orders.log
```

##### Uninstalling

```sh
composer remove tryoto/mage2-ordersync;
php bin/magento maintenance:enable;
php bin/magento module:disable Oto_OrderSync;
php bin/magento setup:upgrade;
php bin/magento setup:di:compile;
php bin/magento setup:static:deploy -f;
php bin/magento maintenance:disable;
php bin/magento cache:flush;
```

Do not forget to remove cron entry from crontab.


© All rights reserved OTO Global Inc. © 2024 | [www.tryoto.com](https://www.tryoto.com)