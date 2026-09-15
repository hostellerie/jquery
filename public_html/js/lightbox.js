(function () {
    'use strict';

    var overlay;
    var image;
    var closeButton;
    var previousFocus;

    function closeLightbox() {
        if (!overlay || overlay.hidden) {
            return;
        }
        overlay.hidden = true;
        image.removeAttribute('src');
        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
    }

    function createLightbox() {
        overlay = document.createElement('div');
        overlay.className = 'jquery-lightbox-overlay';
        overlay.hidden = true;
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');

        image = document.createElement('img');
        image.className = 'jquery-lightbox-overlay__image';
        image.alt = '';

        closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'jquery-lightbox-overlay__close';
        closeButton.setAttribute('aria-label', 'Close');
        closeButton.appendChild(document.createTextNode('\u00d7'));
        closeButton.addEventListener('click', closeLightbox);

        overlay.appendChild(image);
        overlay.appendChild(closeButton);
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                closeLightbox();
            }
        });
        document.body.appendChild(overlay);
    }

    document.addEventListener('click', function (event) {
        var link = event.target;
        while (link && link.nodeType === 1 && !link.classList.contains('jquery-lightbox')) {
            link = link.parentNode;
        }
        if (!link || link.nodeType !== 1) {
            return;
        }
        event.preventDefault();
        if (!overlay) {
            createLightbox();
        }
        previousFocus = link;
        image.src = link.href;
        image.alt = link.getAttribute('aria-label') || '';
        overlay.hidden = false;
        closeButton.focus();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeLightbox();
        }
    });
}());
