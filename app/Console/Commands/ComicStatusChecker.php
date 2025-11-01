<?php

namespace App\Console\Commands;

use App\Models\ComicQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ComicStatusChecker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:comic-status-checker';

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
        $this->checkStatus();
    }

    private function checkStatus()
    {
        $pendingComics = ComicQueue::where('status', 'pending')->get();
        foreach ($pendingComics as $comic) {

            try {
                $response = Http::get('http://91.98.162.246:5555/status/' . $comic->task_id);

                if ($response->failed()) {
                    echo "Failed to retrieve status for task ID: $comic->task_id\n";
                }
                $status = $response->json()['status'];
                if ($status == 'SUCCESS') {
                    ComicQueue::where('task_id', $comic->task_id)->update([
                        'status' => 'completed',
                        'comic_url' => "/" . $response->json()['pdf_url'],
                    ]);
                }
                echo "Checked status for task ID: $comic->task_id - Status: $status\n";
            } catch (\Exception $e) {
                echo "Error checking status for task ID: $comic->task_id - " . $e->getMessage() . "\n";
            }
        }
    }
}
