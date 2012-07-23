<?php
namespace Khepin\Medusa\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;
use Guzzle\Service\Client;
use Composer\Json\JsonFile;

class AddRepoCommand extends Command
{
    protected $guzzle;

    public function __construct()
    {
        parent::__construct();
        $this->guzzle = new Client('http://packagist.org');
    }

    protected function configure()
    {
        $this
            ->setName('add')
            ->setDescription('Add a package to satis')
            ->setDefinition(array(
                new InputArgument('package', InputArgument::REQUIRED, 'The name of a composer package', null),
                new InputArgument('repos-dir', InputArgument::OPTIONAL, 'Location where to output built files', null),
                new InputOption('config-file', null, InputOption::VALUE_NONE, 'The config file to update with the new info'),
            ))
            ->setHelp(<<<EOT
The <info>mirror</info> command reads the given composer.lock file and mirrors
each git repository so they can be used locally.
<warning>This will only work for repos hosted on github.</warning>
EOT
            )
        ;
    }

    /**
     * @param InputInterface  $input  The input instance
     * @param OutputInterface $output The output instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = $input->getArgument('repos-dir');
        $package = $input->getArgument('package');

        $output->writeln(' - Fetching <info>'.$package.'</info>');
        $this->getGitRepo($package, $dir);
        $output->writeln('');

        if($input->getOption('config-file')){
            $file = new JsonFile($input->getOption('config-file'));
            $config = $file->read();
            $url = realpath($dir.'/'.str_replace('/', '-', $package).'.git');
            $repo = array('type' => 'git', 'url' => 'file:///'.$url);
            $config['repositories'][] = $repo;
            $file->write($config);
        }
    }

    protected function getGitRepo($package, $outputDir)
    {
        $response = $this->guzzle->get('/packages/'.$package.'.json')->send();
        $response = $response->getBody(true);
        $package = json_decode($response);
        $url = $package->package->repository;
        if(strpos($url, 'github') === false){
            return;
        }
        $package = $package->package->name;
        $downloader = new \Khepin\Medusa\Downloader($package, $url);
        $downloader->download($outputDir);
    }
}
