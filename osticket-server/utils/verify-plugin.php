<?php
error_reporting(E_ALL ^ E_DEPRECATED);

define('INCLUDE_DIR', 'include/');
require('include/class.plugin.php');

$phar = $argv[1];

$result = PluginManager::isVerified($phar);
if ($result == PluginManager::VERIFIED) {
  print($phar . " authenticity verified.\n");
} else {
  switch ($result) {
    case PluginManager::VERIFY_EXT_MISSING:
      $error = "PHP extension missing";
      break;
    case PluginManager::VERIFY_FAILED:
      $error = "Bad signature data";
      break;
    case PluginManager::VERIFY_ERROR:
      $error = "Unable to verify (unexpected error)";
      break;
    case PluginManager::VERIFY_NO_KEY:
      $error = "Public key missing";
      break;
    case PluginManager::VERIFY_DNS_PASS:
      $error = "DNS check passes, cannot verify sig";
      break;
    default:
      $error = "Unknown error " . $result;
  }
  print("Can't verify authenticity of " . $phar . ": " . $error);
  exit(1);
}
