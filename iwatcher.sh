# In the example below, the command we want to run is `php -f "$DIR/../run.php"` - change this on lines 30 and 46
# $DIR is just the directory containing this script file (computed automatically, but you can hardcode it if you want).
# Use $DIR to specify paths relative to the script's path.

# The PROCESS_NAME variable is the name of the process to kill and restart
# You must ensure your process has the same name each time it runs (we use a file at $DIR/../servername to store the name)
# Alternatively, you can hard code the PROCESS_NAME on line 15 if you like

# Requires inotifywait: sudo apt-get install inotify-tools

#!/bin/bash

ulimit -n 100000

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

printf ${DIR}

PROCESS_NAME='Polyel'

# printf "$PROCESS_NAME\n"

pkill -f "$PROCESS_NAME"

function clean_up
{
    # Perform program exit housekeeping
	pkill -f -9 "$PROCESS_NAME"
	exit
}

trap clean_up SIGHUP SIGINT SIGTERM

php -f "$DIR/server.php" &

while true; do

	inotifywait -r -m $DIR

	pkill -f "$PROCESS_NAME"

	while pkill -0 -f "$PROCESS_NAME"; do

		sleep 0.5

	done

	printf "\n---\n"

	php -f "$DIR/server.php" &

	printf "\n\n\n"

done
