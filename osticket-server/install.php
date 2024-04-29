<?php
/*********************************************************************
    install.php

    osTicket Installer.

    Pierre PINTARIC <pierre@redtrash.fr>
    Copyright (c)  2024 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    From original install.php from osTicket 1.8.1
**********************************************************************/

$_SERVER['HTTP_ACCEPT_LANGUAGE'] = getenv("LANGUAGE") ?: "en-us";

$vars = array(
  'name'            => getenv("INSTALL_NAME")      ?: 'My Helpdesk',
  'email'           => getenv("INSTALL_EMAIL")     ?: 'helpdesk@example.com',
  'url'             => getenv("INSTALL_URL")       ?: 'http://localhost:8080/',
  'lang_id'         => getenv("INSTALL_LANG_ID")   ?: 'en_US',

  'fname'           => getenv("ADMIN_FIRSTNAME")   ?: 'osTicket',
  'lname'           => getenv("ADMIN_LASTNAME")    ?: 'osTicket',
  'admin_email'     => getenv("ADMIN_EMAIL")       ?: 'ostadmin@example.com',
  'username'        => getenv("ADMIN_USERNAME")    ?: 'ostadmin',
  'passwd'          => getenv("ADMIN_PASSWORD")    ?: 'ostadmin',
  'passwd2'         => getenv("ADMIN_PASSWORD")    ?: 'ostadmin',

  'prefix'          => getenv("MYSQL_PREFIX")      ?: 'ost_',
  'dbhost'          => getenv("MYSQL_HOST"),
  'dbname'          => getenv("MYSQL_DATABASE")    ?: 'osticket',
  'dbuser'          => getenv("MYSQL_USER")        ?: 'osticket',
  'dbpass'          => getenv("MYSQL_PASSWORD")    ?: getenv("MYSQL_ENV_MYSQL_PASSWORD"),
       
  'timezone'        => getenv("TZ")                ?: "UTC",

  'smtp_hostname'   => getenv("SMTP_HOSTNAME")     ?: 'localhost',
  'smtp_port'       => getenv("SMTP_PORT")         ?: 25,
  'smtp_from'       => getenv("SMTP_FROM"),
  'smtp_tls'        => getenv("SMTP_TLS"),
  'smtp_tls_certs'  => getenv("SMTP_TLS_CERTS")    ?: '/etc/ssl/certs/ca-certificates.crt',
  'smtp_user'       => getenv("SMTP_USER"),
  'smtp_password'   => getenv("SMTP_PASSWORD"),

  'siri'            => getenv("INSTALL_SECRET"),
  'config'          => '/config/ost-config.php'
);

//Script settings
define('CONNECTION_TIMEOUT_SEC', 180);

function err( $msg) {
  fwrite(STDERR, "$msg\n");
  exit(1);
}

function boolToOnOff($v) {
  return ((boolean) $v) ? 'on' : 'off';
}

function convertStrToBool($varName, $default) {
  global $vars;
   if ($vars[$varName] != '') {
     return $vars[$varName] == '1';
   }
   return $default;
}

// Override Helpdesk URL. Only applied during database installation.
define("URL",$vars['url']);

//Require files (must be done before any output to avoid session start warnings)
chdir("/var/www/osticket/upload/setup");
require "/var/www/osticket/upload/setup/setup.inc.php";
require_once INC_DIR.'class.installer.php';


/************************* Mail Configuration *******************************************/
define('MAIL_CONFIG_FILE', '/etc/msmtp');

echo "Configuring mail settings\n";
if (!$mailConfig = file_get_contents(MAIL_CONFIG_FILE)) {
  err("Failed to load mail configuration file");
};
$mailConfig = str_replace('%SMTP_HOSTNAME%', $vars['smtp_hostname'], $mailConfig);
$mailConfig = str_replace('%SMTP_PORT%', $vars['smtp_port'], $mailConfig);
$v = !empty($vars['smtp_from']) ? $vars['smtp_from'] : $vars['smtp_user'];
$mailConfig = str_replace('%SMTP_FROM%', $v, $mailConfig);
$mailConfig = str_replace('%SMTP_USER%', $vars['smtp_user'], $mailConfig);
$mailConfig = str_replace('%SMTP_PASSWORD%', $vars['smtp_pass'], $mailConfig);
$mailConfig = str_replace('%SMTP_TLS_CERTS%', $vars['smtp_tls_certs'], $mailConfig);

$mailConfig = str_replace('%SMTP_TLS%', boolToOnOff(convertStrToBool('smtp_tls',true)), $mailConfig);
$mailConfig = str_replace('%SMTP_AUTH%', boolToOnOff($vars['smtp_user'] != ''), $mailConfig);

if (!file_put_contents(MAIL_CONFIG_FILE, $mailConfig) || !chown(MAIL_CONFIG_FILE, 'apache')
  || !chgrp(MAIL_CONFIG_FILE, 'apache') || !chmod(MAIL_CONFIG_FILE, 0600)) {
  err("Failed to write mail configuration file");
}

