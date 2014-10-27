<?php

use Avin\TelnetCommander\TelnetCommander;

class TelnetCommanderTest extends PHPUnit_Framework_TestCase {

    protected $tesHost = '95.66.188.1';

    public function testTelnetCommanderNoAuth()
    {
        $commander = new TelnetCommander($this->tesHost, 23);
        $commander->setHasAuth(false);
        $commander->connect();

        $commands = [
            ['command' => "guest", 'promt' => '/Username:\s+$/i'],
            ['command' => "guest", 'promt' => '/Password:\s+$/i'],
            ['command' => "", 'promt' => 'Username:\s+$/i'],
        ];

        //Set commands
        $commander->setCommands($commands);

        //Execute commands
        $data = $commander->processCommands();

        $this->assertTrue(strpos($data,'Login invalid') !== false);
    }

}