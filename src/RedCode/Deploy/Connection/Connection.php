<?php
namespace RedCode\Deploy\Connection;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author maZahaca
 */ 
class Connection
{
    /** @var string */
    private $dsn;

    /** @var OutputInterface */
    private $output;

    /**
     * @param string $dsn
     * @param OutputInterface $output
     */
    public function __construct($dsn, $output = null)
    {
        $this->dsn      = $dsn;
        $this->output   = $output;
    }

    /**
     * @throws \Exception
     * @return self
     */
    public function test()
    {
        if(trim(`ssh -o BatchMode=yes -o ConnectTimeout=5 {$this->dsn} echo ok`) !== 'ok') {
            throw new \Exception(sprintf('Connection to %s is failed. Check your credentials.', $this->dsn));
        }
        if($this->output) {
            $this->output->writeln(sprintf('Connection to %s is ok', $this->dsn));
        }
        return $this;
    }

    /**
     * Execute server command via ssh tool
     * @param $command
     * @return self
     */
    public function execute($command)
    {
        return $this->doCommand(
            sprintf('ssh %s %s', $this->dsn, $command)
        );
    }

    /**
     * Use connection for send files on server via scp tool
     * @param $localPath
     * @param $serverPath
     * @return self
     */
    public function transfer($localPath, $serverPath)
    {
        return $this->doCommand(
            sprintf('scp %s %s:%s', $localPath, $this->dsn, $serverPath)
        );
    }

    /**
     * Execute command
     * @param $command
     * @return self
     */
    private function doCommand($command)
    {
        `{$command}`;
        if($this->output) {
            $this->output->writeln($command);
        }
        return $this;
    }
}
 