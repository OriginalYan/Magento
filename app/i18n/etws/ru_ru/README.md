# Russian Language Pack for Magento 2

Перевод интерфейса Magento 2 на русский язык.


# Installation

## Установка через composer.json:

Запустите следующую команду в корневой папке Magento 2

    composer require etws/magento-language-ru_ru:*
    
Не забудьте очистить кэш

    bin/magento cache:clean
    
Для некоторых фраз (в основном JS) помогает стирание статичных файлов
    
    rm -rf var/view_preprocessed/ pub/static/

## Другие способы установки
 
Инструкция по установке доступна на нашем сайте документации
[doc.etwebsolutions.com/ru/instruction/m2_russian_language_pack/install](http://doc.etwebsolutions.com/ru/instruction/m2_russian_language_pack/install)


# Помощь с переводом

Мы не профессиональные переводчики и поддерживаем перевод в силу своих возможностей и требований.
Если вы хотите помочь улучшении этого перевода, то перейдите по ссылке, чтобы узнать, как именно можете помочь.
[doc.etwebsolutions.com/ru/instruction/m2_russian_language_pack/i-want-to-help-you-with-the-translation](http://doc.etwebsolutions.com/ru/instruction/m2_russian_language_pack/i-want-to-help-you-with-the-translation)


# Готовность перевода

| | | |
|-|-|-|
|**Общий прогресс**|7443/9045|![Progress](http://progressed.io/bar/82)|

## Темы

| | | |
|-|-|-|
|frontend/Magento/luma|50/58|![Progress](http://progressed.io/bar/86)|
|frontend/Magento/blank|7/7|![Progress](http://progressed.io/bar/100)|

## Модули

| | | |
|-|-|-|
|Magento_AdminNotification|51/51|![Progress](http://progressed.io/bar/100)|
|Magento_AdvancedPricingImportExport|5/5|![Progress](http://progressed.io/bar/100)|
|Magento_Authorization|3/3|![Progress](http://progressed.io/bar/100)|
|Magento_Authorizenet|53/70|![Progress](http://progressed.io/bar/76)|
|Magento_Backend|441/494|![Progress](http://progressed.io/bar/89)|
|Magento_Backup|83/87|![Progress](http://progressed.io/bar/95)|
|Magento_Braintree|130/160|![Progress](http://progressed.io/bar/81)|
|Magento_Bundle|104/110|![Progress](http://progressed.io/bar/95)|
|Magento_Captcha|26/27|![Progress](http://progressed.io/bar/96)|
|Magento_CatalogImportExport|14/23|![Progress](http://progressed.io/bar/61)|
|Magento_CatalogInventory|66/78|![Progress](http://progressed.io/bar/85)|
|Magento_CatalogRule|88/90|![Progress](http://progressed.io/bar/98)|
|Magento_CatalogSearch|37/39|![Progress](http://progressed.io/bar/95)|
|Magento_CatalogUrlRewrite|6/6|![Progress](http://progressed.io/bar/100)|
|Magento_CatalogWidget|20/20|![Progress](http://progressed.io/bar/100)|
|Magento_Catalog|696/810|![Progress](http://progressed.io/bar/86)|
|Magento_CheckoutAgreements|40/41|![Progress](http://progressed.io/bar/98)|
|Magento_Checkout|181/201|![Progress](http://progressed.io/bar/90)|
|Magento_Cms|166/176|![Progress](http://progressed.io/bar/94)|
|Magento_Config|96/112|![Progress](http://progressed.io/bar/86)|
|Magento_ConfigurableProduct|113/162|![Progress](http://progressed.io/bar/70)|
|Magento_Contact|27/27|![Progress](http://progressed.io/bar/100)|
|Magento_Cookie|13/15|![Progress](http://progressed.io/bar/87)|
|Magento_Cron|19/22|![Progress](http://progressed.io/bar/86)|
|Magento_CurrencySymbol|20/22|![Progress](http://progressed.io/bar/91)|
|Magento_CustomerImportExport|18/19|![Progress](http://progressed.io/bar/95)|
|Magento_Customer|479/560|![Progress](http://progressed.io/bar/86)|
|Magento_Deploy|1/3|![Progress](http://progressed.io/bar/33)|
|Magento_Developer|9/10|![Progress](http://progressed.io/bar/90)|
|Magento_Dhl|42/83|![Progress](http://progressed.io/bar/51)|
|Magento_Directory|46/51|![Progress](http://progressed.io/bar/90)|
|Magento_DownloadableImportExport|5/5|![Progress](http://progressed.io/bar/100)|
|Magento_Downloadable|104/121|![Progress](http://progressed.io/bar/86)|
|Magento_Eav|107/145|![Progress](http://progressed.io/bar/74)|
|Magento_Email|92/110|![Progress](http://progressed.io/bar/84)|
|Magento_EncryptionKey|14/15|![Progress](http://progressed.io/bar/93)|
|Magento_Fedex|34/79|![Progress](http://progressed.io/bar/43)|
|Magento_GiftMessage|38/50|![Progress](http://progressed.io/bar/76)|
|Magento_GoogleAdwords|13/13|![Progress](http://progressed.io/bar/100)|
|Magento_GoogleOptimizer|7/8|![Progress](http://progressed.io/bar/88)|
|Magento_GroupedProduct|31/35|![Progress](http://progressed.io/bar/89)|
|Magento_ImportExport|92/123|![Progress](http://progressed.io/bar/75)|
|Magento_Indexer|20/25|![Progress](http://progressed.io/bar/80)|
|Magento_Integration|52/119|![Progress](http://progressed.io/bar/44)|
|Magento_LayeredNavigation|28/30|![Progress](http://progressed.io/bar/93)|
|Magento_Marketplace|15/15|![Progress](http://progressed.io/bar/100)|
|Magento_MediaStorage|21/26|![Progress](http://progressed.io/bar/81)|
|Magento_Msrp|16/18|![Progress](http://progressed.io/bar/89)|
|Magento_Multishipping|84/91|![Progress](http://progressed.io/bar/92)|
|Magento_Newsletter|149/152|![Progress](http://progressed.io/bar/98)|
|Magento_OfflinePayments|24/24|![Progress](http://progressed.io/bar/100)|
|Magento_OfflineShipping|54/57|![Progress](http://progressed.io/bar/95)|
|Magento_PageCache|13/21|![Progress](http://progressed.io/bar/62)|
|Magento_Payment|49/67|![Progress](http://progressed.io/bar/73)|
|Magento_Paypal|300/598|![Progress](http://progressed.io/bar/50)|
|Magento_Persistent|16/19|![Progress](http://progressed.io/bar/84)|
|Magento_ProductAlert|37/41|![Progress](http://progressed.io/bar/90)|
|Magento_ProductVideo|41/47|![Progress](http://progressed.io/bar/87)|
|Magento_Quote|50/64|![Progress](http://progressed.io/bar/78)|
|Magento_Reports|210/232|![Progress](http://progressed.io/bar/91)|
|Magento_Review|131/138|![Progress](http://progressed.io/bar/95)|
|Magento_Rss|8/8|![Progress](http://progressed.io/bar/100)|
|Magento_Rule|35/35|![Progress](http://progressed.io/bar/100)|
|Magento_SalesInventory|2/2|![Progress](http://progressed.io/bar/100)|
|Magento_SalesRule|159/167|![Progress](http://progressed.io/bar/95)|
|Magento_SalesSequence|2/2|![Progress](http://progressed.io/bar/100)|
|Magento_Sales|696/832|![Progress](http://progressed.io/bar/84)|
|Magento_Search|81/92|![Progress](http://progressed.io/bar/88)|
|Magento_Security|20/28|![Progress](http://progressed.io/bar/71)|
|Magento_SendFriend|49/49|![Progress](http://progressed.io/bar/100)|
|Magento_Shipping|161/185|![Progress](http://progressed.io/bar/87)|
|Magento_Sitemap|68/69|![Progress](http://progressed.io/bar/99)|
|Magento_Store|28/38|![Progress](http://progressed.io/bar/74)|
|Magento_Swatches|21/39|![Progress](http://progressed.io/bar/54)|
|Magento_TaxImportExport|19/19|![Progress](http://progressed.io/bar/100)|
|Magento_Tax|175/183|![Progress](http://progressed.io/bar/96)|
|Magento_Theme|175/201|![Progress](http://progressed.io/bar/87)|
|Magento_Translation|6/6|![Progress](http://progressed.io/bar/100)|
|Magento_Ui|110/126|![Progress](http://progressed.io/bar/87)|
|Magento_Ups|44/115|![Progress](http://progressed.io/bar/38)|
|Magento_UrlRewrite|61/63|![Progress](http://progressed.io/bar/97)|
|Magento_User|133/147|![Progress](http://progressed.io/bar/90)|
|Magento_Usps|44/133|![Progress](http://progressed.io/bar/33)|
|Magento_Variable|21/21|![Progress](http://progressed.io/bar/100)|
|Magento_Vault|10/18|![Progress](http://progressed.io/bar/56)|
|Magento_Webapi|25/28|![Progress](http://progressed.io/bar/89)|
|Magento_Weee|26/29|![Progress](http://progressed.io/bar/90)|
|Magento_Widget|70/72|![Progress](http://progressed.io/bar/97)|
|Magento_Wishlist|141/143|![Progress](http://progressed.io/bar/99)|
|Magento_GoogleAnalytics|4/4|![Progress](http://progressed.io/bar/100)|
|Magento_NewRelicReporting|12/21|![Progress](http://progressed.io/bar/57)|
|Magento_WebapiSecurity|2/2|![Progress](http://progressed.io/bar/100)|
|Magento_DesignEditor|2/2|![Progress](http://progressed.io/bar/100)|
|Magento_Log|2/2|![Progress](http://progressed.io/bar/100)|

## Библиотеки

| | | |
|-|-|-|
|lib/web/mage/adminhtml/backup.js|4/4|![Progress](http://progressed.io/bar/100)|
|lib/web/mage/adminhtml/wysiwyg/widget.js|3/3|![Progress](http://progressed.io/bar/100)|
|lib/web/mage/backend/suggest.js|3/3|![Progress](http://progressed.io/bar/100)|
|lib/web/mage/decorate.js|1/1|![Progress](http://progressed.io/bar/100)|
|lib/web/mage/dropdown.js|1/1|![Progress](http://progressed.io/bar/100)|
|lib/web/mage/gallery/gallery.js|2/2|![Progress](http://progressed.io/bar/100)|
|lib/web/mage/loader.js|2/2|![Progress](http://progressed.io/bar/100)|
|lib/web/mage/loader_old.js|2/2|![Progress](http://progressed.io/bar/100)|
|lib/web/mage/menu.js|2/2|![Progress](http://progressed.io/bar/100)|
|lib/web/mage/translate-inline-vde.js|2/2|![Progress](http://progressed.io/bar/100)|
|lib/web/mage/translate-inline.js|3/3|![Progress](http://progressed.io/bar/100)|
|lib/web/mage/validation.js|4/4|![Progress](http://progressed.io/bar/100)|
|lib/web/mage/validation/validation.js|4/4|![Progress](http://progressed.io/bar/100)|