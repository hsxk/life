=== Media from FTP ===
Contributors: Katsushi Kawamori
Donate link: https://shop.riverforest-wp.info/donate/
Tags: files, ftp, import, media, sync, uploads
Requires at least: 3.6.0
Requires PHP: 5.6
Tested up to: 5.3
Stable tag: 11.01
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Register to media library from files that have been uploaded by FTP.

== Description ==

= Register to media library from files that have been uploaded by FTP. =
* This create a thumbnail of the image file.
* This create a metadata(Images, Videos, Audios).
* Change the date/time.
* Work with [DateTimePicker](https://xdsoft.net/jqplugins/datetimepicker/). jQuery plugin select date/time.
* Export the log to a CSV file.
* To Import the files to Media Library from a WordPress export file.
* You can register a large number of files without timeout work with Ajax.

= Tutorial Video =
[youtube https://www.youtube.com/watch?v=vdxPvOFZaDk]

= Live Demo =
I prepared live demo including add-ons.
User Name: liveuser1
Password: live789user
[Live Demo](https://live1.riverforest-wp.info/wordpress/wp-login.php)

= Why I made this? =
* In the media uploader, you may not be able to upload by the environment of server. That's when the files are large. You do not mind the size of the file if FTP.

= Special Thanks! Translator =
* Deutsch [pixelverbieger](https://www.pixelverbieger.de/)
* Deutsch(Formal) [transl8or](https://profiles.wordpress.org/transl8or/)
* Español [apasionados](https://apasionados.es/)
* Français [Li-An](https://www.echodesplugins.li-an.fr/)
* Italian [ironicmoka](https://profiles.wordpress.org/photoironic/)
* Russian [khomenkov](https://awesomeprintstudio.com/)
* Persian[lusha](https://profiles.wordpress.org/lusha/)

== Installation ==

1. Upload `media-from-ftp` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= A server error is displayed because there are too many files. "Search & Register" screen does not appear. =
* Media from FTP Settings -> Other -> Limit number of search files
* Please reduce this number.

* Media from FTP Settings -> Other -> Execution time
* Please increasing the number of seconds.

* Media from FTP Search & Register -> Screen Options -> Search method of files
* Check of "Search files only for specified directories."

= Certain file types can not be searched. =
* If you want to add the mime type that can be used in the media library to each file type, Please use the <a href="https://wordpress.org/plugins/mime-types-plus/">Mime Types Plus</a>.

= I will not find a file with name like this: a-b-0x0.jpg. =
* Media from FTP Search & Register -> Screen Options -> Search method for the exclusion of the thumbnail
* Check of "Unusual selection. if you want to search for filename that contains such -0x0. It is low speed."

= Where is it better to upload files? =
* Upload directory is any of the following locations.
* Single-site wp-content/uploads
* Multisite wp-content/uploads/sites/*

= I want to register file for any folder. =
* Media from FTP Settings -> Register -> Date.
* Uncheck of "Organize my uploads into month- and year-based folders".

= I want to register file to "month- and year-based folders" without relevant to the timestamp of the file. =
* Media from FTP Settings -> Register -> Date.
* Uncheck of "Organize my uploads into month- and year-based folders".

= File at the time of registration is moved to another directory. =
* If checked "Organize my uploads into month- and year-based folders", it will move the file at the time of registration to year month-based folders. If you want to register in the same directory, Please uncheck.

= The original file is deleted. =
* The case of the following of this plugin to delete the file.
1. If it contains spaces in the file name. Convert to "-". And remove original file. image example.jpg -> image-example.jpg
2. If the file name is a multi-byte. It makes the MD5 conversion. And remove original file. image例.jpg -> 2edd9ad56212ce13a39f25b429b09012.jpg
3. If checked "Organize my uploads into month- and year-based folders", it copy the file to the "month- and year-based folder" and then delete the original file. wp-content/uploads/sites/2/image-example.jpg -> wp-content/uploads/sites/2/2015/09/image-example.jpg
* Thumbnail creation, database registration, do in file after copy.

= The original file is deleted, it will be the one that has been added to eight characters to the end of the file. =
* When find the same file name in the media library in order to avoid duplication of the file, adds the date and time, minute, and second at the time it was registered in the end.
* image-example.jpg -> image-example03193845.jpg
* Meaning 03193845 -> 3rd 19h38m45s

= 'Fatal error: Maximum execution time of ** seconds exceeded.' get an error message. =
* Media from FTP Settings -> Other -> Execution time
* Please increasing the number of seconds.

= I want to change the date at the time of registration. =
* Media from FTP Settings -> Register -> Date -> Get the date/time of the file, and updated based on it. Change it if necessary.
* Please checked.

= I want to register at the date of the Exif information. =
* Media from FTP Settings -> Register -> Date -> Get the date/time of the file, and updated based on it. Change it if necessary.Get by priority if there is date and time of the Exif information. 
* Please checked.

= I would like to hide the files do not need to search & registration screen. =
* Media from FTP Search & Register -> Screen Options -> Exclude file
* Please enter the exclusion file. It can be a regular expression.

= I want to turn off the creation of additional images such as thumbnail. =
* It conforms to the WordPress settings.
* Settings-> Media
* Please change the six values to all zeros. 
* Please comment out the 'set_post_thumbnail_size' or 'add_image_size' of theme's functions.php.

== Screenshots ==

1. Search file display
2. Settings Screen Options
3. Settings Register
4. Settings Other
5. Registration file selection
6. File registration result
7. Log screen
8. Import File Load
9. Import result

== Changelog ==

= 11.01 =
Supported WordPress 5.3.

= 11.00 =
Conformed to the WordPress coding standard.
The code at the time of registration was reviewed and the speed has improved.

= 10.10 =
Fixed problem of getting user ID.

= 10.09 =
Fixed problem of getting user ID.

= 10.08 =
Fixed problem of getting user ID.

= 10.07 =
Fixed problem of uppercase file suffix.
Fixed the problem of obtaining the shooting date and time of Exif.
Changed add-ons link.

== Upgrade Notice ==

= 9.90 =
Security measures.

= 9.88 =
Security measures.

= 9.87 =
Security measures.

= 9.86 =
Fixed problem of Directory-Traversal. Thanks[Plugin Vulnerabilities](https://www.pluginvulnerabilities.com/).

= 9.85 =
Fixed problem of Directory-Traversal. Thanks[wpl0v3r](https://wordpress.org/support/users/wpl0v3r/).

= 9.80 =
Fixed [PHP Object Injection Vulnerability](https://www.pluginvulnerabilities.com/2017/09/13/authenticated-php-object-injection-vulnerability-in-media-from-ftp/). Please do not use the previous version.

