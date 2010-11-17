<?php

/*
 * Copyright 2010 Eugene Maslovich
 * http://eugenemaslovich.com/
 * ehpc@yandex.ru
 *
 */

error_reporting(E_ALL);

// Functions split_url, join_url and url_remove_dot_segments are taken from:
// http://nadeausoftware.com/articles/2008/05/php_tip_how_parse_and_build_urls
//
function split_url( $url, $decode=TRUE )
{
    $xunressub     = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
    $xpchar        = $xunressub . ':@%';

    $xscheme       = '([a-zA-Z][a-zA-Z\d+-.]*)';

    $xuserinfo     = '((['  . $xunressub . '%]*)' .
                     '(:([' . $xunressub . ':%]*))?)';

    $xipv4         = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';

    $xipv6         = '(\[([a-fA-F\d.:]+)\])';

    $xhost_name    = '([a-zA-Z\d-.%]+)';

    $xhost         = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';
    $xport         = '(\d*)';
    $xauthority    = '((' . $xuserinfo . '@)?' . $xhost .
                     '?(:' . $xport . ')?)';

    $xslash_seg    = '(/[' . $xpchar . ']*)';
    $xpath_authabs = '((//' . $xauthority . ')((/[' . $xpchar . ']*)*))';
    $xpath_rel     = '([' . $xpchar . ']+' . $xslash_seg . '*)';
    $xpath_abs     = '(/(' . $xpath_rel . ')?)';
    $xapath        = '(' . $xpath_authabs . '|' . $xpath_abs .
                     '|' . $xpath_rel . ')';

    $xqueryfrag    = '([' . $xpchar . '/?' . ']*)';

    $xurl          = '^(' . $xscheme . ':)?' .  $xapath . '?' .
                     '(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';


    // Split the URL into components.
    if ( !preg_match( '!' . $xurl . '!', $url, $m ) )
        return FALSE;

    if ( !empty($m[2]) )        $parts['scheme']  = strtolower($m[2]);

    if ( !empty($m[7]) ) {
        if ( isset( $m[9] ) )   $parts['user']    = $m[9];
        else            $parts['user']    = '';
    }
    if ( !empty($m[10]) )       $parts['pass']    = $m[11];

    if ( !empty($m[13]) )       $h=$parts['host'] = $m[13];
    else if ( !empty($m[14]) )  $parts['host']    = $m[14];
    else if ( !empty($m[16]) )  $parts['host']    = $m[16];
    else if ( !empty( $m[5] ) ) $parts['host']    = '';
    if ( !empty($m[17]) )       $parts['port']    = $m[18];

    if ( !empty($m[19]) )       $parts['path']    = $m[19];
    else if ( !empty($m[21]) )  $parts['path']    = $m[21];
    else if ( !empty($m[25]) )  $parts['path']    = $m[25];

    if ( !empty($m[27]) )       $parts['query']   = $m[28];
    if ( !empty($m[29]) )       $parts['fragment']= $m[30];

    if ( !$decode )
        return $parts;
    if ( !empty($parts['user']) )
        $parts['user']     = rawurldecode( $parts['user'] );
    if ( !empty($parts['pass']) )
        $parts['pass']     = rawurldecode( $parts['pass'] );
    if ( !empty($parts['path']) )
        $parts['path']     = rawurldecode( $parts['path'] );
    if ( isset($h) )
        $parts['host']     = rawurldecode( $parts['host'] );
    if ( !empty($parts['query']) )
        $parts['query']    = rawurldecode( $parts['query'] );
    if ( !empty($parts['fragment']) )
        $parts['fragment'] = rawurldecode( $parts['fragment'] );
    return $parts;
}

