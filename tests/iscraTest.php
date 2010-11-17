<?php

class IscraTest extends PHPUnit_Framework_TestCase
{
    var $iscra;

    function setUp()
    {
        require_once "../iscra.php";
        
        $dbHost = "localhost"; // Database host
        $dbName = "iscra"; // Database name
        $dbUser = "root"; // Database user
        $dbPassword = ""; // Database password
        $dbQueueTable = "queue"; // Table for storing parser-specific data

        $baseUrl = "http://google.com"; // Base url to resolve relative urls in page
        $startUrl = "http://www.google.com"; // From where to start parsing
        // Regular expressions to define what urls will be parsed
        $urlsRange = array(
            '/google\.ru/',
            '/google\.com/'
        );
        // Regular expressions for parsing page
        $parserRegexes = array(
            '/href="(?P<href>.+?)"/'
        );
        $this->iscra = new Iscra(
            $dbHost, $dbName, $dbUser, $dbPassword, $dbQueueTable,
            $baseUrl, $startUrl,
            $urlsRange, $parserRegexes
        );
    }

    function tearDown()
    {
        unset($this->iscra);
    }




    public function testReset()
    {
        $result = $this->iscra->reset();
        $this->assertEquals($result, "Parser reset");
    }

    /*
     * @depends testReset
     */
    public function testRunOnce()
    {
        function mapper($res) {
            
        }
        $result = $this->iscra->runOnce();
        $this->assertStringMatchesFormat("%d;%d", $result);
    }

    /**
     * @depends testReset
     * @dataProvider urlProvider
     */
    public function testFetchUrl($url, $rx)
    {
        $result = $this->iscra->fetchUrl($url);
        $this->assertRegExp($rx, $result);
    }

    public function urlProvider()
    {
        return array(
            array("http://eugenemaslovich.com/test", "/^TEST$/"),
            array("http://google.com/", "/google/im")
        );
    }

}