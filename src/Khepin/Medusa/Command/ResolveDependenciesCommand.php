<?php
namespace Khepin\Medusa\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Khepin\Medusa\DependencyResolver;

class ResolveDependenciesCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('resolve')
            ->setDescription('Finds the dependencies of a package')
            ->setDefinition(array(
                new InputArgument('package', InputArgument::REQUIRED, 'The name of a composer package', null),
            ))
        ;
    }

    /**
     * @param InputInterface  $input  The input instance
     * @param OutputInterface $output The output instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resolver = new DependencyResolver($input->getArgument('package'));
    }
}
