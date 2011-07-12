<?php
$package->dependencies['required']->php = '5.3.0';
$package->dependencies['required']->package['pear2.php.net/PEAR2_Pyrus']->min('0.5.0');
$package->dependencies['required']->package['pear2.php.net/PEAR2_MultiErrors']->save();

$compatible->dependencies['required']->php = '5.3.0';
$compatible->dependencies['required']->package['pear2.php.net/PEAR2_Pyrus']->min('0.5.0');
$compatible->dependencies['required']->package['pear2.php.net/PEAR2_MultiErrors']->save();
?>
