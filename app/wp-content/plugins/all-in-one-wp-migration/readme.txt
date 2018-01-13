=== All-in-One WP Migration ===
Contributors: yani.iliev, bangelov, pimjitsawang
Tags: move, transfer, copy, migrate, backup, clone, restore, db migration, wordpress migration, website migration, database export, database import, apoyo, sauvegarde, di riserva, バックアップ
Requires at least: 3.3
Tested up to: 4.9
Stable tag: 6.61
License: GPLv2 or later

Move, transfer, copy, migrate, and backup a site with 1-click. Quick, easy, and reliable.

== Description ==
This plugin exports your WordPress website including the database, media files, plugins and themes with no technical knowledge required.
Upload your site to a different location with a drag and drop in to WordPress.
There is an option to apply an unlimited number of find and replace operations on your database during the export process. The plugin will also fix any
serialisation problems that occur during the find/replace operation.

Mobile device compatible: All in One WP Plugin is the first plugin to offer true mobile experience on WordPress versions 3.3 and up.

= No limitations on host or operating system =
* We have tested the plugin on the major Linux distributions, MacOS and Microsoft Windows.
* [Please see the list of hosting providers that we work with.](https://help.servmask.com/knowledgebase/supported-hosting-providers/)

= Bypass all upload size restriction =
* We use chunks to import your site data. Most providers set the maximum upload file size to 2MB. As the file restrictions are only applied to each chunk, webserver upload size restrictions are bypassed by keeping the chunks under 2MB to easily upload your entire site.

= Zero Dependencies =
* The plugin does not require any PHP extensions and works with all versions of PHP from v5.2 onwards. This is great news for v5.2 users who are unsupported by many other products.

= Support for MySQL and MySQLi =
* No matter what php mysql driver your webserver ships with, we support it.

= Compatible with WordPress v3.3 to present =
* We have a comprehensive Quality Assurance and testing process that ensures that the plugin is always compatible with the latest release of WordPress, but we don't support versions of WordPress prior to version 3.3 (2012)

= WP-CLI Integration is included =
* [WP-CLI Integration Documentation](https://help.servmask.com/knowledgebase/cli-integration/)

= Support =
* For the community version of the plugin please watch the instruction videos below and see our FAQ.
* If you have more complex requirements, our team is here to help. If you have any questions please feel free to get in touch at [help.servmask.com](https://help.servmask.com/)
* All premium products include premium support.

= Migrate WordPress to cloud storage services using our completely new premium extensions =
**All of the Cloud Storage and Multisite extensions include premium support and the Unlimited extension free of charge**

* [Unlimited](https://servmask.com/products/unlimited-extension)
* [Dropbox](https://servmask.com/products/dropbox-extension)
* [Multisite](https://servmask.com/products/multisite-extension)
* [FTP](https://servmask.com/products/ftp-extension)
* [Google Drive](https://servmask.com/products/google-drive-extension)
* [Amazon S3](https://servmask.com/products/amazon-s3-extension)
* [URL](https://servmask.com/products/url-extension)
* [OneDrive](https://servmask.com/products/onedrive-extension)
* [Box](https://servmask.com/products/box-extension)
* [Mega](https://servmask.com/products/mega-extension)

= Supported hosting providers =
**The plugin does not have any dependancies, making it compatible with all PHP hosting providers. We support a vast range of hosting providers. Some of the most popular include:**

* Bluehost
* InMotion
* Web Hosting Hub
* Siteground
* Pagely
* Dreamhost
* Justhost
* GoDaddy
* WP Engine
* Site5
* 1&1
* Pantheon
* [See the full list of supported providers here](https://help.servmask.com/knowledgebase/supported-hosting-providers/)

= Contact us =
* [Get free help from us here](https://servmask.com/help)
* [Report a bug or request a feature](https://servmask.com/help)
* [Find out more about us](https://servmask.com)

[youtube http://www.youtube.com/watch?v=BpWxCeUWBOk]

[youtube http://www.youtube.com/watch?v=mRp7qTFYKgs]

== Installation ==
1. All-in-One WP Migration can be installed directly through your WordPress
Plugins dashboard.
1. Click "Add New" and Search for "All-in-One WP Migration"
1. Install and Activate

Alternatively you can download the plugin using the download button on this page and then upload the all-in-one-wp-migration folder to the /wp-content/plugins/ directory then activate throught the Plugins dashboard in WordPress

== Screenshots ==
1. Mobile Export page
2. Mobile Import page
3. Plugin Menu

== Changelog ==
= 6.61 =
**Added**

* Disable wp-force-ssl plugin if current site is not SSL based on import
* Support for Mega Extension

= 6.60 =
**Added**

* Tested up to WordPress 4.9

= 6.59 =
**Added**

* Disable wordpress-https plugin if current site is not SSL based on import
* Support for Azure db on import
* New button icons for cloud extensions

= 6.58 =
**Fixed**

* Remove WP CLI commands on PHP 5.2 and below
* Issue with files on export

= 6.57 =
**Added**

* Disable really-simple-ssl plugin if current site is not SSL based on import
* Support for WP-CLI

= 6.56 =
**Added**

* Symlink directories on export
* Support sub directories on Backups page
* A cancel button on import confirm step

**Fixed**

* Support IE11
* Wrong blogs.dir URL replacement
* Wrong path network drive replacement (Windows)
* Text placeholders of first find/replace inputs on export

**Changed**

* Added loading indicator to feedback and report a problem forms
* Do not clear cache on export
* Skip files that contain new line on export

= 6.55 =
**Added**

* Percentage indicator on "Unpacking archive" step
* Chunking mechanism when adding database.sql to wpress file on export

**Changed**

* Display 2GB+ value if file size cannot be obtained on Backups page
* Move COMMIT condition after processing all table records

**Fixed**

* Directory separator of archiver on Windows

= 6.54 =
**Changed**

* Use late row lookup to perform database export

= 6.53 =
**Added**

* Warn the user when web server configuration files are not created

**Changed**

* Buffered queries instead of unbuffered queries
* Relative URLs instead of absolute URLs when loading fonts and images

= 6.52 =
**Changed**

* Remove temporary files on error

**Fixed**

* Incorrect subsite path replacement on import

= 6.51 =
**Added**

* Validation on leave feedback, report issue and delete backup actions
* More descriptive wpress file names on export

**Changed**

* Remove "Unable to authenticate with secret key" message

**Fixed**

* Wrong file size in wpress file on export

= 6.50 =
**Fixed**

* Stuck on preparing to import

= 6.49 =
**Changed**

* Plugin description in readme.txt

= 6.48 =
**Fixed**

* Escape Find/Replace values on import
* Unable to load CSS and JS when event hook contains capital letters

= 6.47 =
**Added**

* Elementor plugin support

**Fixed**

* Site URL and Home URL replacement in JSON data

= 6.46 =
**Fixed**

* Domain replacement on import
* Invalid secret key check on import

= 6.45 =
**Changed**

* Better mechanism when enumerating files on import

**Fixed**

* Validation mechanism on export/import

= 6.44 =
**Added**

* PHP and DB version metadata in package.json
* Find/Replace values in package.json
* Internal Site URL and Internal Home URL in package.json
* Confirmation mechanism when uploading chunk by chunk on import
* Progress indicator on database export/import
* Shutdown handler to catch fatal errors

**Changed**

* Replace TYPE with ENGINE keyword on database export
* Detect Site URL and Home URL in Find/Replace values
* Activate template and stylesheet on import
* Import database chunk by chunk to avoid timeout limitation

**Fixed**

* An issue on export/import when using HipHop for PHP

= 6.43 =
**Changed**

* Plugin tags and description

= 6.42 =
**Changed**

* Improved performance when exporting database

= 6.41 =
**Added**

* Support Visual Composer plugin
* Support Jetpack Photon module

**Changed**

* Improved Maria DB support
* Disable WordPress authentication checking during migration
* Clean any temporary files after migration

= 6.40 =
**Added**

* Separate action hook in advanced settings called "ai1wm_export_advanced_settings" to allow custom checkbox options on export

**Changed**

* Do not extract dropins files on import
* Do not exclude active plugins in package.json and multisite.json on export
* Do not show "Resolving URL address..." on export/import

**Fixed**

* An issue with large files on import
* An issue with inactive plugins option in advanced settings on export

= 6.39 =
**Added**

* Support for MariaDB

**Changed**

* Do not include package.json, multisite.json, blogs.json, database.sql and filemap.list files on export
* Remove HTTP Basic authentication from Backups page

**Fixed**

* An issue with unpacking archive on import
* An issue with inactivated plugins on import

= 6.38 =
**Added**

* Support for HyperDB plugin
* Support for RevSlider plugin
* Check available disk space during export/import
* Support very restricted hosting environments
* WPRESS mime-type to web.config when the server is IIS

**Changed**

* Switch to AJAX from cURL on export/import
* Respect WordPress constants FS_CHMOD_DIR and FS_CHMOD_FILE on import
* Remove misleading available disk space information on "Backups" page

**Fixed**

* An issue related to generating archive and folder names
* An issue related to CSS styles on export page
