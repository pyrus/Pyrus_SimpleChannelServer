<?php
/**
 * This example shows how to upgrade a pear channel running Chiara_PEAR_Server
 * to Pyrus_SimpleChannelServer.
 * 
 * All release dates are taken from the package.xml, so we can easily re-create
 * the channel.
 * 
 * This could also be accomplished with:
 * for i in *.tgz; do php pearscs.phar release $i saltybeagle; done
 * 
 * This example shows you how to use the API to release the packages.
 */

require_once '/Users/bbieber/pyrus/src/PEAR2/Autoload.php';

// Here we re-create the channel.xml
$channel = new Pyrus_SimpleChannelServer_Channel('pear.saltybeagle.com','Brett Bieber\'s PEAR Channel','salty','Chiara_PEAR_Server_REST/');
$scs = new Pyrus_SimpleChannelServer($channel, dirname(__FILE__).'/pearchannel');
$scs->saveChannel();

// Path to the get directory.
$dirname = dirname(__FILE__).'/pearchannel/get/';

$dir = new DirectoryIterator($dirname);
foreach ($dir as $file) {
    if (!$file->isDot() && substr($file->getFilename(), -3) != 'tar') {
        $scs->saveRelease(new \PEAR2\Pyrus\Package($dirname.$file->getFilename()), 'saltybeagle');
    }
}

?>
