<?php

namespace Dedoc\Scramble\Console\Commands;

use Dedoc\Scramble\Console\Commands\Components\TermsOfContentItem;
use Dedoc\Scramble\Exceptions\ConsoleRenderable;
use Dedoc\Scramble\Exceptions\RouteAware;
use Dedoc\Scramble\Generator;
use Dedoc\Scramble\GeneratorConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'scramble:analyze',
    description: 'Analyzes the documentation generation process to surface any issues.',
)]
class AnalyzeDocumentation extends Command
{
    public function __construct(
        private Generator $generator,
        private GeneratorConfig $config
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'api',
                null,
                InputOption::VALUE_OPTIONAL,
                'The API to analyze',
                'default'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->generator->setThrowExceptions(false);

        ($this->generator)($this->config);

        $i = 1;
        foreach ($this->groupExceptions($this->generator->exceptions) as $group => $exceptions) {
            $this->renderExceptionsGroup($exceptions, $group, $i, $io);
        }

        if (count($this->generator->exceptions)) {
            $io->error('[ERROR] Found '.count($this->generator->exceptions).' errors.');

            return Command::FAILURE;
        }

        $io->success('Everything is fine! Documentation is generated without any errors 🍻');

        return Command::SUCCESS;
    }

    private function groupExceptions(array $exceptions): array
    {
        $groups = [];
        foreach ($exceptions as $exception) {
            $key = $exception instanceof RouteAware ? $this->getRouteKey($exception->getRoute()) : '';
            $groups[$key][] = $exception;
        }
        return $groups;
    }

    private function renderExceptionsGroup(array $exceptions, string $group, int &$i, SymfonyStyle $io): void
    {
        if ($group) {
            $this->renderRouteExceptionsGroupLine($exceptions, $io);
        }

        foreach ($exceptions as $exception) {
            $this->renderException($exception, $i, $io);
            $i++;
            $io->newLine();
        }
    }

    private function getRouteKey(?object $routeInfo): string
    {
        if (! $routeInfo) {
            return '';
        }
        // Assuming $routeInfo implements a similar interface to the old Route
        return $routeInfo->getMethod().'.'.$routeInfo->getAction();
    }

    private function renderRouteExceptionsGroupLine(array $exceptions, SymfonyStyle $io): void
    {
        $firstException = $exceptions[0];
        $routeInfo = $firstException->getRoute();

        $method = $routeInfo->getMethod();
        $errorsMessage = ($count = count($exceptions)).' '.($count > 1 ? 'errors' : 'error');

        $io->writeln(sprintf(
            '<options=bold;fg=%s>%s</> %s <fg=red>%s</>',
            $this->getHttpMethodColor($method),
            $method,
            $routeInfo->getUri(),
            $errorsMessage
        ));
        $io->writeln($this->getRouteAction($routeInfo));
        $io->newLine();
    }

    private function getHttpMethodColor(string $method): string
    {
        return match (strtoupper($method)) {
            'POST', 'PUT' => 'blue',
            'DELETE' => 'red',
            default => 'yellow',
        };
    }

    public function getRouteAction(?object $routeInfo): ?string
    {
        $action = $routeInfo->getAction();
        if (!is_string($action) || !str_contains($action, '@')) {
            return null;
        }

        [$class, $method] = explode('@', $action, 2);

        $className = str_replace(['App\Http\Controllers\\', 'App\Http\\'], '', $class);

        return "<fg=gray>{$className}@{$method}</>";
    }

    private function renderException(Throwable $exception, int $i, SymfonyStyle $io): void
    {
        $message = str_replace('Dedoc\Scramble\Support\Generator\Types\\', '', property_exists($exception, 'originalMessage') ? $exception->originalMessage : $exception->getMessage());

        $io->writeln("<options=bold>$i. {$message}</>");

        if ($exception instanceof ConsoleRenderable) {
            $exception->renderInConsole($io);
        }
    }
}
