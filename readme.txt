=== Deployment info for Freemius ===
Contributors: koen12344
Donate link: https://tycoonmedia.net
Tags: freemius, deployment, plugin, info
Requires at least: 4.5
Tested up to: 6.2.2
Requires PHP: 7.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Increase customer confidence by displaying info about your most recent plugin update

== Description ==

Increase customer confidence by showing your plugin is being actively maintained. With **Deployment info for Freemius**
you can display info about your latest [Freemius](https://freemius.com) plugin release on your website.

![sample](assets/sample.png)

**Deployment info for Freemius** adds the `[difs_plugin_info]` shortcode that can be used anywhere on your
website, and will display the latest version, release date, WordPress version and amount of installs of your Freemius
powered plugin.

[Demo](https://tycoonmedia.net/)

= Features =
* Easy to style with built-in CSS classes
* Automatically updated through Freemius webhooks
* Generates schema.org JSON-LD structured data of the SoftwareApplication type (can be turned off)
* Plugin data is cached for maximum performance

Feel free to fork to suit your needs, or make pull requests with improvements, making sure base functionality is
maintained without having to make modifications to the shortcode.

*Inspired by [Iconic](https://iconicwp.com).*

== Installation ==

Here's how to install and use **Deployment info for Freemius**.

1. Download [the latest release](https://github.com/koen12344/deployment-info-for-fs/releases/latest)
1. Upload the content of the .zip file to your wp-content/plugins folder or upload the zip file through your WordPress
dashboard.
1. Activate the plugin
1. Navigate to Settings > Deployment info for FS
1. Enter your Freemius details (can be found on the [My Profile](https://dashboard.freemius.com/#!/profile/) page)
1. Copy the webhook URL and add a new webhook to each plugin that you want to use with the plugin in your
[Freemius dashboard](https://dashboard.freemius.com) (Integrations > Custom Webhooks).
1. Select only the `plugin.version.released` event
1. Add the `[difs_plugin_info plugin_id="12345"]` shortcode wherever you want to display the release info. Obviously
replace the 12345 with your Freemius plugin ID.

= Shortcode Attributes =

The shortcode supports the following attributes:

* `plugin_id` - *required* - The ID of your Freemius plugin (found in Settings > Keys)
* `show_installs` - Whether or not to show the amount of installs. Set to `1` or `0`, default `1`
* `structured_data` - Whether or not the plugin should generate JSON-LD structured data. Set to `1` or `0`, default `1`

With all attributes: `[difs_plugin_info plugin_id="12345" show_installs="1" structured_data="1"]`

= CSS Classes =

**Deployment info for Freemius** comes without any CSS but can be styled through the built-in CSS classes:

* `difs-container` - Parent container
* `difs-divider` - The divider between the different attributes ( | )
* `difs-item` - Each item is encapsulated by this class, and individual classes (`difs-lastupdate`, `difs-released`,
`difs-wordpress`, `difs-installs`)

== Frequently Asked Questions ==

= Can I turn the amount of installs off? =

If your plugin has a low amount of installs that may not actually work in your favor. You can choose not to display them
by adding the `show_installs="0"` attribute to the shortcode.

== Changelog ==

= 0.1.0 =
* Initial release
