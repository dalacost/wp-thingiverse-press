thingverse-press
===============================

thingiverse-press is a fork from original [thingiverse-embed](https://github.com/martymcguire/wp-thingiverse-embed) wordpress plugin.
tested at wordress 6.2 and support for PHP 8


This plugin let you embed Thingiverse Things into your posts and pages, and embed a Thingiverse Stream widget into your
sidebars.

To install:

1. Clone this repository to thingiverse-press/
2. Copy the thingiverse-press/ directory to your Wordpress' plugins directory.
3. Activate the plugin
4. Embed some [thingiverse] shortcodes and Thingiverse Stream widgets!


## Thing Embedding

For example, to embed [thing:3678135](http://www.thingiverse.com/thing:3678135) enter this in a post or page:

  [thingiverse thing=3678135]

## Stream Embedding

The Thingiverse Stream widget allows you to embed Thingiverse streams into your sidebars.  To use it, simply drag-and-drop the Thingiverse Stream widget to a sidebar and configure it.

There are two types of streams: *Global* and *User*.  *User* streams require you to specify a Thingiverse username.

*User Streams*

- `designed` - content from http://www.thingiverse.com/< User >/designs
- `like` - content from http://www.thingiverse.com/< User >/favorites
- `made` - content from http://www.thingiverse.com/< User >/makes

*Global Streams*

- `featured` content from http://www.thingiverse.com/featured
- `newest` content from http://www.thingiverse.com/newest
- `popular` content from http://www.thingiverse.com/popular
- `derivatives` content from http://www.thingiverse.com/derivatives
- `made-things` content from http://www.thingiverse.com/made-things

## Custom Formatting

Once installed, you can customize the look of your Things on the following files:

- `styles.css` - CSS for both Streams and individual Things.
- `thingiverse-stream-widget.php` - The `widget` method renders the stream.
- `templates/thing.php` - Template for [thingiverse] shortcode embeds.




