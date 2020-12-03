<?php

namespace Polyel\Console\Commands;

use Polyel\Console\Command;
use Polyel\Encryption\EncryptionManager;

class GenerateEncryptionKeyCommand extends Command
{
    public string $description = 'Generates and saves a new encryption key inside the .env file';

    public function execute(EncryptionManager $encryptionManager)
    {
        if(!file_exists(APP_DIR . '/config/env/.env'))
        {
            $this->fatal("Cannot generate encryption key, no .env file exists! \n\n\tPlease create one at: /config/env/.env");
        }

        /*
         * Generate a new Encryption Key
         */
        $this->writeNewLine('Generating and building new encryption key...');
        $newEncryptionKey = $encryptionManager->generateEncryptionKey();
        $this->writeNewLine('New encryption key generated!');

        /*
         * Access the .env file and parse the input so a new key can be set
         */
        $this->writeNewLine('Accessing the .env file');
        $currentKey = env('Encryption.KEY', null);
        $envFile = parse_ini_file(APP_DIR . '/config/env/.env', true);

        if(is_null($currentKey))
        {
            $this->info('No encryption key exists in .env file');
        }
        else
        {
            $this->info('Encryption key already set, will backup the current key');
            $envFile['Encryption']['OLD_KEY'] = $currentKey;
        }

        /*
         * Overwrite the old Encryption Key
         */
        $this->writeNewLine('Setting new encryption key');
        $envFile['Encryption']['KEY'] = $newEncryptionKey;

        /*
         * Before writing to the .env INI file with the changes,
         * check to see if we have comments, as they will need
         * to be reinserted once the new data has been saved.
         */
        $iniComments = $this->getIniComments();

        $this->writeNewLine('Saving changes to INI file...');
        $this->saveIniFile($envFile);

        if(!empty($iniComments))
        {
            $this->info('INI comments were found, adding them back');
            $this->reSaveIniComments($iniComments);
        }

        $this->writeNewLine("\e[32m\nGenerated and set a new encryption key! Keep this key safe!", 2);
    }

    private function getIniComments()
    {
        /*
         * $commentFound:            Used to keep track of when a INI comment was detected within the loop.
         * $newLinesBeforeComments:  Used to keep track of blank lines before INI comments.
         * $iniComments:             Where all the detected INI comments are stored that are from the .env file
         */
        $commentFound = false;
        $newLinesBeforeComments = 0;
        $iniComments = [];

        // Loop through the INI file line by line, checking for comments...
        $envFile = fopen(APP_DIR . '/config/env/.env', 'rb+');
        while(($line = stream_get_line($envFile, 1024 * 1024, "\n")) !== false)
        {
            // Keep track of newlines before comments, these get added in when a comment is found
            if($commentFound === false && empty($line))
            {
                $newLinesBeforeComments++;
            }

            // Process a comment to get a target to figure out where the comment is located in the INI file
            if($commentFound === true)
            {
                /*
                 * A comment was detected but no target was found on the next line(s)
                 * but we want to respect any blank lines between comments and
                 * a suitable comment target, so we inject a newline if one is
                 * found.
                 */
                if(empty($line))
                {
                    $iniComments[array_key_last($iniComments)]['comment'] .= "\n";

                    continue;
                }

                /*
                 * A comment can be detected when we are already trying to find
                 * a target for a comment, this just means we have comments bunched
                 * to together like a comment block. So we just add the comment to the
                 * last found comment in the array.
                 */
                if($line[0] === '#')
                {
                    $iniComments[array_key_last($iniComments)]['comment'] .= "$line\n";

                    continue;
                }

                /*
                 * Getting to this stage means we have found a target for
                 * the comment that was detected but to respect any newlines
                 * before comments, we insert newlines in front of the actual
                 * comment that was detected first.
                 */
                if($newLinesBeforeComments > 0)
                {
                    $commentTemp = $iniComments[array_key_last($iniComments)]['comment'];

                    $iniComments[array_key_last($iniComments)]['comment'] =
                        str_repeat("\n", $newLinesBeforeComments) . $commentTemp;

                    $newLinesBeforeComments = 0;
                }

                // A target was found for the detected comment, set the target for the last detected comment
                $iniComments[array_key_last($iniComments)]['target'] = $line;

                // Reset the flag that indicates we are looking for a comment target
                $commentFound = false;

                continue;
            }

            /*
             * Looping through the INI file line by line, checking if
             * the line starts with a hash mark which indicates we
             * have found a comment. Add the comment to the array and
             * set the flag to indicate we are not looking for a comment
             * target.
             */
            if(!empty($line) && $line[0] === '#')
            {
                $iniComments[] = ['comment' => "$line\n"];

                $commentFound = true;

                continue;
            }
        }

        fclose($envFile);

        return $iniComments;
    }

    private function saveIniFile(array $iniData)
    {
        if(!file_exists(APP_DIR . '/config/env/.env'))
        {
            $this->fatal("Cannot save new encryption key, no .env file found!");
        }

        $newIniFile = '';

        foreach($iniData as $section => $sectionKeyValues)
        {
            $sectionKeyValues = array_map(function($value, $key)
            {
                return "$key='$value'";
            }, array_values($sectionKeyValues), array_keys($sectionKeyValues));

            $sectionKeyValues = implode("\n", $sectionKeyValues);

            $newIniFile .= "[$section]\n$sectionKeyValues\n";
        }

        file_put_contents(APP_DIR . '/config/env/.env', $newIniFile);
    }

    private function reSaveIniComments(array $comments)
    {
        // Convert the .env file into a line by line array
        $env = file(APP_DIR . '/config/env/.env');

        // Reverse the array to get the comments in the correct order from top to bottom
        $comments = array_reverse($comments);

        foreach($comments as $comment)
        {
            // Trim each env line so that we can find the target without newlines/spaces converting array_search
            $envTrimmed = array_map(function($line)
            {
                // Only trim a line when we don't have a newline or a comment
                if(trim($line, " \t\r\0\x0B") !== PHP_EOL && $line[0] !== '#')
                {
                    return trim($line);
                }

                // Only when we have a newline or INI comment
                return $line;

            }, $env);

            // The offset is the distance that the comment is from the target
            $offset = array_search($comment['target'], $envTrimmed, true);

            // Reinsert the comment using the offset from the target
            array_splice($env, $offset, 0, $comment['comment']);
        }

        // Finally save the .env file with the comments reinserted
        file_put_contents(APP_DIR . '/config/env/.env', $env);
    }
}