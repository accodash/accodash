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
    protected $signature = 'data:populate {directory?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description =
    'This command is used to populate your database with data.
    It has argument {directory} which allows you to used specified directory.
    By default it will use all directories from scraperLogs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $settings = config('scraper.command');
        $directory = $this->argument('directory') ?? null;
        $directories = scandir(config('scraper.command.scraper_directory'));

        // Additional 2 for './' and '../'.
        if (count($directories) < $settings['min_number_of_directories'] + 2) {
            alert("There are no directories with data.");
            die();
        }

        $directories = array_slice($directories, 2);

        if ($directory) {
            if (!in_array($directory, $directories)) {
                alert("This directory doesn't exist.");
                die();
            }

            $directories = [$directory];
        }

        $populateService = new PopulateService();
        $populateService->populate($directories);
    }
}
