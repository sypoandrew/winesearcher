<?php

namespace Sypo\WineSearcher\Console\Commands;

use Illuminate\Console\Command;
use Sypo\WineSearcher\Models\Sypo\WineSearcherFeed;

class WineSearcherFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sypo:winesearcher:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the WineSearcher XML feed';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $f = new WineSearcherFeed;
		if($f->call()){
			$this->info('Feed generated successfully');
		}
		else{
			$this->info('Feed failed to generated');
		}
    }
}