function join_url( $parts, $encode=TRUE )
{
    if ( $encode )
    {
        if ( isset( $parts['user'] ) )
            $parts['user']     = rawurlencode( $parts['user'] );
        if ( isset( $parts['pass'] ) )
            $parts['pass']     = rawurlencode( $parts['pass'] );
        if ( isset( $parts['host'] ) &&
            !preg_match( '!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host'] ) )
            $parts['host']     = rawurlencode( $parts['host'] );
        if ( !empty( $parts['path'] ) )
            $parts['path']     = preg_replace( '!%2F!ui', '/',
                rawurlencode( $parts['path'] ) );
        if ( isset( $parts['query'] ) )
            $parts['query']    = rawurlencode( $parts['query'] );
        if ( isset( $parts['fragment'] ) )
            $parts['fragment'] = rawurlencode( $parts['fragment'] );
    }

    $url = '';
    if ( !empty( $parts['scheme'] ) )
        $url .= $parts['scheme'] . ':';
    if ( isset( $parts['host'] ) )
    {
        $url .= '//';
        if ( isset( $parts['user'] ) )
        {
            $url .= $parts['user'];
            if ( isset( $parts['pass'] ) )
                $url .= ':' . $parts['pass'];
            $url .= '@';
        }
        if ( preg_match( '!^[\da-f]*:[\da-f.:]+$!ui', $parts['host'] ) )
            $url .= '[' . $parts['host'] . ']'; // IPv6
        else
            $url .= $parts['host'];             // IPv4 or name
        if ( isset( $parts['port'] ) )
            $url .= ':' . $parts['port'];
        if ( !empty( $parts['path'] ) && $parts['path'][0] != '/' )
            $url .= '/';
    }
    if ( !empty( $parts['path'] ) )
        $url .= $parts['path'];
    if ( isset( $parts['query'] ) )
        $url .= '?' . $parts['query'];
    if ( isset( $parts['fragment'] ) )
        $url .= '#' . $parts['fragment'];
    return $url;
}

function url_remove_dot_segments( $path )
{
    // multi-byte character explode
    $inSegs  = preg_split( '!/!u', $path );
    $outSegs = array( );
    foreach ( $inSegs as $seg )
    {
        if ( $seg == '' || $seg == '.')
            continue;
        if ( $seg == '..' )
            array_pop( $outSegs );
        else
            array_push( $outSegs, $seg );
    }
    $outPath = implode( '/', $outSegs );
    if ( $path[0] == '/' )
        $outPath = '/' . $outPath;
    // compare last multi-byte character against '/'
    if ( $outPath != '/' &&
        (mb_strlen($path)-1) == mb_strrpos( $path, '/', 'UTF-8' ) )
        $outPath .= '/';
    return $outPath;
}




/*
 * Iscra main class
 *
 */


class Iscra
{
    // Database settings
    public $dbHost;
    public $dbName;
    public $dbUser;
    public $dbPassword;
    public $dbQueueTable; // Table where parser stores queue

    //
    public $baseUrl; // Url to resolve relative paths
    public $startUrl; // First url to crawl
    public $urlsRange; // Regexes array defining range of urls to be crawled
    public $parserRegexes; // Regexes to find data in pages

    function __construct(
            $dbHost, $dbName, $dbUser, $dbPassword, $dbQueueTable,
            $baseUrl, $startUrl,
            $urlsRange, $parserRegexes)
    {
        $this->dbHost = $dbHost;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPassword = $dbPassword;
        $this->dbQueueTable = $dbQueueTable;
        $this->baseUrl = $baseUrl;
        $this->startUrl = $startUrl;
        $this->urlsRange = $urlsRange;
        $this->parserRegexes = $parserRegexes;

        // Setup db
        require_once "rb12lg.php"; // We use Redbean ORM
        R::setup("mysql:host=$this->dbHost;dbname=$this->dbName", $this->dbUser, $this->dbPassword);
    }

    // Clear queue
    public function reset()
    {
        $database = R::$adapter;
        $database->exec("TRUNCATE TABLE `".$this->dbQueueTable."`");
        return "Parser reset";
    }

    // Run parser on one item in queue
    public function runOnce()
    {
        // Init queue
        $this->pushToQueue($this->startUrl);

        // Get next item from queue
        $url = $this->popFromQueue();
        // TODO: What if connection have been broken
        if ($url !== null)
        {
            // Get page from web
            $html = $this->fetchUrl($url);
            // Find urls in page
            $this->extractUrls($html);
            // Extract data from page
            $this->parse($html, 'mapper');
            // Response
            $curN = count(Finder::where($this->dbQueueTable, "used = 1"));
            $n = count(Finder::where($this->dbQueueTable, ""));
            return "$curN;$n";
        }
        else
        {
            // No items in queue
            return "END";
        }
    }

    // Add url to queue
    public function pushToQueue($url)
    {
        // If url is not in queue already
        if (reset(Finder::where($this->dbQueueTable, "url = '$url'")) === FALSE)
        {
            $row = R::dispense($this->dbQueueTable);
            $row->url = $url;
            $row->used = 0; // Url is not crawled yet
            R::store($row);
        }
    }

