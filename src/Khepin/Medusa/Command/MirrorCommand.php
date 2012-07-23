<?php
namespace Khepin\Medusa\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Khepin\Medusa\DependencyResolver;
use Guzzle\Service\Client;
use Khepin\Medusa\Downloader;
use Symfony\Component\Console\Input\ArrayInput;

class MirrorCommand extends Command
{
    protected $guzzle;

    protected function configure()
    {
        $this
            ->setName('mirror')
            ->setDescription('Mirrors all repositories given a config file')
            ->setDefinition(array(
                new InputArgument('config', InputArgument::OPTIONAL, 'A config file', null),
            ))
        ;
    }

    /**
     * @param InputInterface  $input  The input instance
     * @param OutputInterface $output The output instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>First getting all dependencies</info>');
        $this->guzzle = new Client('http://packagist.org');
        $config = json_decode(file_get_contents($input->getArgument('config')));
        $repos = [];
        foreach($config->require as $dependency){
            $output->writeln(' - Getting dependencies for <info>'.$dependency.'</info>');
            $resolver = new DependencyResolver($dependency);
            $deps = $resolver->resolve();
            $repos = array_merge($repos, $deps);
        }

        $output->writeln('<info>Create mirror repositories</info>');
        foreach($repos as $repo){
            $command = $this->getApplication()->find('add');

            $arguments = array(
                'command'   => 'add',
                'package'   => $repo,
                'repos-dir' => $config->repodir,
            );
            if(!is_null($config->satisconfig)){
                $arguments['--config-file'] = $config->satisconfig;
            }

            $input = new ArrayInput($arguments);
            $returnCode = $command->run($input, $output);
        }
    }
}
