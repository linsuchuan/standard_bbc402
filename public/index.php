<?php
/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/
function dump2file($content,$filename = 'debuglog') {
    define('DEBUG_PATH', realpath(__DIR__.'/../../..'));
    $m_time = microtime();
    list($t1, $t2) = explode(' ', $m_time);
    $t1 = $t1*1000000;
    $t2 = date('Y-m-d H:i:s',$t2);
    $flag = $t2.':'.$t1;
    $filename = $filename?$filename:'debuglog';
    $import_data = print_r($content,1);
    $import_data = "================".$flag."================\r\n".$import_data."\r\n";
    file_put_contents(DEBUG_PATH.'/debuglog/'.$filename.'.txt', $import_data,FILE_APPEND);
}
require __DIR__.'/../bootstrap/autoload.php';

require __DIR__.'/../bootstrap/start.php';

kernel::boot();
