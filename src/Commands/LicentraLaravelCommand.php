<?php

namespace Licentra\LicentraLaravel\Commands;

use Illuminate\Console\Command;

class LicentraLaravelCommand extends Command
{
    public $signature = 'licentra-laravel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
