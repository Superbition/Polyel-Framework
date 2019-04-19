#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

#echo $DIR

cd $DIR/../

PROCESS_NAME='Phase'

#printf "$PROCESS_NAME\n"

pkill -f "$PROCESS_NAME"

function clean_up
{
    # Perform program exit housekeeping
	pkill -f -9 "$PROCESS_NAME"
	exit
}

trap clean_up SIGHUP SIGINT SIGTERM

DATE="$(date +'%d-%m-%Y %T')"
printf "\n-------------------------------------------------------------------\n"

printf "Starting the Phase Server [${DATE}]"

printf "\n-------------------------------------------------------------------\n"

php -f "$DIR/server.php" &

DIR_HASH="$(tar cf - Phase/ | sha1sum)"

#echo $DIR_HASH

while true; do

    sleep 1

    UPDATED_DIR_HASH="$(tar cf - Phase/ | sha1sum)"

    if [ "$UPDATED_DIR_HASH" != "$DIR_HASH" ]
    then

        DIR_HASH=$UPDATED_DIR_HASH

        DATE="$(date +'%d-%m-%Y %T')"
        printf "\n-------------------------------------------------------------------\n"

        printf "Code Change Detected [${DATE}]"

        printf "\n-------------------------------------------------------------------\n"

        pkill -f "$PROCESS_NAME"

	    while pkill -0 -f "$PROCESS_NAME"; do

		    sleep 0.5

	    done

        DATE="$(date +'%d-%m-%Y %T')"
	    printf "\n-------------------------------------------------------------------\n"

	    printf "Restarting the server... [${DATE}]"

	    printf "\n-------------------------------------------------------------------\n"

	    php -f "$DIR/server.php" &

    fi

done