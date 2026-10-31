<?php
declare(strict_types=1);
if (($argv[1] ?? '') === '--probe') {
    putenv('AWC_APP_ROOT=' . $argv[2]); putenv('AWC_DATA_ROOT=' . $argv[2] . '/runtime');
    require __DIR__ . '/../app/bootstrap.php';
    echo json_encode(['count'=>count(cameras()),'settings'=>public_settings()]); exit;
}
$root=sys_get_temp_dir().'/awc-settings-'.bin2hex(random_bytes(6));mkdir($root.'/config',0700,true);
$config=json_decode(file_get_contents(__DIR__.'/../config/site.json'),true,512,JSON_THROW_ON_ERROR);
function probe(array $config,string $root): array {
    file_put_contents($root.'/config/site.json',json_encode($config));
    $process=proc_open([PHP_BINARY,__FILE__,'--probe',$root],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
    $output=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return[proc_close($process),$output];
}
function expect(bool $value,string $label):void{if(!$value)throw new RuntimeException($label);echo "PASS $label\n";}
try {
    [$code,$output]=probe($config,$root);$result=json_decode($output,true);expect($code===0&&$result['count']===18,'JSON configuration loads 18 unique cameras');expect(!isset($result['settings']['cameras'])&&!isset($result['settings']['retrieval']['maxDownloadBytes']),'Browser settings omit source camera URLs and private collector configuration');
    $changed=$config;$changed['retrieval']['intervalMinutes']=10;$changed['media']['quality']=80;[$code,$output]=probe($changed,$root);$result=json_decode($output,true);expect($code===0&&$result['settings']['retrieval']['intervalMinutes']===10&&$result['settings']['media']['quality']===80,'Configuration values reach browser settings without hardcoded defaults');
    foreach(['duplicate','unsafe-url','coordinates','interval','format'] as $case){$bad=$config;if($case==='duplicate')$bad['cameras'][]=$bad['cameras'][0];if($case==='unsafe-url')$bad['cameras'][0]['url']='https://example.org/unapproved';if($case==='coordinates')$bad['cameras'][0]['lat']=91;if($case==='interval')$bad['retrieval']['intervalMinutes']=0;if($case==='format')$bad['media']['format']='jpeg';expect(probe($bad,$root)[0]!==0,'Invalid '.$case.' configuration rejected');}
} finally {foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST) as $file){$file->isDir()?rmdir($file->getPathname()):unlink($file->getPathname());}rmdir($root);}
