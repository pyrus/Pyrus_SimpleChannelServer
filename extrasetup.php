<?php

$pyrus = new \pear2\Pyrus\Package(__DIR__ . '/../../Pyrus/package.xml');
$pyrus->setPackagingFilter('pear2\Pyrus\PackageFile\v2Iterator\MinimalPackageFilter');
$extrafiles = array(
    new \pear2\Pyrus\Package(__DIR__ . '/../../HTTP_Request/package.xml'),
    $pyrus,
    new \pear2\Pyrus\Package(__DIR__ . '/../../Pyrus_Developer/package.xml'),
    new \pear2\Pyrus\Package(__DIR__ . '/../../Exception/package.xml'),
    new \pear2\Pyrus\Package(__DIR__ . '/../../MultiErrors/package.xml'),
);
?>
