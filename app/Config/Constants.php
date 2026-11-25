<?php

/*
 | --------------------------------------------------------------------
 | App Namespace
 | --------------------------------------------------------------------
 |
 | This defines the default Namespace that is used throughout
 | CodeIgniter to refer to the Application directory. Change
 | this constant to change the namespace that all application
 | classes should use.
 |
 | NOTE: changing this will require manually modifying the
 | existing namespaces of App\* namespaced-classes.
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 | --------------------------------------------------------------------------
 | Composer Path
 | --------------------------------------------------------------------------
 |
 | The path that Composer's autoload file is expected to live. By default,
 | the vendor folder is in the Root directory, but you can customize that here.
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', ROOTPATH . 'vendor/autoload.php');

/*
 |--------------------------------------------------------------------------
 | Timing Constants
 |--------------------------------------------------------------------------
 |
 | Provide simple ways to work with the myriad of PHP functions that
 | require information to be in seconds.
 */
defined('SECOND') || define('SECOND', 1);
defined('MINUTE') || define('MINUTE', 60);
defined('HOUR')   || define('HOUR', 3600);
defined('DAY')    || define('DAY', 86400);
defined('WEEK')   || define('WEEK', 604800);
defined('MONTH')  || define('MONTH', 2_592_000);
defined('YEAR')   || define('YEAR', 31_536_000);
defined('DECADE') || define('DECADE', 315_360_000);

/*
 | --------------------------------------------------------------------------
 | Exit Status Codes
 | --------------------------------------------------------------------------
 |
 | Used to indicate the conditions under which the script is exit()ing.
 | While there is no universal standard for error codes, there are some
 | broad conventions.  Three such conventions are mentioned below, for
 | those who wish to make use of them.  The CodeIgniter defaults were
 | chosen for the least overlap with these conventions, while still
 | leaving room for others to be defined in future versions and user
 | applications.
 |
 | The three main conventions used for determining exit status codes
 | are as follows:
 |
 |    Standard C/C++ Library (stdlibc):
 |       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
 |       (This link also contains other GNU-specific conventions)
 |    BSD sysexits.h:
 |       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
 |    Bash scripting:
 |       http://tldp.org/LDP/abs/html/exitcodes.html
 |
 */
defined('EXIT_SUCCESS')        || define('EXIT_SUCCESS', 0);        // no errors
defined('EXIT_ERROR')          || define('EXIT_ERROR', 1);          // generic error
defined('EXIT_CONFIG')         || define('EXIT_CONFIG', 3);         // configuration error
defined('EXIT_UNKNOWN_FILE')   || define('EXIT_UNKNOWN_FILE', 4);   // file not found
defined('EXIT_UNKNOWN_CLASS')  || define('EXIT_UNKNOWN_CLASS', 5);  // unknown class
defined('EXIT_UNKNOWN_METHOD') || define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     || define('EXIT_USER_INPUT', 7);     // invalid user input
defined('EXIT_DATABASE')       || define('EXIT_DATABASE', 8);       // database error
defined('EXIT__AUTO_MIN')      || define('EXIT__AUTO_MIN', 9);      // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      || define('EXIT__AUTO_MAX', 125);    // highest automatically-assigned error code

/*
 |--------------------------------------------------------------------------
 | SpiderWaves Domain Constants
 |--------------------------------------------------------------------------
 */
defined('stock_id')                    || define('stock_id', '1');
defined('stock_private_key')            || define('stock_private_key', '87890fca66eacc10c9c7cf440a956262');
defined('casting_id')                  || define('casting_id', '2');
defined('casting_private_key')          || define('casting_private_key', '80f372f8dffb5126736fa8c02a24c9fa');
defined('heera_light_id')              || define('heera_light_id', '3');
defined('heera_light_private_key')      || define('heera_light_private_key', 'cb4e1da17c5c64278fe575d4dcfe7491');
defined('filing_id')                   || define('filing_id', '4');
defined('filing_private_key')           || define('filing_private_key', '0eca01acd25aada9700d6a22406795fa');
defined('setting_account_id')          || define('setting_account_id', '5');
defined('setting_private_key')          || define('setting_private_key', 'd3dc6550b750332ff3d5c369791c8eda');
defined('polish_id')                   || define('polish_id', '6');
defined('polish_private_key')           || define('polish_private_key', '7634c0ea21ffed80632a521eec65a841');
defined('rodium_account_id')           || define('rodium_account_id', '7');
defined('rodium_private_key')           || define('rodium_private_key', '592d02d58179f9b92b590ea1fea5ff2b');
defined('packing_id')                  || define('packing_id', '8');
defined('packing_private_key')          || define('packing_private_key', '68f2968985d8728d155d65ab5505ab34');
defined('refining_account_id')         || define('refining_account_id', '9');
defined('refining_private_key')         || define('refining_private_key', '860d4bcf649e0a2393ef85ae72cb2dd9');
defined('loss_account_id')             || define('loss_account_id', '12');
defined('loss_private_key')             || define('loss_private_key', '3442203db2c3bf62b928ac74f3d64b88');
defined('net_refining_account_id')     || define('net_refining_account_id', '10');
defined('net_refining_private_key')     || define('net_refining_private_key', '230556fb4f93ec758f2c69ae7eb553ed');
defined('more_account_id')             || define('more_account_id', '11');
defined('more_account_private_key')     || define('more_account_private_key', 'a5d6c93eda90de5944fbb4b0113c7165');
defined('g58_polish_id')               || define('g58_polish_id', '13');
defined('g58_polish_private_key')       || define('g58_polish_private_key', '43sdfh3sdklf239gjklfd43gkls4gfr4');
defined('g58_setting_account_id')      || define('g58_setting_account_id', '14');
defined('g58_setting_private_key')      || define('g58_setting_private_key', 'dsfs159422hzzqkd439fdpo60923jkd2');
defined('g58_filing_id')               || define('g58_filing_id', '15');
defined('g58_filing_private_key')       || define('g58_filing_private_key', '423fwr46jk8563db447hddv467jhm57h');
defined('zero_balance_id')             || define('zero_balance_id', '16');
defined('zero_balance_private_key')     || define('zero_balance_private_key', '43434325b7f95cbfe2e5edfbb1228c4a');
defined('g58_transfer_id')             || define('g58_transfer_id', '17');
defined('g58_transfer_private_key')     || define('g58_transfer_private_key', 'bb8e719f01a6ff8ca84634ce2095cf03');
defined('transfer_packing_id')         || define('transfer_packing_id', '18');
defined('transfer_packing_private_key') || define('transfer_packing_private_key', 'aagahedr01a6ff8a58427cef2095asvf');
defined('po_check_id')                 || define('po_check_id', '19');
defined('po_check_private_key')         || define('po_check_private_key', '8e10da49a72eaffc20sa6f5hgvara85f');
defined('galai_id')                    || define('galai_id', '20');
defined('galai_private_key')            || define('galai_private_key', 'fer32fgd3fif952mcmbje8234gis3824');
defined('tash_id')                     || define('tash_id', '21');
defined('tash_private_key')             || define('tash_private_key', 'n4l742kfdg96k3mbdw05k73mfmv6693k');
defined('sapphire_id')                 || define('sapphire_id', '22');
defined('sapphire_private_key')         || define('sapphire_private_key', 'zr4th43570jmf23679jbde5tghy65f4h');
defined('megnet_id')                   || define('megnet_id', '23');
defined('megnet_private_key')           || define('megnet_private_key', 'gqe2953fg4knbmassqww533fghjbc456');
defined('stone_cust_id')               || define('stone_cust_id', 'ab333333');

