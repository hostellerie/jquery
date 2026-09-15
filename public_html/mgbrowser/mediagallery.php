<?php
// +---------------------------------------------------------------------------+
// | Media Gallery Plugin 1.7                                                  |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2012-2016 by the following authors:                         |
// |                                                                           |
// | Author:                                                                   |
// | Ben              - ben@geeklog.fr                                         |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2008 by the following authors:                              |
// |                                                                           |
// | Author:                                                                   |
// | Mark R. Evans              - mark@gllabs.org                              |
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

require_once '../../lib-common.php';

$mb_base_path = '/jquery/mgbrowser';

$langfile = $_CONF['path_html'] . $mb_base_path . '/langs/' . $_CONF['language'] . '.php';

if (file_exists($langfile)) {
    include_once $langfile;
} else {
    include_once $_CONF['path_html'] . $mb_base_path . '/langs/english.php';
}

$jslangfile = $_CONF['language'] . '.js';

if (!file_exists($_CONF['path_html'] . $mb_base_path . '/langs/' . $jslangfile)) {
    $jslangfile = 'english.js';
}

function MG_popupHeader($pagetitle = '') {

    global $_CONF, $LANG_CHARSET, $LANG_DIRECTION, $mb_base_path, $jslangfile;

    // send out the charset header
    if (empty($LANG_CHARSET)) {
        $charset = $_CONF['default_charset'];
        if (empty($charset)) {
            $charset = 'iso-8859-1';
        }
    } else {
        $charset = $LANG_CHARSET;
    }
    header ('Content-Type: text/html; charset=' . $charset);

    // If we reach here then either we have the default theme OR
    // the current theme only needs the default variable substitutions

    $header = new Template($_CONF['path_html'] . $mb_base_path . '/templates');
    $header->set_file('header', 'mb_header.thtml');
    $pagetitle .= empty($pagetitle) ? $_CONF['pagetitle'] : '';
    if (!empty($pagetitle) && !empty($_CONF['site_name'])) {
        $pagetitle = $pagetitle . ' - ' . $_CONF['site_name'];
    } else {
        $pagetitle .= $_CONF['site_name'];
    }
    $header->set_var('page_title',  htmlspecialchars($pagetitle, ENT_QUOTES, $charset));
    $header->set_var('site_url',    $_CONF['site_url']);
    $header->set_var('site_name',   htmlspecialchars($_CONF['site_name'], ENT_QUOTES, $charset));
    $header->set_var('css_url',     $_CONF['site_url'] . $mb_base_path . '/css/style.css');
    $header->set_var('js_lang_url', $_CONF['site_url'] . $mb_base_path . '/langs/' . $jslangfile);
    $header->set_var('js_url',      $_CONF['site_url'] . $mb_base_path . '/jscripts/functions.js');
    $header->set_var('charset',     $charset);
    $header->set_var('direction',   (empty($LANG_DIRECTION) ? 'ltr' : $LANG_DIRECTION));
    $header->parse('output', 'header');
    $retval = $header->finish($header->get_var('output'));

    return $retval;
}

function MG_popupFooter() {
    return '</body></html>';
}

if (!in_array('mediagallery', $_PLUGINS, true)) {
    // The plugin is disabled
    $display = MG_popupHeader();
    $display .= COM_startBlock('Plugin disabled');
    $display .= 'The Media Gallery plugin is currently disabled.';
    $display .= COM_endBlock();
    $display .= MG_popupFooter();
    echo $display;
    exit;
}

require_once $_CONF['path'] . 'plugins/mediagallery/include/common.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/classMedia.php';
require_once $_CONF['path'] . 'plugins/mediagallery/include/classAlbum.php';
include_once $_CONF['path_html'] . $mb_base_path . '/config.php';

if ($_USER['uid'] < 2 && !empty($_MG_CONF['loginrequired'])) {
    $display = MG_popupHeader();
    $display .= 'Site Configuration requires that you login before using this feature.';
    $display .= MG_popupFooter();
    echo $display;
    exit;
}

/*
* Main Function
*/

