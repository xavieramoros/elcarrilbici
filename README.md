El carril bici
----------------------
Elcarrilbici is a blog about bike lanes and urban cycling.
Wordpress blog hosted on pagodabox (v. 1.0). 

Pagoda configuration is added to the Boxfile:
[Boxfile!](Boxfile)

To use different settings for Pagoda or Locahost, we add to the wp-config.php the following:

```php
if (isset($_SERVER['PLATFORM']) && $_SERVER['PLATFORM'] == 'PAGODABOX') {
define('DB_NAME', $_SERVER['DB1_NAME']);
define('DB_USER', $_SERVER['DB1_USER']);
define('DB_PASSWORD', $_SERVER['DB1_PASS']);
define ('DB_HOST', $_SERVER['DB1_HOST'] . ':' . $_SERVER['DB1_PORT']);
}
else {
define('DB_NAME', 'localhostDB');
define('DB_USER', 'localhostUSER');
define('DB_PASSWORD', 'localhostDB_PASSWORD');
define('DB_HOST', 'localhost');
}
```


* Config File: Because Pagoda Box needs a different config file than a local version of the site, we created a new directory in the root of the project called "pagoda" and created a pagoda version of the config file there. Then we created an After Build deploy hook in the Boxfile that moved that file from pagoda/wp-config.php to wp-config.php. Also, in place of the static database credentials, we used the auto-created environment variables.

<pre>
    <code>
        after_build:
        - "mv pagoda/wp-config.php wp-config.php"
    </code>
</pre>  

* Database Component: An empty database was created by adding a db component to the Boxfile.

<pre>
    <code>
        db1:
            name: wp-db
    </code>
</pre>