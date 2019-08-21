# Version 1.0.20
## 1.0.20 - Aug 21, 2019

### New Features
- Implemented new cron job for re-process product feed operation(s) which are in 'Error' state. Available to set the max number of attempts from backend.
- Added 'Repeat' action to Actions column on Indexing Queue listing page.
- Added 'Repeat' action to Actions column on Feed View listing page.
### Improvements
- Changed setup config section header block.
- Moved header block about product feed module from setup config section to catalog config section.
- Optimization of the process of forming the list of categories in the appropriate format.
- Removed 'System Information' column from Indexing Queue/Feed View details layout.
- Added number of attempts information on Indexing Queue/Feed View details layout.
- Improved Actions on Indexing Queue listing page. Now, only available action(s) for current record will be displayed.
- Improved Actions on Feed View listing page. Now, only available action(s) for current record will be displayed. 

### Fix Issues
- Don't logging information about empty operations related to product reindex into related log file.
This caused a problematic rendering Indexing Queue Grid on backend.

## 1.0.19 - Aug 08, 2019
### Fix Issues
- Uncaught Error: Call to a member function getBackend() on null in /app/vendor/unbxd/magento2-product-feed/Model/CacheManager.php:182
- Warning: date_format() expects parameter 1 to be DateTimeInterface, boolean given in /app/vendor/unbxd/magento2-product-feed/Helper/Data.php on line 487 
### Improvements
- Removed logic related to search module
- Added CHANGELOG.md
