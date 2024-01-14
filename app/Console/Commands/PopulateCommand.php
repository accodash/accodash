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
    protected $signature = 'app:populate-command {directory?}';

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
        $settings = config('scraper.command');
        $directory = $this->argument('directory') ?? null;
        $directories = scandir("./scraperLogs");

        // Additional 2 for './' and '../'
        if (count($directories) < $settings['min_number_of_directories'] + 2) {
            alert("There are no directories with data");
            die();
        }
        $directories = array_slice($directories, 2);

        if ($directory) {
            if (!in_array($directory, $directories)) {
                alert("This directory doesn't exist");
                die();
            }

            $directories = [$directory];
        }

        $populateService = new PopulateService();
        $populateService->populate($directories);
    }
}
