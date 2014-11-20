Telnet-Commander
=========

Library to easy send packets of commands to your telnet based devices and recieve answers for processing

  - Send commands to device
  - Setup needful promt regexp
  - Read answers


Example
--------------

In this example we login to DLink DES-3200, change time-zone and check results

```php

$host = "192.168.1.10";
$host = 23;

$username = 'john';
$password = 'secretpass'

$commands = [
    ['command' => $username, 'promt' => '/username:$/i'],
    ['command' => $password, 'promt' => '/password:$/i'],
    ['command' => "enable admin", 'promt' => '/#$/i'],
    ['command' => "mysecretpass", 'promt' => '/PassWord:$|#$/i'],
    ['command' => "config time_zone operator + hour 3 min 0", 'promt' => '/#$/i'],
    ['command' => "show time", 'promt' => '/#$/i'],
    ['command' => "", 'promt' => '/#$/i'],
];

try {
        //Setup connection
        $commander = new TelnetCommander($host, $port);
        $commander->setHasAuth(false);
        $commander->connect();

        //Set commands
        $commander->setCommands($commands);

        //Execute commands
        $data = $commander->processCommands();

        /*
        Process $data if you need...
        */

    } catch (Exception $e) {
        echo $e->getMessage();
    }


```
