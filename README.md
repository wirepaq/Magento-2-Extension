# Unbxd Product Feed Module For Magento 2

This module provide possibility for synchronization product catalog with Unbxd service.

Support Magento 2.2.\* || 2.3.\*

# Installation Guide

### Install by composer

```
composer require unbxd/magento2-product-feed
php bin/magento module:enable Unbxd_ProductFeed
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### Manual installation

1. Download this module [Link](https://github.com/unbxd/Magento-2-Extension/archive/1.0.19.zip)
3. Unzip module in the folder:

    app\code\Unbxd\ProductFeed  
    
4. Access the root of you Magento 2 instance from command line and run the following commands:

```
php bin/magento module:enable Unbxd_ProductFeed
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

5. Configure module in backend
 

