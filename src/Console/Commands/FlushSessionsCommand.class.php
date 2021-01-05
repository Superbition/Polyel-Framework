<?php

namespace Polyel\Console\Commands;

use Polyel\Console\Command;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class FlushSessionsCommand extends Command
{
    public string $description = 'Deletes all sessions using the selected Session driver';

    public function execute()
    {
        // Find the session drive that is being used as we need to know where to delete active sessions...
        $this->writeNewLine('Getting the selected session driver from the config...');
        $sessionDriver = config('session.driver');

        // Use a switch to support the different type of session drivers
        switch($sessionDriver)
        {
            /*
             * The file session driver.
             * Deletes session files locally.
             */
            case 'file':

                $this->writeNewLine('File Session driver has been selected!');

                $pathToFileSessions = APP_DIR . '/storage/polyel/sessions/';

                /*
                 * Get a list of files to check if it is a session and
                 * if to delete it or not and skip all dot files.
                 */
                $directoryToFileSessions = new RecursiveDirectoryIterator(
                    $pathToFileSessions, RecursiveDirectoryIterator::SKIP_DOTS);
                $sessions = new RecursiveIteratorIterator($directoryToFileSessions,
                    RecursiveIteratorIterator::CHILD_FIRST);

                // If a session prefix is set, we can check if the file starts with it
                $sessionPrefix = config('session.prefix');

                // Keep count of how many sessions get deleted
                $sessionsDeleted = 0;

                $this->writeNewLine('');

                /*
                 * Loop through each session file, check if it starts with the
                 * prefix if one is set or check that it is not a hidden file and if not
                 * then delete it, if no prefix is used.
                 */
                foreach($sessions as $session)
                {
                    if(!empty($sessionPrefix))
                    {
                        // Does the file name start and match the prefix set within the session config
                        if(substr($session->getFileName(), 0, strlen($sessionPrefix)) === $sessionPrefix)
                        {
                            $this->info('Removing session file: ' . $session->getFileName());
                            unlink($session->getRealPath());

                            $sessionsDeleted++;
                        }

                        continue;
                    }

                    // When no prefix is set, only delete the file if it is not a hidden file starting with a dot
                    if(strpos($session->getFileName(), '.') !== 0)
                    {
                        $this->info('Removing session file: ' . $session->getFileName());
                        unlink($session->getRealPath());

                        $sessionsDeleted++;
                    }
                }

                $this->writeNewLine('');

                if($sessionsDeleted === 0)
                {
                    $this->writeNewLine("\e[32mNo sessions exist to delete!", 3);
                }
                else if($sessionsDeleted >= 1)
                {
                    $this->writeNewLine("\e[32mFinished deleting $sessionsDeleted sessions", 2);
                }

            break;
        }
    }
}