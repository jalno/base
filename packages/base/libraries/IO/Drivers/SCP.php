<?php

namespace packages\base\IO\Drivers;

use packages\base\SSH;

class SCP
{
    private $ssh;

    public function __construct(SSH $ssh)
    {
        $this->ssh = $ssh;
    }

    public function upload($local, $remote, $mode = 0644)
    {
        return ssh2_scp_send($this->ssh->connection(), $local, $remote, $mode);
    }

    public function download($remote, $local)
    {
        return ssh2_scp_recv($this->ssh->connection(), $remote, $local);
    }
}
