<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
|---------------------------------------------------------------
| LANGUAGE FILE - ENGLISH
|---------------------------------------------------------------
| File: application/language/english/app_lang.php
| System Version: 1.0
|
| English language file for the system. Punctuation constants are
| defined in ./application/config/constants.php
|
*/

/* figure out what language the file is */
$language = basename(dirname(__FILE__));

/* include the base language file */
include_once APPPATH .'language/'. $language .'/base_lang.php';

/*
 * Your language array keys go here in the following format:
 * 
 * $lang['key'] = 'My Key';
 * 
 * If you want to override an existing key, you can do so
 * by redeclaring it like this:
 * 
 * $lang['global_position'] = 'job';
 * 
 */

/*
|---------------------------------------------------------------
| UCIP PERSONAL LOG EMAIL FIELDS
|---------------------------------------------------------------
*/

$lang['email_content_personal_log'] = "The following is a personal log from %s.

%s
%s

%s";

$lang['email_content_entry_pending'] = "The %s %s by %s has been held for moderation and must be approved before it can be emailed to the crew and appear on the site. For reference, the content of the pending %s is below.

%s
%s

%s

Please login using the link below to approve the %s.

%s";

/* End of file app_lang.php */
/* Location: ./application/language/english/app_lang.php */