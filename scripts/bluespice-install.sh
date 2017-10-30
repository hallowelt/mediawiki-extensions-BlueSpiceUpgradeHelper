#!/bin/sh

fileLocalSettings="${BLUESPICE_CONFIG_PATH}/LocalSettings.php"
fileWikiSysopPass="${BLUESPICE_CONFIG_PATH}/wikisysop_password.txt"

mkdir -p ${BLUESPICE_DATA_PATH}/cache
mkdir -p ${BLUESPICE_DATA_PATH}/images
mkdir -p ${BLUESPICE_DATA_PATH}/data
mkdir -p ${BLUESPICE_DATA_PATH}/config
mkdir -p ${BLUESPICE_DATA_PATH}/compiled_templates

if [ ! -f $fileLocalSettings ]; then
  chown www-data:www-data ${BLUESPICE_WEBROOT} -R
  if [ ! -f $fileWikiSysopPass ]; then
    WIKI_ADMIN_PASS=$(openssl rand -base64 32)
    echo $WIKI_ADMIN_PASS > $fileWikiSysopPass
  else
    WIKI_ADMIN_PASS=$( cat $fileWikiSysopPass )
  fi

  cd "$BLUESPICE_WEBROOT"; php maintenance/install.php --dbserver "$DB_HOST" --dbport "$DB_PORT" --dbname "$DB_NAME" --dbuser "$DB_USER" --dbpass "$DB_PASSWORD" --pass "$WIKI_ADMIN_PASS" --scriptpath /bluespice "$WIKI_NAME" "$WIKI_ADMIN"

  #post install
  if [ -f LocalSettings.php ]; then
    mv ${BLUESPICE_WEBROOT}/LocalSettings.php $fileLocalSettings
    ln -s $fileLocalSettings ${BLUESPICE_WEBROOT}/LocalSettings.php

    echo "wfLoadExtension('BlueSpiceExtensions/UniversalExport');" >> ${BLUESPICE_WEBROOT}/LocalSettings.php
    echo "wfLoadExtension('BlueSpiceExtensions/UEModulePDF');" >> ${BLUESPICE_WEBROOT}/LocalSettings.php

    cp ${BLUESPICE_WEBROOT}/extensions/BlueSpiceFoundation/config.template/* ${BLUESPICE_DATA_PATH}/config/.

  else
    echo "Error occured: installation not successfull, LocalSettings.php is missing"
  fi

else
  ln -s ${BLUESPICE_CONFIG_PATH}/LocalSettings.php ${BLUESPICE_WEBROOT}/LocalSettings.php
fi

php ${BLUESPICE_WEBROOT}/maintenance/update.php --quick
#php ${BLUESPICE_WEBROOT}/extensions/BlueSpiceExtensions/ExtendedSearch/maintenance/searchUpdate.php

chown www-data:www-data ${BLUESPICE_WEBROOT} -R
chown www-data:www-data ${BLUESPICE_DATA_PATH} -R
chown www-data:www-data ${BLUESPICE_CONFIG_PATH} -R
