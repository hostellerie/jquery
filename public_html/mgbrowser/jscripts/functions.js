/* MediaGallery autotag browser for the Geeklog Forum editor. */
(function (window, document) {
    'use strict';

    function selectedValue(field) {
        var fields = field && typeof field.length === 'number' ? field : [field];
        var index;
        for (index = 0; index < fields.length; index += 1) {
            if (fields[index] && fields[index].checked) {
                return fields[index].value;
            }
        }
        return '';
    }

    function selectedMedia(form) {
        return selectedValue(form.thumbnail);
    }

    function positiveInteger(value, maximum) {
        var number = parseInt(value, 10);
        return isFinite(number) && number > 0 && number <= maximum ? number : 0;
    }

    function safeCaption(value) {
        return String(value || '').replace(/[\[\]\r\n]/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function addDimensions(tag, form) {
        var width = positiveInteger(form.width.value, 2000);
        var height = positiveInteger(form.height.value, 2000);
        if (width) {
            tag += ' width:' + width;
        }
        if (height) {
            tag += ' height:' + height;
        }
        return tag;
    }

    function addCommonOptions(tag, form, includeBorder, includeLink) {
        if (includeBorder) {
            tag += ' border:' + form.border.value;
        }
        tag += ' align:' + form.alignment.value;
        if (form.source.value !== 'tn') {
            tag += ' src:' + form.source.value;
        }
        if (includeLink && form.link.value !== '') {
            tag += ' link:' + form.link.value;
        }
        if (form.dest && form.dest.value === 'block') {
            tag += ' dest:block';
        }
        return tag;
    }

    function makeHtmlForInsertion(form) {
        var autotag = selectedValue(form.autotag);
        var albumId = positiveInteger(form.aid.value, 2147483647);
        var mediaId;
        var caption = safeCaption(form.caption.value);
        var tag;
        var delay;

        if (!autotag) {
            window.alert(lang.no_autotag);
            return false;
        }

        if (autotag === 'album' || autotag === 'slideshow' || autotag === 'fslideshow' || autotag === 'playall') {
            if (!albumId) {
                window.alert(lang.no_album);
                return false;
            }
            tag = '[' + autotag + ':' + albumId;
        } else {
            mediaId = selectedMedia(form);
            if (!mediaId) {
                window.alert(lang.no_media);
                return false;
            }
            tag = '[' + autotag + ':' + mediaId;
        }

        if (autotag === 'playall') {
            return tag + ' autoplay:' + form.autoplay.value + ' align:' + form.alignment.value + ']';
        }

        tag = addDimensions(tag, form);
        if (autotag === 'slideshow' || autotag === 'fslideshow') {
            delay = positiveInteger(form.delay.value, 999);
            if (delay) {
                tag += ' delay:' + delay;
            }
        }

        tag = addCommonOptions(tag, form, autotag !== 'img' && autotag !== 'mlink',
            autotag !== 'video' && autotag !== 'audio' && autotag !== 'mlink');

        if ((autotag === 'media' || autotag === 'img') && form.lightbox.value === '1') {
            tag = tag.replace(/ link:[01]/, '') + ' link:2';
        }
        if (autotag === 'video' || autotag === 'audio') {
            tag += ' autoplay:' + form.autoplay.value;
        }
        if (caption && autotag !== 'img' && autotag !== 'video' && autotag !== 'audio') {
            tag += ' ' + caption;
        }
        return tag + ']';
    }

    window.insertImage = function (form) {
        var autotag = makeHtmlForInsertion(form);
        if (autotag === false) {
            return false;
        }
        if (typeof window.InsertHtml === 'function') {
            window.InsertHtml(autotag);
            window.close();
        }
        return false;
    };

    window.dodisabled = function () {
        var form = document.forms.mediabrowser;
        var autotag;
        var mediaTag;
        if (!form) {
            return;
        }
        autotag = selectedValue(form.autotag);
        mediaTag = autotag === 'media' || autotag === 'img';
        form.autoplay.disabled = autotag !== 'video' && autotag !== 'audio' && autotag !== 'playall';
        form.border.disabled = autotag === 'img' || autotag === 'mlink' || autotag === 'fslideshow' || autotag === 'playall';
        form.alignment.disabled = autotag === 'mlink';
        form.source.disabled = autotag === 'mlink' || autotag === 'video' || autotag === 'audio' || autotag === 'playall';
        form.link.disabled = autotag === 'mlink' || autotag === 'video' || autotag === 'audio' || autotag === 'playall';
        form.caption.disabled = autotag === 'img' || autotag === 'video' || autotag === 'audio' || autotag === 'playall';
        form.delay.disabled = autotag !== 'slideshow' && autotag !== 'fslideshow';
        form.lightbox.disabled = !mediaTag;
    };
}(window, document));