$album_id   = isset($_REQUEST['aid']) ? (int) COM_applyFilter($_REQUEST['aid'], true) : 0;
$page       = isset($_REQUEST['page']) ? (int) COM_applyFilter($_REQUEST['page'], true) : 1;
$instance   = isset($_REQUEST['i']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_REQUEST['i']) : '';
$navigation = isset($_POST['navigation']) ? COM_applyFilter($_POST['navigation']) : '';
if ($navigation === 'next') {
    $page++;
} elseif ($navigation === 'prev') {
    $page--;
}
$page = max(1, $page);

$root_album = new mgAlbum(0); // root album
$album      = new mgAlbum($album_id); // current album

if ($root_album->access == 0 || ($root_album->hidden == 1 && $root_album->access != 3)) {
    $display = COM_showMessageText($LANG_MG02['albumaccessdeny']);
    $display = MG_createHTMLDocument($display);
    COM_output($display);
    exit;
}     

if ($album_id > 0 && ( $album->access == 0 || ($album->hidden == 1 && $album->access !=3) )) {
    $display  = MG_popupHeader();
    $display .= COM_startBlock ($LANG_ACCESS['accessdenied'], '',COM_getBlockTemplate ('_msg_block', 'header'))
             . '<br>' . $LANG_MG00['no_access']
             . COM_endBlock (COM_getBlockTemplate ('_msg_block', 'footer'));
    $display .= MG_popupFooter();
    echo $display;
    exit;
}

$columns_per_page = 5;
$rows_per_page    = 2;
$media_per_page   = $columns_per_page * $rows_per_page;

// construct the album jumpbox...
$level = 0;
$album_jumpbox = $LANG_mgMB['select_album'] . ':&nbsp;<select name="aid" onchange="forms[\'mediabrowser\'].submit()">';
$album->buildJumpBox($album_jumpbox, $album_id, 1, -1);
$album_jumpbox .= '</select>' . LB;

$page = $page - 1;

$total_items_in_album = $album->media_count;

$total_pages = (int) ceil($total_items_in_album / $media_per_page);

if ($total_pages > 0 && $page >= $total_pages) {
    $page = 0;
}
if ($page < 0) {
    $page = max(0, $total_pages - 1);
}

$begin = $media_per_page * $page;
if ($begin < 0) $begin=0;
$end   = $media_per_page;

$album_jumpbox .= '<input type="hidden" name="page" value="' . ($page + 1) . '">&nbsp;' . LB;

if ($album_id == 0) {
    if (!empty($root_album->children)) {
        $children = $root_album->getChildren();
        foreach ($children as $child) {
           $albums[$child] = new mgAlbum($child);;
           if ($albums[$child]->access > 0) {
               $album_id = $albums[$child]->id;
			   $total_items_in_album = $albums[$child]->media_count;
               $album = $albums[$child];
               break;
           }
       }
   }
}

if (!isset($album->id) && $album_id != 0) {
    $display = MG_popupHeader();
    COM_errorLog("Media Gallery Error - User attempted to view an album that does not exist.");
    $display .= COM_startBlock ($LANG_mgMB['error_header'], '',COM_getBlockTemplate ('_admin_block', 'header'));
    $T = new Template($_CONF['path'] . 'plugins/mediagallery/templates');
    $T->set_file('error','error.thtml');
    $T->set_var('site_url', $_CONF['site_url']);
    $T->set_var('errormessage',$LANG_MG02['albumaccessdeny']);
    $T->parse('output', 'error');
    $display .= $T->finish($T->get_var('output'));
    $display .= COM_endBlock (COM_getBlockTemplate ('_admin_block', 'footer'));
    $display .= MG_popupFooter();
    echo $display;
    exit;
}

$total_media = 0;
$arrayCounter = 0;
$total_object_count = 0;
$mediaObject = array();

$MG_media = array();

$sortOrder = 0;
$orderBy = MG_getSortOrder($album_id, $sortOrder);

$sql = "SELECT * FROM {$_TABLES['mg_media_albums']} AS ma INNER JOIN " . $_TABLES['mg_media'] . " AS m " .
        " ON ma.media_id=m.media_id WHERE ma.album_id=" . $album_id . $orderBy . ' LIMIT ' . $begin . ',' . $end;

$result = DB_query($sql);
$nRows  = DB_numRows($result);
$mediaRows = 0;
if ($nRows > 0) {
    while ($row = DB_fetchArray($result)) {
        $media = new Media($row, $album_id);
        $MG_media[$arrayCounter] = $media;
        $arrayCounter++;
        $mediaRows++;
    }
}

