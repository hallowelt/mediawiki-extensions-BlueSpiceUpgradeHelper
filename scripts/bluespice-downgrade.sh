#!/bin/sh

if [ -f $BLUESPICE_FREE_BACKUPFILE ]; then
  {
    rm -R $BLUESPICE_WEBROOT
    mkdir $BLUESPICE_WEBROOT
    unzip $BLUESPICE_FREE_BACKUPFILE -d $BLUESPICE_WEBROOT
    rm $BLUESPICE_FREE_BACKUPFILE
    php ${BLUESPICE_WEBROOT}/maintenance/update.php --quick
    php ${BLUESPICE_WEBROOT}/maintenance/rebuildall.php
    chown www-data:www-data ${BLUESPICE_WEBROOT} -R
  } || {
    echo "Error while installing backup file" > $BLUESPICE_CONFIG_PATH/$BLUESPICE_DOWNGRADE_ERRORFILE
  }
else
  echo "Error: no Backup file found" > $BLUESPICE_CONFIG_PATH/$BLUESPICE_DOWNGRADE_ERRORFILE
fi
