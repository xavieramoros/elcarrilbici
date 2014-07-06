<?php
//PATH SETTINGS        
define('CLASSES_PATH', $dirName . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR);
define('SITE_PATH', $dirName . DIRECTORY_SEPARATOR);
define('TEMPLATES_PATH', SITE_PATH . 'templates' . DIRECTORY_SEPARATOR);
define('MODEL_PATH', SITE_PATH . "model" . DIRECTORY_SEPARATOR);
define('DB_TABLE_PATH', MODEL_PATH . "db_table" . DIRECTORY_SEPARATOR);
define('JS_PATH', $dirName . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR);
define('URL_WP_NOTEDLINKS','http://notedlinks.com');
define('URL_FAQ', URL_WP_NOTEDLINKS . '/faqs');
define('URL_SUPPORT', URL_WP_NOTEDLINKS . '/contact-us');
//define('NL_DEBUG', true);

//NOTEDLINKS WP SETTING
define('URL_NOTEDLINKS', 'http://js.notedlinks.com/wordpress');
define('URL_WIDGET', 'http://js.notedlinks.com/widget');
define('WEB_NOTEDLINKS','app.notedlinks.com');
define('URL_REGISTER', 'http://' . WEB_NOTEDLINKS . '/check/checkPlugin');
define('URL_ACCOUNT_VAL', 'http://' . WEB_NOTEDLINKS . '/check/checkAccount');
define('URL_CHECK_ACTIVACION', 'http://' . WEB_NOTEDLINKS . '/plugin/checkActivatePlugin');
define('URL_STATUS', 'http://' . WEB_NOTEDLINKS . '/plugin/updateStatus');
define('URL_ACTIVATION', 'http://' . WEB_NOTEDLINKS . '/plugin/activeDate');
define('URL_LINK_STATS', 'http://' . WEB_NOTEDLINKS . '/plugin/links');
define('URL_WP_VERSION_UPDATE', 'http://' . WEB_NOTEDLINKS . '/plugin/updateWordpress');
