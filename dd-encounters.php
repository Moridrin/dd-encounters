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

require_once 'post-type/Encounter.php';
require_once 'general/general.php';
require_once 'models/Creature.php';
require_once 'models/CombatAction.php';
require_once 'models/Player.php';
require_once 'models/Monster.php';
require_once 'models/CombatMonster.php';
require_once 'DD_Encounters.php';
require_once 'Options.php';
require_once 'ajax.php';


$monsters = file_get_contents('https://dl.dropboxusercontent.com/s/iwz112i0bxp2n4a/5e-SRD-Monsters.json');
// $monsters = file_get_contents('https://dl.dropboxusercontent.com/s/121t7xstyyeofxw/5e-SRD-Spells.json');
$monsters = json_decode($monsters, true);
$tmp      = array_keys($monsters[0]);

foreach ($monsters as $monster) {
    if (array_keys($monster) != $tmp) {
        $tmp = array_unique(array_merge($tmp, array_keys($monster)));
    }
}
// \mp_general\base\BaseFunctions::var_export($tmp, true);
// $monsters = json_decode($monsters);
// \mp_general\base\BaseFunctions::var_export($monsters, true);
// \dd_encounters\DD_Encounters::CLEAN_INSTALL();