#!/bin/sh
# upgrade bluespice free docker container to bluespice pro

#download bluespice from hallowelt server before running this script, put into /data/ directory ...
#collect needed data:
if [ -f $BLUESPICE_CONFIG_PATH/$BLUESPICE_PRO_KEY_FILE ]; then
  TOKEN=$(cat $BLUESPICE_CONFIG_PATH/$BLUESPICE_PRO_KEY_FILE)
else
  TOKEN=""
fi

rm -f $BLUESPICE_PRO_FILE
rm -f $BLUESPICE_CONFIG_PATH/$BLUESPICE_UPGRADE_ERRORFILE
curl --fail -o $BLUESPICE_PRO_FILE -H "Authorization: Bearer $TOKEN" $BLUESPICE_AUTOSERVICE_URL

if [ ! -f $BLUESPICE_PRO_FILE ]; then
  echo "Error while downloading upgrade file" > $BLUESPICE_CONFIG_PATH/$BLUESPICE_UPGRADE_ERRORFILE
  exit
fi

bluespice-backup-free.sh

#install bluespice pro and save snapshot
if [ -f $BLUESPICE_PRO_FILE  ] && [ -f $BLUESPICE_FREE_BACKUPFILE ]; then
  {
    rm -Rf $BLUESPICE_WEBROOT
    mkdir $BLUESPICE_WEBROOT
    unzip $BLUESPICE_PRO_FILE -d $BLUESPICE_WEBROOT
				echo ${BLUESPICE_PRO_FILE} > ${BLUESPICE_FREE_FILE}/${BLUESPICE_VERSION_FILE}
    rm $BLUESPICE_PRO_FILE
    cd $BLUESPICE_WEBROOT

    fileLocalSettings="${BLUESPICE_CONFIG_PATH}/LocalSettings.php"
    ln -s $fileLocalSettings ${BLUESPICE_WEBROOT}/LocalSettings.php

    #remove bad things added from installer
    sed -i '/^wfLoadSkin/d' $fileLocalSettings

    #update data and webservices
    find $BLUESPICE_WEBROOT -name '*.war' -exec mv {} /var/lib/tomcat8/webapps/ \;
    php ${BLUESPICE_WEBROOT}/maintenance/update.php --quick
    php ${BLUESPICE_WEBROOT}/maintenance/rebuildall.php
    chown www-data:www-data ${BLUESPICE_WEBROOT} -R
  } || {
    #restore original if something went wrong
    echo "Error while installing upgrade file" > $BLUESPICE_CONFIG_PATH/$BLUESPICE_UPGRADE_ERRORFILE
    touch $BLUESPICE_CONFIG_PATH/$BLUESPICE_DOWNGRADE_JOBFILE
  }

  #cronjobs ...
else
  echo "Error: upgrade- or backup-file missing" > $BLUESPICE_CONFIG_PATH/$BLUESPICE_UPGRADE_ERRORFILE
fi