    // Remove first url from queue and return it
    public function popFromQueue()
    {
        $row = reset(Finder::where($this->dbQueueTable, "used = 0"));
        if ($row !== FALSE)
        {
            $row->used = 1; // Now this url won't pop
            R::store($row);
            return $row->url;
        }
        else
        {
            return null;
        }
    }

    // parse text
    public function parse($text, $mapper)
    {
        $matches = array();
        for ($i = 0; $i < count($this->parserRegexes); $i++)
        {
            preg_match_all($this->parserRegexes[$i], $text, $match);
            $matches[$i] = $match;
        }
        // Dispatch results to user-defined handler
        return $$mapper($matches);
    }

    // get all urls in string
    public function extractUrls($text)
    {
        // TODO: maybe extend regex to find more urls?
        $rx = '/href="(?P<url>.+?)"/i';
        preg_match_all($rx, $text, $urls);
        foreach ($urls["url"] as $url)
        {
            $url = $this->absoluteUrl($this->baseUrl, $url);
            $inRange = false;
            // Check if extracted url is in allowed range
            foreach ($this->urlsRange as $urlRange)
            {
                if (preg_match($urlRange, $url) != 0)
                {
                    $inRange = true;
                    break;
                }
            }
            // If url is in range, add it to queue
            if ($inRange)
            {
                $this->pushToQueue($url);
            }
        }
    }

    // get html from url
    public function fetchUrl($url)
    {
        // is curl installed?
        if (!function_exists('curl_init'))
        {
            die('CURL is not installed!');
        }
        // create a new curl resource
        $ch = curl_init();
        // set URL to download
        curl_setopt($ch, CURLOPT_URL, $url);
        // set referer:
        curl_setopt($ch, CURLOPT_REFERER, "http://www.google.com/");
        // user agent:
        curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
        // remove header? 0 = yes, 1 = no
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // should curl return or print the data? true = return, false = print
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        // download the given URL, and return output
        $output = curl_exec($ch);
        // close the curl resource, and free system resources
        curl_close($ch);
        return $output;
    }

    // http://nadeausoftware.com/node/79
    public function absoluteUrl($baseUrl, $relativeUrl)
    {
        // If relative URL has a scheme, clean path and return.
        $r = split_url( $relativeUrl );
        if ( $r === FALSE )
            return FALSE;
        if ( !empty( $r['scheme'] ) )
        {
            if ( !empty( $r['path'] ) && $r['path'][0] == '/' )
                $r['path'] = url_remove_dot_segments( $r['path'] );
            return join_url( $r );
        }

        // Make sure the base URL is absolute.
        $b = split_url( $baseUrl );
        if ( $b === FALSE || empty( $b['scheme'] ) || empty( $b['host'] ) )
            return FALSE;
        $r['scheme'] = $b['scheme'];

        // If relative URL has an authority, clean path and return.
        if ( isset( $r['host'] ) )
        {
            if ( !empty( $r['path'] ) )
                $r['path'] = url_remove_dot_segments( $r['path'] );
            return join_url( $r );
        }
        unset( $r['port'] );
        unset( $r['user'] );
        unset( $r['pass'] );

        // Copy base authority.
        $r['host'] = $b['host'];
        if ( isset( $b['port'] ) ) $r['port'] = $b['port'];
        if ( isset( $b['user'] ) ) $r['user'] = $b['user'];
        if ( isset( $b['pass'] ) ) $r['pass'] = $b['pass'];

        // If relative URL has no path, use base path
        if ( empty( $r['path'] ) )
        {
            if ( !empty( $b['path'] ) )
                $r['path'] = $b['path'];
            if ( !isset( $r['query'] ) && isset( $b['query'] ) )
                $r['query'] = $b['query'];
            return join_url( $r );
        }

        // If relative URL path doesn't start with /, merge with base path
        if ( $r['path'][0] != '/' )
        {
            $base = mb_strrchr( $b['path'], '/', TRUE, 'UTF-8' );
            if ( $base === FALSE ) $base = '';
            $r['path'] = $base . '/' . $r['path'];
        }
        $r['path'] = url_remove_dot_segments( $r['path'] );
        return join_url( $r );
    }

}



