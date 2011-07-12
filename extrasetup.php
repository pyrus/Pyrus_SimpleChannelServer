<?php

$pyrus = new \Pyrus\Package(__DIR__ . '/../Pyrus/package.xml');
$pyrus->setPackagingFilter('Pyrus\PackageFile\v2Iterator\MinimalPackageFilter');
$extrafiles = array(
    new \Pyrus\Package(__DIR__ . '/../PEAR2_HTTP_Request/package.xml'),
    $pyrus,
    new \Pyrus\Package(__DIR__ . '/../Pyrus_Developer/package.xml'),
    new \Pyrus\Package(__DIR__ . '/../PEAR2_Exception/package.xml'),
    new \Pyrus\Package(__DIR__ . '/../PEAR2_MultiErrors/package.xml'),
);
?>