$total_media = $total_media + $nRows;

$start = $page * $media_per_page;

$current_print_page = (floor($start / $media_per_page) + 1);
$total_print_pages  = ceil($total_items_in_album/($media_per_page));

if ($current_print_page == 0) {
    $current_print_page = 1;
}
if ($total_print_pages == 0) {
    $total_print_pages = 1;
}

$T = new Template($_CONF['path_html'] . $mb_base_path . '/templates');
$T->set_file (array(
    'page'      => 'mb.thtml',
    'body'      => 'mb_body.thtml',
));

$T->set_var ('real_site_url', $_CONF['site_url']);

$aOffset = $album->getOffset();
$aPage = 1;
if ($aOffset > 0) {
    $aPage = intval($aOffset / ($_MG_CONF['album_display_columns'] * $_MG_CONF['album_display_rows'])) + 1;
}

$prev_disabled = ($current_print_page <= 1) ? ' disabled' : '';
$next_disabled = ($current_print_page >= $total_print_pages) ? ' disabled' : '';

$birdseed = MG_getBirdseed($album_id, 0, $sortOrder, 0);

$refresh = (isset($_REQUEST['refresh']) ? COM_applyFilter($_REQUEST['refresh'],true) : 0);

if ($refresh != 1) {  // initial call
    $T->set_var(array(
        'border_yes'            => $_mgMB_CONF['at_border'] == 1 ? ' selected' : '',
        'border_no'             => $_mgMB_CONF['at_border'] == 1 ? '' : ' selected',
        'align_none'            => $_mgMB_CONF['at_align'] == 'none' ? ' selected' : '',
        'align_auto'            => $_mgMB_CONF['at_align'] == 'auto' ? ' selected' : '',
        'align_right'           => $_mgMB_CONF['at_align'] == 'right' ? ' selected' : '',
        'align_left'            => $_mgMB_CONF['at_align'] == 'left' ? ' selected' : '',
        'width'                 => $_mgMB_CONF['at_width'],
        'height'                => $_mgMB_CONF['at_height'],
        'delay'                 => $_mgMB_CONF['at_delay'],
        'src_tn'                => $_mgMB_CONF['at_src'] == 'tn' ? ' selected' : '',
        'src_disp'              => $_mgMB_CONF['at_src'] == 'disp' ? ' selected' : '',
        'src_orig'              => $_mgMB_CONF['at_src'] == 'orig' ? ' selected' : '',
        'autoplay_yes'          => $_mgMB_CONF['at_autoplay'] == 1 ? ' selected' : '',
        'autoplay_no'           => $_mgMB_CONF['at_autoplay'] == 1 ? '' : ' selected',
        'link_yes'              => $_mgMB_CONF['at_enable_link'] == 1 ? ' selected' : '',
        'link_no'               => $_mgMB_CONF['at_enable_link'] == 0 ? ' selected' : '',
        'lightbox_yes'          => $_mgMB_CONF['at_enable_link'] == 2 ? ' selected' : '',
        'lightbox_no'           => $_mgMB_CONF['at_enable_link'] != 2 ? ' selected' : '',
        'alturl_no'             => (isset($_mgMB_CONF['at_alt_url']) && $_mgMB_CONF['at_alt_url'] == 1) ? '' : ' selected',
        'alturl_yes'            => (isset($_mgMB_CONF['at_alt_url']) && $_mgMB_CONF['at_alt_url'] == 1) ? ' selected' : '',
        'mediaon'               => ' checked',
    ));
} else {
    $post = array(
        'border' => isset($_POST['border']) ? (int) $_POST['border'] : 0,
        'alignment' => isset($_POST['alignment']) ? COM_applyFilter($_POST['alignment']) : 'none',
        'width' => isset($_POST['width']) ? (int) $_POST['width'] : 0,
        'height' => isset($_POST['height']) ? (int) $_POST['height'] : 0,
        'delay' => isset($_POST['delay']) ? (int) $_POST['delay'] : (int) $_mgMB_CONF['at_delay'],
        'source' => isset($_POST['source']) ? COM_applyFilter($_POST['source']) : 'tn',
        'autoplay' => isset($_POST['autoplay']) ? (int) $_POST['autoplay'] : 0,
        'link' => isset($_POST['link']) ? (int) $_POST['link'] : 0,
        'lightbox' => isset($_POST['lightbox']) ? (int) $_POST['lightbox'] : 0,
        'alturl' => isset($_POST['alturl']) ? (int) $_POST['alturl'] : 0,
        'autotag' => isset($_POST['autotag']) ? COM_applyFilter($_POST['autotag']) : 'media',
        'caption' => isset($_POST['caption']) ? strip_tags($_POST['caption']) : ''
    );
    $T->set_var(array(
        'border_yes'            => $post['border'] === 1 ? ' selected' : '',
        'border_no'             => $post['border'] === 1 ? '' : ' selected',
        'align_none'            => $post['alignment'] === 'none' ? ' selected' : '',
        'align_auto'            => $post['alignment'] === 'auto' ? ' selected' : '',
        'align_right'           => $post['alignment'] === 'right' ? ' selected' : '',
        'align_left'            => $post['alignment'] === 'left' ? ' selected' : '',
        'width'                 => min(2000, max(0, $post['width'])),
        'height'                => min(2000, max(0, $post['height'])),
        'delay'                 => min(999, max(0, $post['delay'])),
        'src_tn'                => $post['source'] === 'tn' ? ' selected' : '',
        'src_disp'              => $post['source'] === 'disp' ? ' selected' : '',
        'src_orig'              => $post['source'] === 'orig' ? ' selected' : '',
        'autoplay_yes'          => $post['autoplay'] === 1 ? ' selected' : '',
        'autoplay_no'           => $post['autoplay'] === 1 ? '' : ' selected',
        'link_yes'              => $post['link'] === 1 ? ' selected' : '',
        'link_no'               => $post['link'] === 0 ? ' selected' : '',
        'lightbox_yes'          => $post['lightbox'] === 1 ? ' selected' : '',
        'lightbox_no'           => $post['lightbox'] === 0 ? ' selected' : '',
        'alturl_yes'            => $post['alturl'] === 1 ? ' selected' : '',
        'alturl_no'             => $post['alturl'] === 1 ? '' : ' selected',
        'albumon'               => $post['autotag'] === 'album' ? ' checked' : '',
        'slideshowon'           => $post['autotag'] === 'slideshow' ? ' checked' : '',
        'fslideshowon'          => $post['autotag'] === 'fslideshow' ? ' checked' : '',
        'mediaon'               => $post['autotag'] === 'media' ? ' checked' : '',
        'mlinkon'               => $post['autotag'] === 'mlink' ? ' checked' : '',
        'imgon'                 => $post['autotag'] === 'img' ? ' checked' : '',
        'videoon'               => $post['autotag'] === 'video' ? ' checked' : '',
        'audioon'               => $post['autotag'] === 'audio' ? ' checked' : '',
        'playallon'             => $post['autotag'] === 'playall' ? ' checked' : '',
        'caption'               => htmlspecialchars($post['caption'], ENT_QUOTES, 'UTF-8'),
    ));
}

