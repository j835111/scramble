<?php

namespace Dedoc\Scramble\Console\Commands;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\GeneratorConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'scramble:export',
    description: 'Export the OpenAPI document to a JSON file.',
)]
class ExportDocumentation extends Command
{
    public function __construct(
        private Generator $generator,
        private Filesystem $filesystem,
        private GeneratorConfig $config // Assuming a default config is injected
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'path',
                null,
                InputOption::VALUE_REQUIRED,
                'The path to save the exported JSON file'
            )
            ->addOption(
                'api',
                null,
                InputOption::VALUE_OPTIONAL,
                'The API to export a documentation for',
                'default'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $api = $input->getOption('api');
        $path = $input->getOption('path');

        // In a real multi-API scenario, we'd need a service to fetch the correct config.
        // For now, we use the injected default config.
        $specification = json_encode(($this->generator)($this->config), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $filename = $path ?: $this->config->get('export_path') ?? 'api'.($api === 'default' ? '' : "-$api").'.json';

        $this->filesystem->dumpFile($filename, $specification);

        $io->success("OpenAPI document exported to {$filename}.");

        return Command::SUCCESS;
    }
}
