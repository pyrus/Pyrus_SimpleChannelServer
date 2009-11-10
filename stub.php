#!/usr/bin/env php
<?php
if (version_compare(phpversion(), '5.3.0', '<')
	&& substr(phpversion(), 0, 5) != '5.3.0') {
    // this small hack is because of running RCs of 5.3.0
    echo "SimpleChannelServer requires PHP 5.3.0 or newer.\n";
    exit -1;
}
foreach (array('sqlite3', 'phar', 'spl', 'pcre', 'simplexml') as $ext) {
    if (!extension_loaded($ext)) {
        echo 'Extension ', $ext, " is required\n";
        exit -1;
    }
}
function pyrus_autoload($class)
{
    $class = str_replace(array('_','\\'), '/', $class);
    if (file_exists('phar://' . __FILE__ . '/PEAR2_SimpleChannelServer-@PACKAGE_VERSION@/php/' .
                    $class . '.php')) {
        include 'phar://' . __FILE__ . '/PEAR2_SimpleChannelServer-@PACKAGE_VERSION@/php/' .
            $class . '.php';
    }
}
spl_autoload_register("pyrus_autoload");
$cli = new \pear2\SimpleChannelServer\CLI();
$cli->process();
__HALT_COMPILER();
