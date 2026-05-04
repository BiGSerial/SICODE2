<?php

namespace App\Console\Commands\Concerns;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;

trait ShowsProgress
{
    protected function shouldShowProgress(): bool
    {
        return $this->input->isInteractive()
            && defined('STDOUT')
            && (
                (function_exists('stream_isatty') && stream_isatty(STDOUT))
                || (function_exists('posix_isatty') && posix_isatty(STDOUT))
            );
    }

    protected function createProgressBar(int $max = 0, float $minSecondsBetweenRedraws = 0.1): ProgressBar
    {
        return new ProgressBar(
            $this->shouldShowProgress() ? $this->output : new NullOutput(),
            $max,
            $minSecondsBetweenRedraws
        );
    }

    protected function progressStart(int $max = 0): void
    {
        if ($this->shouldShowProgress()) {
            $this->output->progressStart($max);
        }
    }

    protected function progressAdvance(int $step = 1): void
    {
        if ($this->shouldShowProgress()) {
            $this->output->progressAdvance($step);
        }
    }

    protected function progressFinish(): void
    {
        if ($this->shouldShowProgress()) {
            $this->output->progressFinish();
        }
    }
}
