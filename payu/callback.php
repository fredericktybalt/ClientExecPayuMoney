/**
 *
 *
 * ClientExec Plugin
 *
 * ClientExec payment gateway plugin PayuMoney
 *
 * @Author : Frederick T Tybalt
 * 
 * Version : 1.0
 *
 * Release Date : 7 Jun 2016 
 *
 * Contact : social@rick.co.in
 *
 *
 */
 
<?php
$_GET['fuse'] = 'billing';
$_GET['action'] = 'gatewaycallback';
$_GET['plugin'] = 'payu';           //replace 'skeletongateway' with the respective plugin folder name
chdir('../../..');
require_once dirname(__FILE__).'/../../../library/front.php';
?>