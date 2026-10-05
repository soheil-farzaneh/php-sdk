<?php
require dirname(__DIR__,2).'/vendor/autoload.php';

use Aqayepardakht\PhpSdk\PaymentManager;
use Aqayepardakht\PhpSdk\Tests\Support\FakeHttpClient;

$_SERVER['HTTP_ACCEPT']=$argv[1] ?? '';
PaymentManager::make('aqp','test-pin',new FakeHttpClient())->start($argv[2] ?? 'T-1');
echo 'AFTER_START';
