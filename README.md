# Magento

Тут  все изменение, которые были произведены при переносе:

1)в таблице core_config_data изменены пути для:
    1)web/secure/base_link_url(old - https://vplaboratory.ru/)
    1)web/secure/base_url(old - https://vplaboratory.ru/)
    1)web/unsecure/base_link_url(old - https://vplaboratory.ru/)
    1)web/unsecure/base_url(old - https://vplaboratory.ru/)


2)файл app>etc>env.php(поменять данные бд и mage mode на production)

        'host' => 'localhost',
        'dbname' => 'magento',
        'username' => 'root',
        'password' => '',


  'MAGE_MODE' => 'production',


3)в базе данных изменить value на 1
UPDATE core_config_data SET value = 0 WHERE path = 'dev/static/sign'


