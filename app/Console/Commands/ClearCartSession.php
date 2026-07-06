<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearCartSession extends Command
{
    protected $signature = 'ClearCartSession';

    public function handle(): int
    {
        $name = 'cartProducts';

        // Get the path to the session files
        $sessionPath = storage_path('framework/sessions');

        // Check if session path exists
        if (!File::exists($sessionPath)) {
            $this->error("Session path not found: $sessionPath");
            return 1;
        }

        // Get all session files
        $sessionFiles = File::files($sessionPath);

        foreach ($sessionFiles as $file) {
            $contents = File::get($file);

            // Un-serialize the session data
            $sessionData = unserialize($contents);

            if (isset($sessionData[$name])) {
                unset($sessionData[$name]);

                // Re-serialize and write back to the file
                File::put($file, serialize($sessionData));

                $this->info("Cleared session variable '{$name}' in file {$file->getFilename()}");
            }
        }

        $this->info("Session variable '{$name}' cleared for all sessions.");

        return 0;
    }
}
