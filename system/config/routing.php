<?php

/**
 * The below routes are actually slightly more user-friendly preg_match
 * and preg_replace regular expressions. The key is a regular expression
 * matched against the PATH_INFO environment variable, and its corresponding 
 * value is what to replace it with.
 * 
 * Several expression macros are available for these, and are meant to match
 * the intuitiveness of CodeIgniter's routing expressions which usually meet
 * most needs.
 * 
 * The macros are as follows:
 * String          Equivalent          Purpose
 * -------------------------------------------------------------------------
 * MATCHING EXPRESSION:
 * (:num)          ([0-9]+)            Match any integer.
 * (:any)          (.+)                Match any character.
 * 
 * REPLACEMENT EXPRESSION:
 * $1              \1                  Insert first parenthetical match.
 * $2              \2                  Insert second parenthetical match.
 * 
 * For example:
 *    $routes['user/([0-9]+)'] = 'user/detail/\1';
 * Can be simplified to:
 *    $routes['user/(:num)'] = 'user/detail/$1';
 * 
 * Both forms will still work regardless, and will end up calling the
 * controller method User::detail() where the first argument is the value
 * of the parenthetical match.
 */

$routes = array();
$routes['blog/rss.xml'] = 'blog/rss';   // Route /blog/rss.xml to Blog::rss()

