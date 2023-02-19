<?php

declare(strict_types=1);

namespace Quartetcom\StaticAnalysisKit\PhpCsFixer;

use Quartetcom\StaticAnalysisKit\ProcessTtyTrait;
use Symfony\Component\Process\Process;

class Runner
{
    use ProcessTtyTrait;

    /**
     * @param list<string> $command
     */
    public function __construct(
        private readonly array $command = ['php', './vendor/bin/php-cs-fixer', 'fix', '-vv'],
        private readonly string $distConfigPath = './.php-cs-fixer.dist.php',
        private readonly string $configPath = './.php-cs-fixer.php',
    ) {
    }

    /**
     * @param list<string> $additionalArguments
     */
    public function run(bool $risky, array $additionalArguments = []): int
    {
        $distConfigPath = $this->distConfigPath;
        $configPath = $this->configPath;

        $useRiskyRules = $risky ? 'true' : 'false';
        $config = <<<"EOF"
            <?php // GENERATED BY quartetcom/static-analysis-kit. DO NOT EDIT.

            declare(strict_types=1);

            return (require_once('{$distConfigPath}'))
                ->configure(useRiskyRules: {$useRiskyRules})
            ;

            EOF;

        if (file_exists($configPath)) {
            throw new \RuntimeException(
                "File '{$configPath}' already exists. Use '{$distConfigPath}' instead.",
            );
        }

        file_put_contents($configPath, $config);

        $exitCode = $this->runInTtyOrFallback(
            new Process(
                [...$this->command, ...$additionalArguments],
                env: [
                    // TODO: Delete this after php-cs-fixer supported PHP 8.2 officially
                    'PHP_CS_FIXER_IGNORE_ENV' => '1',
                ],
                timeout: null,
            ),
        );

        @unlink($configPath);

        return $exitCode;
    }
}
