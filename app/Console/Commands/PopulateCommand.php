<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PopulateService;
use function Laravel\Prompts\alert;

class PopulateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $directories = scandir("./scraperLogs");

        if (count($directories) < 3) {
            alert("There are no files with data");
            die();
        }

        $populateService = new PopulateService();
        $populateService->populate($directories);
    }
}
