<?php
error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors',true);
// Get the autoloader
require __DIR__ . '/../../../autoload.php';

/*
$channel = new Pyrus_SimpleChannelServer('pear2.php.net','/Library/WebServer/Documents/pearserver', null, '/Users/bbieber/pyrus', array('saltybeagle','cellog'));
if (!@unserialize(file_get_contents('/tmp/categories.inf'))) {
      $cat = Pyrus_SimpleChannelServer_Categories::create('Name1',
          'Description 1', 'Alias1')->
          create('Name2', 'Description 2')->
          create('Name3', 'Description 3', 'Alias3')->
          create('Name4', 'Description 4');
      file_put_contents('/tmp/categories.inf', serialize($cat));
}

$categories = Pyrus_SimpleChannelServer_Categories::getCategories();
$categories = $channel->listCategories();
foreach($categories as $category) {
    var_dump($category);
}
*/
$channel = new Pyrus\SimpleChannelServer\Channel('pear2.php.net','Brett Bieber\'s PEAR Channel','salty');

//$scs = new Pyrus\SimpleChannelServer\Main($channel,'/Library/WebServer/Documents/pearserver','/home/bbieber/pyrus/php');
$scs = new Pyrus\SimpleChannelServer\Main($channel,'/home/cellog/testapache/htdocs',\Pyrus\Config::current()->location);
$categories = Pyrus\SimpleChannelServer\Categories::create('Default', 'This is the default category');
$scs->saveChannel();
$scs->saveRelease(new \Pyrus\Package(dirname(__FILE__) . '/../package.xml'), 'cellog');
echo 'did it'.PHP_EOL;
/*
$manager = new Pyrus\SimpleChannelServer\REST_Manager('/Library/WebServer/Documents/pearserver','pear2.php.net','rest/',array('cellog'));
var_dump($manager->saveRelease(new \Pyrus\Package(dirname(__FILE__) . '/../package.xml'),'cellog'));
*/
?>
