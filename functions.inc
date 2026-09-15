<?php

/* Reminder: always indent with 4 spaces (no tabs). */

/**
 * Compatibility plugin for legacy [lightbox:] autotags and the MediaGallery
 * browser used by the Forum editor.
 *
 * Copyright (C) 2010-2026 ::Ben
 * Distributed under the GNU General Public License, version 2 or later.
 *
 * @package jQuery
 */

if (strpos(strtolower($_SERVER['PHP_SELF']), 'functions.inc') !== false) {
    die('This file can not be used on its own.');
}

$plugin_path = $_CONF['path'] . 'plugins/jquery/';
$langfile = $plugin_path . 'language/' . $_CONF['language'] . '.php';
if (file_exists($langfile)) {
    require_once $langfile;
} else {
    require_once $plugin_path . 'language/english.php';
}

require_once $_CONF['path_system'] . 'classes/config.class.php';
$c = config::get_instance();
$_JQ_CONF = $c->get_config('jquery');

/**
 * Adds the MediaGallery browser button only where Forum 2.9.x provides the
 * expected editor field. Geeklog remains responsible for loading jQuery.
 */
function plugin_getheadercode_jquery()
{
    global $_CONF, $_PLUGINS, $_SCRIPTS, $LANG_JQUERY_1;

    if (!in_array('mediagallery', $_PLUGINS, true)
        || !in_array('forum', $_PLUGINS, true)
        || stripos(COM_getCurrentURL(), '/forum/createtopic.php') === false
    ) {
        return;
    }

    $_SCRIPTS->setJavaScriptLibrary('jquery');
    $browserUrl = $_CONF['site_url'] . '/jquery/mgbrowser/mediagallery.php';
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    $browserUrlJson = json_encode($browserUrl, $jsonFlags);
    $labelJson = json_encode($LANG_JQUERY_1['add_mg_tag'], $jsonFlags);
    $script = 'jQuery(function ($) {'
        . 'var field=$("#form-forum-text");'
        . 'if(!field.length||$("#mg_browser").length){return;}'
        . 'var link=$("<a>",{id:"mg_browser",href:' . $browserUrlJson . ',text:' . $labelJson . '});'
        . 'link.on("click",function(event){event.preventDefault();'
        . 'window.open(this.href,"mediagallery","width=760,height=720,resizable=yes,scrollbars=yes");});'
        . 'field.after($("<p>").append(link));'
        . '});';
    $_SCRIPTS->setJavaScript($script, true);
}

function JQUERY_loadLightboxResources()
{
    global $_SCRIPTS;

    static $loaded = false;
    if (!$loaded) {
        $_SCRIPTS->setJavaScriptFile('jquery_legacy_lightbox', '/jquery/js/lightbox.js');
        $_SCRIPTS->setCSSFile('jquery_legacy_lightbox', '/jquery/css/lightbox.css', true);
        $loaded = true;
    }
}

function JQUERY_validateImageUrl($url)
{
    $url = trim((string) $url);
    if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url)) {
        return '';
    }
    if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
        return $url;
    }
    $parts = parse_url($url);
    if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
        return '';
    }
    $scheme = strtolower($parts['scheme']);
    return ($scheme === 'http' || $scheme === 'https') ? $url : '';
}

function JQUERY_normalizeDimension($value, $fallback)
{
    $value = strtolower(trim((string) $value));
    if ($value === 'auto') {
        return 'auto';
    }
    if (ctype_digit($value)) {
        $number = (int) $value;
        if ($number >= 1 && $number <= 2000) {
            return (string) $number;
        }
    }
    return (string) $fallback;
}

function plugin_autotags_jquery($op, $content = '', $autotag = '')
{
    global $_JQ_CONF, $LANG_JQUERY_1;

    if ($op === 'tagname') {
        return 'lightbox';
    }
    if ($op === 'description') {
        return array('lightbox' => $LANG_JQUERY_1['autotag_desc_lightbox']);
    }
    if ($op !== 'parse' || !is_array($autotag) || !isset($autotag['tagstr'])) {
        return $content;
    }
    if (isset($_JQ_CONF['use_lightbox']) && empty($_JQ_CONF['use_lightbox'])) {
        return str_replace($autotag['tagstr'], '', $content);
    }

    $url = JQUERY_validateImageUrl(isset($autotag['parm1']) ? $autotag['parm1'] : '');
    if ($url === '') {
        return str_replace($autotag['tagstr'], '', $content);
    }

    $defaultWidth = isset($_JQ_CONF['lightbox_width'])
        ? JQUERY_normalizeDimension($_JQ_CONF['lightbox_width'], 75) : '75';
    $defaultHeight = isset($_JQ_CONF['lightbox_height'])
        ? JQUERY_normalizeDimension($_JQ_CONF['lightbox_height'], 75) : '75';
    $parameters = isset($autotag['parm2']) ? trim($autotag['parm2']) : '';
    $width = $defaultWidth;
    $height = $defaultHeight;

    if (preg_match('/(?:^|\s)width:(auto|\d{1,5})(?=\s|$)/i', $parameters, $match)) {
        $width = JQUERY_normalizeDimension($match[1], 75);
    }
    if (preg_match('/(?:^|\s)height:(auto|\d{1,5})(?=\s|$)/i', $parameters, $match)) {
        $height = JQUERY_normalizeDimension($match[1], 75);
    }
    $title = preg_replace('/(?:^|\s)(?:width|height):(?:auto|\d{1,5})(?=\s|$)/i', ' ', $parameters);
    $title = trim(preg_replace('/\s+/', ' ', $title));

    JQUERY_loadLightboxResources();
    $escapedUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $ariaLabel = $title !== '' ? $title : $LANG_JQUERY_1['open_image'];
    $attributes = ' class="jquery-lightbox" href="' . $escapedUrl . '"'
        . ' data-lightbox="legacy-content" aria-label="'
        . htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8') . '"';

    if ($title !== '') {
        $replacement = '<a' . $attributes . '>' . $escapedTitle . '</a>';
    } else {
        $style = ($width !== 'auto' ? 'width:' . $width . 'px;' : '')
            . ($height !== 'auto' ? 'height:' . $height . 'px;' : '');
        $replacement = '<a' . $attributes . '><img class="jquery-lightbox__thumbnail"'
            . ' src="' . $escapedUrl . '" alt="" loading="lazy" decoding="async"'
            . ($style !== '' ? ' style="' . $style . '"' : '') . '></a>';
    }

    return str_replace($autotag['tagstr'], $replacement, $content);
}

