Nova Personal Log Layout Change
===============================
Developer: Dustin Lennon<br />
Email: <demonicpagan@gmail.com>

This application is developed under the licenses of Nova and CodeIgniter.

Install Instructions
--------------------
The following application will allow you to display and use an alternate layout to writing personal logs. To install
this application you need to perform the following steps.

1. Use the included nova_personal_log_alt.sql file to add the needed sql table fields to your nova installation.

2. Upload application/controllers/manage.php to your application/controllers folder of your Nova install 
replacing the existing one if you haven't already modified this file. If you already have changes in this file, 
it's best that you just take the contents of this file and add it into your existing manage.php file.

3. Upload application/controllers/sim.php to your application/controllers folder of your Nova install 
replacing the existing one if you haven't already modified this file. If you already have changes in this file, 
it's best that you just take the contents of this file and add it into your existing sim.php file.

4. *Upload application/controllers/write.php to your application/controllers folder of your Nova install 
replacing the existing one if you haven't already modified this file. If you already have changes in this file, 
it's best that you just take the contents of this file and add it into your existing write.php file.

	*NOTE: This file includes the _email() function. Be aware that overwriting this function with one you already have
	in your existing write.php file will cause you to lose any other MOD alterations you may have already installed.

5. Add the following line into your app_lang.php for your associated language(s) after the rest of the includes 
and before the Global items.

	`/* include Alternate Personal Log Language file */`<br />
	`include_once APPPATH .'language/'. $language . '/ucip_lang.php';`

6. Upload application/language/english/ucip_lang.php to your 
application/views/language/english folder of your Nova install. Translate this page into other languages and upload
them to the appropriate language directories. (If you would like your language included into a future release, 
please contact me via email.)

7. Upload application/views/_base_override/admin/pages/manage_logs_edit.php to your
application/views/_base_override/admin/pages folder of your Nova install.

8. Upload application/views/_base_override/admin/pages/write_personallog.php to your
application/views/_base_override/admin/pages folder of your Nova install.

9. Upload application/views/_base_override/main/pages/sim_viewlog.php to your
application/views/_base_override/main/pages folder of your Nova install.

If you experience any issues please submit a bug report on
<http://github.com/demonicpagan/Nova-Personal-Log-Change/issues>.

You can always get the latest trunk from <http://github.com/demonicpagan/Nova-Personal-Log-Change>
as well.

Changelog - Dates are in Epoch time
-----------------------------------
1410004755:

*	Updated files to compatible with Nova 2.3.2

1347264535:

*	Updated write.php to be compatible with Nova 2.1.0
*	Updated manage_logs_edit.php to use code under Nova 2.1.0
*	Updated write_peronallog.php to be uniform with Nova 2.1.0
*	Updated sim_viewlog.php to be uniform with Nova 2.1.0

1328666468:

*	Updating files to be compatible with Nova 2.0.1

1294317838:

*	Updated write.php to have code from version 1.2.2 of Nova

1284468115:

*	Updated controller files to work with Nova 1.1.

1272514259:

*	Created a more readable README for GitHub.

1270559641:

*	Nova 1.0 has added email_lang.php into the base_lang.php file for email language extensibility. Can
now place what was in app_lang.php into the ucip_lang.php file. Updated README. Removed app_lang.php
file from repository.

1270136498:

*	Added application/language/english/app_lang.php to the repository. The perpose of this file is to
overwrite existing declared language keys.

1270069498:

*	Started work on coming up with an alternate layout for personal logs. Alternate layout includes fields
for location and stardate. Email template has been adjusted to show these as well.