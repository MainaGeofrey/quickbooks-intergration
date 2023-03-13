<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleQB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:qb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send data to quick_books';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        //
        Log::info("new rats");
    }

    public function fetchPayment(){
      
    }
}