function plugin_cclabel_jquery()
{
    global $_CONF, $LANG_JQUERY_1;
    if (!SEC_hasRights('jquery.admin')) {
        return array();
    }
    return array($LANG_JQUERY_1['plugin_name'], $_CONF['site_admin_url'] . '/plugins/jquery/index.php', plugin_geticon_jquery());
}

function plugin_getadminoption_jquery()
{
    global $_CONF, $LANG_JQUERY_1;
    if (SEC_hasRights('jquery.admin')) {
        return array($LANG_JQUERY_1['plugin_name'], $_CONF['site_admin_url'] . '/plugins/jquery/index.php', 0);
    }
}

function plugin_geticon_jquery()
{
    global $_CONF;
    return $_CONF['site_admin_url'] . '/plugins/jquery/images/jquery.png';
}

function plugin_chkVersion_jquery()
{
    global $_CONF;
    require_once $_CONF['path'] . 'plugins/jquery/autoinstall.php';
    $parameters = plugin_autoinstall_jquery('jquery');
    return $parameters['info']['pi_version'];
}

function plugin_getdocumentationurl_jquery()
{
    return 'https://github.com/Geeklog-Plugins/jquery';
}

function plugin_upgrade_jquery()
{
    global $_CONF, $_TABLES;

    $installedVersion = DB_getItem($_TABLES['plugins'], 'pi_version', "pi_name = 'jquery'");
    $codeVersion = plugin_chkVersion_jquery();
    if ($installedVersion === $codeVersion) {
        return true;
    }

    require_once $_CONF['path'] . 'plugins/jquery/install_defaults.php';
    require_once $_CONF['path'] . 'plugins/jquery/autoinstall.php';
    if (!plugin_compatible_with_this_version_jquery('jquery')) {
        return 3002;
    }

    $configuration = config::get_instance();
    if (!$configuration->group_exists('jquery') && !plugin_initconfig_jquery()) {
        return false;
    }
    $current = $configuration->get_config('jquery');
    $useLightbox = isset($current['use_lightbox']) ? (int) $current['use_lightbox'] : 1;
    $lightboxWidth = isset($current['lightbox_width'])
        ? JQUERY_normalizeDimension($current['lightbox_width'], 75) : '75';
    $lightboxHeight = isset($current['lightbox_height'])
        ? JQUERY_normalizeDimension($current['lightbox_height'], 75) : '75';
    foreach (array('use_CND', 'CND_url', 'use_datepicker', 'use_colorpicker',
        'use_feedback', 'use_codehighlighter', 'use_ckeditor', 'fs_01', 'fs_03',
        'use_lightbox', 'lightbox_width', 'lightbox_height', 'fs_02') as $name) {
        $configuration->del($name, 'jquery');
    }
    // Recreate retained settings to repair legacy configuration metadata while
    // preserving this site's values in shared-files multisite installations.
    $configuration->add('fs_02', null, 'fieldset', 0, 1, null, 0, true, 'jquery');
    $configuration->add('use_lightbox', $useLightbox, 'select', 0, 1, 3, 30, true, 'jquery');
    $configuration->add('lightbox_width', $lightboxWidth, 'text', 0, 1, null, 40, true, 'jquery');
    $configuration->add('lightbox_height', $lightboxHeight, 'text', 0, 1, null, 50, true, 'jquery');

    $parameters = plugin_autoinstall_jquery('jquery');
    $safeVersion = DB_escapeString($codeVersion);
    $safeGlVersion = DB_escapeString($parameters['info']['pi_gl_version']);
    DB_query("UPDATE {$_TABLES['plugins']} SET pi_version = '{$safeVersion}', "
        . "pi_gl_version = '{$safeGlVersion}' WHERE pi_name = 'jquery'");
    if (DB_error()) {
        return false;
    }
    COM_errorLog("Updated jQuery plugin from v{$installedVersion} to v{$codeVersion}", 1);
    return true;
}

function plugin_autouninstall_jquery()
{
    return array(
        'tables' => array(),
        'groups' => array('jQuery Admin'),
        'features' => array('jquery.admin'),
        'php_blocks' => array(),
        'vars' => array()
    );
}

?>
