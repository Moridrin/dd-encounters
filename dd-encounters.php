<?php
/**
 * Plugin Name: D&D Encounters
 * Plugin URI: http://moridrin.com/dd-encounters
 * Description: This is a plugin to manage campaigns with ease.
 * Version: 0.0.1
 * Author: Jeroen Berkvens
 * Author URI: http://nl.linkedin.com/in/jberkvens/
 * License: WTFPL
 * License URI: http://www.wtfpl.net/txt/copying/
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DD_ENCOUNTERS_PATH', plugin_dir_path(__FILE__));
define('DD_ENCOUNTERS_URL', plugins_url() . '/' . plugin_basename(__DIR__));

require_once 'post-type/Encounter/Admin.php';
require_once 'post-type/Encounter/Frontend.php';
require_once 'general/general.php';
require_once 'models/Creature.php';
require_once 'models/CombatAction.php';
require_once 'models/Player.php';
require_once 'models/Monster.php';
require_once 'models/CombatMonster.php';
require_once 'DD_Encounters.php';
require_once 'Options.php';
require_once 'Ajax.php';

// \dd_encounters\DD_Encounters::CLEAN_INSTALL();
