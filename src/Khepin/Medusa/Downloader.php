<?php
namespace Khepin\Medusa;

use Symfony\Component\Process\Process;

class Downloader
{
    protected $url;

    protected $package;

    public function __construct($package, $url)
    {
        $this->package = $package;
        $this->url = $url;
    }

    public function download($in_dir)
    {
        $cmd = 'git clone --mirror %s %s';
        $dir = $in_dir.'/'.$this->package.'.git';
        if(is_dir($dir)){
            return;
        }
        $process = new Process(sprintf($cmd, $this->url, $dir));
        $process->setTimeout(3600);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }
    }
}