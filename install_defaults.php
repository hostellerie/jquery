<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | jquery plugin 1.1                                                         |
// +---------------------------------------------------------------------------+
// | install_defaults.php                                                      |
// |                                                                           |
// | Initial Installation Defaults used when loading the online configuration  |
// | records. These settings are only used during the initial installation     |
// | and not referenced any more once the plugin is installed.                 |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2008 by the following authors:                              |
// |                                                                           |
// | Authors: Dirk Haun        - dirk AT haun-online DOT de                    |
// +---------------------------------------------------------------------------+
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+
//

if (strpos(strtolower($_SERVER['PHP_SELF']), 'install_defaults.php') !== false) {
    die('This file can not be used on its own!');
}

/*
 * jquery default settings
 *
 * Initial Installation Defaults used when loading the online configuration
 * records. These settings are only used during the initial installation
 * and not referenced any more once the plugin is installed
 *
 */
 
// Note JQ is for jquery
 
global $_DB_table_prefix, $_JQ_DEFAULT;
$_JQ_DEFAULT = array();

$_JQ_DEFAULT['use_lightbox'] = 1;
$_JQ_DEFAULT['lightbox_width'] = 75;
$_JQ_DEFAULT['lightbox_height'] = 75;

/**
* Initialize jquery plugin configuration
*
* Creates the database entries for the configuation if they don't already
* exist. 
*
* @return   boolean     true: success; false: an error occurred
*
*/
function plugin_initconfig_jquery()
{
    global $_CONF, $_JQ_DEFAULT;

    $c = config::get_instance();
    if (!$c->group_exists('jquery')) {

        //This is main subgroup #0
		$c->add('sg_0', NULL, 'subgroup', 0, 0, NULL, 0, true, 'jquery');
		
		//This is fieldset #1  in subgroup #0   
		$c->add('fs_02', NULL, 'fieldset', 0, 1, NULL, 0, true, 'jquery');
        $c->add('use_lightbox', $_JQ_DEFAULT['use_lightbox'],
                'select', 0, 1, 3, 30, true, 'jquery');
        $c->add('lightbox_width', $_JQ_DEFAULT['lightbox_width'],
                'text', 0, 1, NULL, 40, true, 'jquery');
		$c->add('lightbox_height', $_JQ_DEFAULT['lightbox_height'],
                'text', 0, 1, NULL, 50, true, 'jquery');
		}
    return true;
}

?>
