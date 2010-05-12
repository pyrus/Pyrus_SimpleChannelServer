<?php

$pyrus = new \PEAR2\Pyrus\Package(__DIR__ . '/../../Pyrus/package.xml');
$pyrus->setPackagingFilter('PEAR2\Pyrus\PackageFile\v2Iterator\MinimalPackageFilter');
$extrafiles = array(
    new \PEAR2\Pyrus\Package(__DIR__ . '/../../HTTP_Request/package.xml'),
    $pyrus,
    new \PEAR2\Pyrus\Package(__DIR__ . '/../../Pyrus_Developer/package.xml'),
    new \PEAR2\Pyrus\Package(__DIR__ . '/../../Exception/package.xml'),
    new \PEAR2\Pyrus\Package(__DIR__ . '/../../MultiErrors/package.xml'),
);
?>