$T->set_var(array(
    's_form_action'         => htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'),
    'site_url'              => $_MG_CONF['site_url'],
    'birdseed'              => $birdseed,
    'album_title'           => PLG_replaceTags($album->title),
    'table_columns'         => $columns_per_page,
    'table_column_width'    => intval(100 / $columns_per_page) . '%',
    'page_number'           => sprintf("%s %d %s %d",'', $current_print_page, '/', $total_print_pages),
    'prev_disabled'         => $prev_disabled,
    'next_disabled'         => $next_disabled,
    'current_page'          => $current_print_page,
    'total_pages'           => $total_print_pages,
    'jumpbox'               => $album_jumpbox,
    'album_id'              => $album_id,
    'instance'              => $instance,
    'lang_menulabel'        => $LANG_mgMB['menulabel'],
    'lang_select_album'     => $LANG_mgMB['select_album'],
    'lang_go'               => $LANG_mgMB['go'],
    'lang_error_header'     => $LANG_mgMB['error_header'],
    'lang_current_album'    => $LANG_mgMB['current_album'],
    'lang_autotag_attr'     => $LANG_mgMB['autotag_attr'],
    'lang_album'            => $LANG_mgMB['album'],
    'lang_playall'          => $LANG_mgMB['playall'],
    'lang_slideshow'        => $LANG_mgMB['slideshow'],
    'lang_fslideshow'       => $LANG_mgMB['fslideshow'],
    'lang_media'            => $LANG_mgMB['media'],
    'lang_mlink'            => $LANG_mgMB['mlink'],
    'lang_img'              => $LANG_mgMB['img'],
    'lang_video'            => $LANG_mgMB['video'],
    'lang_audio'            => $LANG_mgMB['audio'],
    'lang_width'            => $LANG_mgMB['width'],
    'lang_height'           => $LANG_mgMB['height'],
    'lang_delay'            => $LANG_mgMB['delay'],
    'lang_border'           => $LANG_mgMB['border'],
    'lang_alignment'        => $LANG_mgMB['alignment'],
    'lang_source'           => $LANG_mgMB['source'],
    'lang_link'             => $LANG_mgMB['link'],
    'lang_autoplay'         => $LANG_mgMB['autoplay'],
    'lang_caption'          => $LANG_mgMB['caption'],
    'lang_thumbnails'       => $LANG_mgMB['thumbnails'],
    'lang_navigation'       => $LANG_mgMB['navigation'],
    'lang_insert'           => $LANG_mgMB['insert'],
    'lang_cancel'           => $LANG_mgMB['cancel'],
    'lang_yes'              => $LANG_mgMB['yes'],
    'lang_no'               => $LANG_mgMB['no'],
    'lang_auto'             => $LANG_mgMB['auto'],
    'lang_none'             => $LANG_mgMB['none'],
    'lang_right'            => $LANG_mgMB['right'],
    'lang_left'             => $LANG_mgMB['left'],
    'lang_thumbnail'        => $LANG_mgMB['thumbnail'],
    'lang_display'          => $LANG_mgMB['display'],
    'lang_original'         => $LANG_mgMB['original'],
    'lang_alturl'           => $LANG_mgMB['alturl'],
    'lang_lightbox'         => $LANG_mgMB['lightbox'],
    'destination'           => ($_mgMB_CONF['enable_dest'] == 1 ? $LANG_mgMB['destination'] . '&nbsp;<select name="dest"><option value="story">' . $LANG_mgMB['story'] . '</option><option value="block">' . $LANG_mgMB['block'] . '</option></select>' : ''),
));

