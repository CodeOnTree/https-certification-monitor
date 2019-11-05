# https-certification-monitor
```
$filename = __DIR__ . '/example/conf.json';
$scene = new \Tree\Util\Scene\DomainsMonitoring($filename);
$scene->doMonitorAndRemindByEMail();
// $scene->doMonitor();  
echo "done\n";
```