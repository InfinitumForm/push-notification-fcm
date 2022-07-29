=== Push Notification FCM ===
Contributors: ivijanstefan, creativform
Tags: firebase, push notification, fcm, android, ios, cloud messaging, iphone, rest
Requires at least: 5.0
Tested up to: 6.0
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.buymeacoffee.com/ivijanstefan

Firebase Cloud Messaging (FCM) to iOS and Android when content is published or updated.

== Description ==

A very simple plugin for the Firebase Cloud Messaging (FCM) system that enables simple push notifications to all Android and iOS devices around the world.

= Easy integration and use =

Simply, you install the plugin, enter your Firebase Server (API) key in the plugin settings, generate a site key and choose the types of posts for which you want to push a notification.

Then you need to insert the generated REST endpoints of your site in your mobile application. 

These endpoints are used to register the device with the site when the application is launched and deregister the device when the user deletes the application.

After device registration, every time a new article or page is published in the selected post types, all registered devices are notified and a notification appears.

= REST API Endpoints =

In order to be able to send push notifications, you need to record the device ID and device token in the site's database. Therefore, you have 2 REST endpoints to subscribe the device when the application is installed or launched, and unsubscribe the device during app deletion.

**Subscribe device:**

`https://example.domain/wp-json/fcm/pn/subscribe`

**Parameters:**

* `rest_api_key` (required) - Unique generated site key provided by the plugin
* `device_uuid` (required)
* `device_token` (required)
* `subscription` (required) - This would be the some category name in which the device is registered, if there is no category exists in WordPress it’ll be created automatically.
* `device_name` (optional)
* `os_version` (optional)

**Returns JSON:**
`
{
	"error": false,
	"message": "Device token registered",
	"subscription_id": 123
}
`

**Unsubscribe device:**

`https://example.domain/wp-json/fcm/pn/unsubscribe`

**Parameters:**

* `rest_api_key` (required) - Unique generated site key provided by the plugin
* `device_uuid` (required)

**Returns JSON:**
`
{
	"error": false,
	"message": "The device token was successfully removed"
}
`

== Changelog ==

= 1.0.0 =
* First stabile version