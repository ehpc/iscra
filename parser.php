<?php

include "iscra.php";

$dbHost = "localhost"; // Database host
$dbName = "iscra"; // Database name
$dbUser = "root"; // Database user
$dbPassword = ""; // Database password
$dbQueueTable = "queue"; // Table for storing parser-specific data

$baseUrl = "http://google.ru"; // Base url to resolve relative urls in page
$startUrl = "http://www.google.com/search?q=javascript+split1&ie=utf-8&oe=utf-8&aq=t&rls=org.mozilla:ru:official&client=firefox#sclient=psy&hl=en&client=firefox&hs=NAT&rls=org.mozilla:ru%3Aofficial&source=hp&q=javascript+split1f&aq=&aqi=&aql=&oq=javascript+split1f&gs_rfai=&pbx=1&fp=2289185d5cea093"; // From where to start parsing
// Regular expressions to define what urls will be parsed
$urlsRange = array(
    '/google\.ru/',
    '/google\.com/'
);
// Regular expressions for parsing page
$parserRegexes = array(
    '/href="(?P<href>.+?)"/'
);


$iscra = new Iscra(
    $dbHost, $dbName, $dbUser, $dbPassword, $dbQueueTable, 
    $baseUrl, $startUrl,
    $urlsRange, $parserRegexes
);


function mapper($res)
{
    /* We have one row per each regex */
    foreach ($res as $match)
    {
        /*
         * Here goes your business logic
         * BEGIN
         */
        foreach ($match["href"] as $href)
        {
            if (reset(Finder::where("data", "href = '$href'")) === false)
            {
                $row = R::dispense("data");
                $row->href = $href;
                R::store($row);
            }
        }
        /*
         * Here ends your business logic
         * END
         */
    }
}

if (isset($_GET["reset"]))
{
    echo $iscra->reset();
}
else
{
    echo $iscra->runOnce();
}


