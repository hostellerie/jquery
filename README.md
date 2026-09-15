# jQuery compatibility plugin for Geeklog

This maintenance release preserves the legacy `[lightbox:]` autotag and the
MediaGallery browser used by Forum 2.9.x. It does not ship or replace jQuery:
Geeklog already provides and manages that library.

## Supported environment

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through PHP 8.1

## Security and compatibility changes in 1.5.0

- replaces the obsolete jQuery Lightbox code with dependency-free JavaScript;
- removes TimThumb and all server-side fetching or resizing of images;
- validates and escapes lightbox autotag URLs, labels and dimensions;
- removes jQuery Migrate, Feedback, Datepicker and ColorPicker;
- keeps and hardens the MediaGallery browser for the Forum editor;
- removes obsolete settings during each site's explicit plugin upgrade.

## Lightbox autotag

```text
[lightbox: /images/example.jpg]
[lightbox: /images/example.jpg width:150 height:100]
[lightbox: /images/example.jpg Open the full image]
```

HTTP and HTTPS image URLs are supported. Invalid schemes are rejected. Without
JavaScript, the generated link still opens the original image.

## Issues

Repository and documentation:

https://github.com/Geeklog-Plugins/jquery

Bug reports and compatibility issues:

https://github.com/Geeklog-Plugins/jquery/issues