/************************* OSTicket Installation *******************************************/

//Create secret if not set by env var and not previously stored
DEFINE('SECRET_FILE', '/config/secret.txt');
if (!$vars['siri']) {
  if (file_exists(SECRET_FILE)) {
    echo "Loading installation secret\n";
    $vars['siri'] = file_get_contents(SECRET_FILE);
  } else {
    echo "Generating new installation secret and saving\n";
    //Note that this randomly generated value is not intended to secure production sites!
    $vars['siri'] = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890_="), 0, 32);
    if (!file_put_contents(SECRET_FILE, $vars['siri']) || !chmod(SECRET_FILE, 0600)) {
      err("Failed to write secret file");
    }
  }
} else {
  echo "Using installation secret from INSTALL_SECRET environmental variable\n";
  if (!file_put_contents(SECRET_FILE, $vars['siri']) || !chmod(SECRET_FILE, 0600)) {
    err("Failed to write secret file");
  }
}

//Create installer class
$installer = new Installer($vars['config']); //Installer instance.

//Always rewrite config file in case MySQL details changed (e.g. ip address)
DEFINE('CONFIG_TEMPLATE_FILE', '/var/www/osticket/upload/include/ost-sampleconfig.php');
echo "Updating configuration file\n";
if (!$configFile = file_get_contents(CONFIG_TEMPLATE_FILE)) {
  err("Failed to load configuration file: " . CONFIG_TEMPLATE_FILE);
};
$configFile= str_replace("define('OSTINSTALLED',FALSE);","define('OSTINSTALLED',TRUE);",$configFile);
$configFile= str_replace('%ADMIN-EMAIL',$vars['admin_email'],$configFile);
$configFile= str_replace('%CONFIG-DBHOST',$vars['dbhost'],$configFile);
$configFile= str_replace('%CONFIG-DBNAME',$vars['dbname'],$configFile);
$configFile= str_replace('%CONFIG-DBUSER',$vars['dbuser'],$configFile);
$configFile= str_replace('%CONFIG-DBPASS',$vars['dbpass'],$configFile);
$configFile= str_replace('%CONFIG-PREFIX',$vars['prefix'],$configFile);
$configFile= str_replace('%CONFIG-SIRI',$vars['siri'],$configFile);

if (!file_put_contents($vars['config'], $configFile) || !chmod($vars['config'], 0644)) {
  err("Failed to write configuration file");
}

//Determine if using linked container
$linked = (boolean)getenv("MYSQL_ENV_MYSQL_PASSWORD");

if (!$linked) {
  echo "Using external MySQL connection\n";

  //Check mandatory connection settings provided
  if (!getenv("MYSQL_HOST")) {
    err('Missing required environmental variable MYSQL_HOST');
  }
  if (!getenv("MYSQL_PASSWORD")) {
    err('Missing required environmental variable: MYSQL_PASSWORD');
  }
} else {
  echo "Using linked MySQL container\n";
}

//Wait for database connection
echo "Waiting for database TCP connection to become available...\n";
$s = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
$t = 0;
while (!@socket_connect($s,$vars['dbhost'],3306) && $t < CONNECTION_TIMEOUT_SEC) {
  $t++;
  if (($t % 15) == 0) {
    echo "Waited for $t seconds...\n";
  }
  sleep(1);
}
if ($t >= CONNECTION_TIMEOUT_SEC) {
  err("Timed out waiting for database TCP connection");
}

//Check database installation status
$db_installed = false;
echo "Connecting to database mysql://${vars['dbuser']}@${vars['dbhost']}/${vars['dbname']}\n";
if (!db_connect($vars['dbhost'],$vars['dbuser'],$vars['dbpass']))
   err(sprintf(__('Unable to connect to MySQL server: %s'), db_connect_error()));
elseif(explode('.', db_version()) < explode('.', $installer->getMySQLVersion()))
   err(sprintf(__('osTicket requires MySQL %s or later!'),$installer->getMySQLVersion()));
elseif(!db_select_database($vars['dbname']) && !db_create_database($vars['dbname'])) {
   err("Database doesn't exist");
} elseif(!db_select_database($vars['dbname'])) {
   err('Unable to select the database');
} else {
   $sql = 'SELECT * FROM `'.$vars['prefix'].'config` LIMIT 1';
   if(db_query($sql, false)) {
       $db_installed = true;
       echo "Database already installed\n";
   }
}

//Perform database installation if required
if (!$db_installed) {
  echo "Installing database. Please wait...\n";
  if (!$installer->install($vars)) {
    $errors=$installer->getErrors();
    echo "Database installation failed. Errors:\n";
    foreach($errors as $e) {
      echo "  $e\n";
    }
    exit(1);
  } else {
    echo "Database installation successful\n";
  }
}

?>