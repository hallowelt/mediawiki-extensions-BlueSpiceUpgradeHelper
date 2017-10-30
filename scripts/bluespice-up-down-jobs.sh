#!/bin/sh

inotifywait -m $BLUESPICE_CONFIG_PATH -e create |
    while read path action file; do
        if [ "$file" = "$BLUESPICE_UPGRADE_JOBFILE" ]; then
          echo "Upgrade request ..."
          sh /usr/sbin/bluespice-upgrade.sh
        elif [ "$file" = "$BLUESPICE_DOWNGRADE_JOBFILE" ]; then
          echo "Downgrade request ..."
          sh /usr/sbin/bluespice-downgrade.sh
        fi

        rm $BLUESPICE_CONFIG_PATH/$BLUESPICE_UPGRADE_JOBFILE
        rm $BLUESPICE_CONFIG_PATH/$BLUESPICE_DOWNGRADE_JOBFILE
    done