if ($total_media == 0) {
    $T->set_var('lang_no_image', $LANG_MG03['no_media_objects']);
    $T->parse('album_noimages', 'noitems');
}

if ($total_media > 0) {
    $k = 0;
    $T->set_block('body', 'ImageDetail', 'IDetail');
    $T->set_block('body', 'ImageColumn', 'IColumn');
    $T->set_block('body', 'ImageRow', 'IRow');
    for ($i = 0; $i < $media_per_page; $i += $columns_per_page) {
        $T->set_var('IDetail','');
        $T->set_var('IColumn','');
        for ($j = $i; $j < ($i + $columns_per_page); $j++) {
            if ($j >= $total_media) {
                $k = ($i+$columns_per_page) - $j;
                $m = $k % $columns_per_page;
                break;
            }
            $previous_image = $i - 1;
            if ($previous_image < 0) {
                $previous_image = -1;
            }
            $next_image = $i + 1;
            if ($next_image >= $total_media - 1) {
                $next_image = -1;
            }
            $z = ($j+$start);
            $title = '';
            if (!empty($MG_media[$j]->title)) {
                $title = '<p>' . htmlspecialchars(strip_tags($MG_media[$j]->title), ENT_QUOTES, 'UTF-8') . '</p>';
            }
            $celldisplay = '<div class="thumb">' . $MG_media[$j]->displayRawThumb() . '</div>'
                         . '<div class="description">' . COM_truncate($title, 20,'...')
                         . '</div><input type="radio" name="thumbnail" value="' . (int) $MG_media[$j]->id . '">';
            $T->set_var('CELL_DISPLAY_IMAGE', $celldisplay);
            $T->parse('IDetail', 'ImageDetail', true);
            $T->parse('IColumn', 'ImageColumn', true);
        }
        $T->parse('IRow', 'ImageRow', true);
    }
    $T->parse('album_body', 'body');
}
$T->parse('output', 'page');

ob_start();
echo MG_popupHeader(strip_tags($album->title));
echo $T->finish($T->get_var('output'));
echo MG_popupFooter();
$data = ob_get_contents();
ob_end_clean();
echo $data;
exit;
?>
