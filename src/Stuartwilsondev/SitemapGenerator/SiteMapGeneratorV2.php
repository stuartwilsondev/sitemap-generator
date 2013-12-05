<?php
/**
 * @author Stuart Wilson <stuart@stuartwilsondev.com>
 * This is based on the work already done int he sitemapgenerator available
 * on Github @see https://github.com/pawelantczak/php-sitemap-generator
 *
 * This class is an updated version of the original
 *
 */

namespace Stuartwilsondev\SitemapGenerator;

use InvalidArgumentException;
use LengthException;
use Exception;
use SimpleXMLElement;
use BadMethodCallException;

/**
 * Class SiteMapGeneratorV2
 * @package Stuartwilsondev\SitemapGenerator
 */
class SiteMapGeneratorV2 {

    /**
     * The final file name of the sitemap file
     */
    const SITEMAP_FILE_NAME = 'sitemap.xml';

    /**
     * The final name of the sitemap index file
     */
    const SITEMAP_INDEX_FILE_NAME = 'sitemap-index.xml';

    /**
     * the name of the robots.txt file
     */
    const ROBOTS_FILE_NAME = 'robots.txt';

    /**
     * MAximum number of Urls per sitemap file
     */
    const MAX_URLS_PER_SITEMAP = 50000;

    /**
     * Current version of this script
     */
    const CURRENT_VERSION = '2.0';

    /**
     * Maximum Url length (per url)
     */
    const URL_LENGTH = 2048;

    /**
     * Array containing the valid frequency strings that can be used
     * @var array
     */
    protected static $allowedFrequencies = array(
        'always',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'never'
    );

    /**
     * Array containing the valid priorities that can be given to Urls
     * @var array
     */
    protected static $allowedPriorities = array(
        '1',
        '0.9',
        '0.8',
        '0.7',
        '0.6',
        '0.5',
        '0.4',
        '0.3',
        '0.2',
        '0.1'
    );

    /**
     * Whether a GZipped file should also be created (not currently used)
     * @var bool
     */
    private $createGZipFile = false;

    /**
     * Placeholder for the base url of the site (where the sitemaps should live)
     * @var String
     */
    private $baseUrl;

    /**
     * Placeholder for the basepath
     * @var
     */
    private $basePath;

    /**
     * Placeholder for the Urls to which they are added
     * @var array
     */
    private $urls = array();

    /**
     * Placeholder for Sitemaps
     * @var array
     */
    private $sitemaps = array();

    /**
     *
     * @var
     */
    private $sitemapIndex;

    /**
     * @var
     */
    private $sitemapFullURL;

    /**
     * An array containing the urls of the search engines that should be notified when the sitemap changes
     * @var array
     */
    private $searchEngines = array(
        "http://search.yahooapis.com/SiteExplorerService/V1/ping?sitemap=",
        "http://www.google.com/webmasters/tools/ping?sitemap=",
        "http://submissions.ask.com/ping?sitemap=",
        "http://www.bing.com/webmaster/ping.aspx?siteMap=",
        //"http://zhanzhang.baidu.com/dashboard/index",
        //"http://webmaster.yandex.com/site/map.xml?sitemap=",
    );

    /**
     * If $additionalSearchEngines are provided they are merged with the predefined ones
     *
     * @param $baseURL
     * @param string $basePath
     * @param null $additionalSearchEngines
     */
    public function __construct($baseURL, $basePath = "", $additionalSearchEngines=null)
    {
        $this->setBaseUrl($baseURL);
        $this->setBasePath($basePath);

        //if passed additional search engines get the current ones and add the new ones
        if(isset($additionalSearchEngines) && is_array($additionalSearchEngines)){
            $this->setSearchEngines(
                array_merge($this->getSearchEngines(),$additionalSearchEngines)
            );
        }

    }

    /**
     * @param string $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param mixed $baseUrl
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param boolean $createGZipFile
     */
    public function setCreateGZipFile($createGZipFile)
    {
        $this->createGZipFile = $createGZipFile;
    }

    /**
     * @return boolean
     */
    public function getCreateGZipFile()
    {
        return $this->createGZipFile;
    }

    /**
     * @param array $searchEngines
     */
    public function setSearchEngines($searchEngines)
    {
        $this->searchEngines = $searchEngines;
    }

    /**
     * @return array
     */
    public function getSearchEngines()
    {
        return $this->searchEngines;
    }

    /**
     * @param mixed $sitemapFullURL
     */
    public function setSitemapFullURL($sitemapFullURL)
    {
        $this->sitemapFullURL = $sitemapFullURL;
    }

    /**
     * @return mixed
     */
    public function getSitemapFullURL()
    {
        return $this->sitemapFullURL;
    }

    /**
     * @param mixed $sitemapIndex
     */
    public function setSitemapIndex($sitemapIndex)
    {
        $this->sitemapIndex = $sitemapIndex;
    }

    /**
     * @return mixed
     */
    public function getSitemapIndex()
    {
        return $this->sitemapIndex;
    }

    /**
     * @param mixed $sitemaps
     */
    public function setSitemaps($sitemaps)
    {
        $this->sitemaps = $sitemaps;
    }

    /**
     * @return mixed
     */
    public function getSitemaps()
    {
        return $this->sitemaps;
    }

    /**
     * @param mixed $urls
     */
    public function setUrls($urls)
    {
        $this->urls = $urls;
    }

    /**
     * @return mixed
     */
    public function getUrls()
    {
        return $this->urls;
    }

    /**
     * @return array
     */
    public static function getAllowedFrequencies()
    {
        return self::$allowedFrequencies;
    }

    /**
     * @return array
     */
    public static function getAllowedPriorities()
    {
        return self::$allowedPriorities;
    }

    /**
     * @param \stdClass $url
     */
    private function addUrlToUrls(\stdClass $url){
        array_push($this->urls,$url);
    }

    /**
     * @param $sitemap
     */
    private function addSitemap($sitemap)
    {
        array_push($this->sitemaps,$sitemap);
    }

    /**
     * Generate the Sitemap header
     * @return string
     */
    private function getSitemapHeader()
    {
        $sitemapHeader = '<?xml version="1.0" encoding="UTF-8"?>
                             <urlset
                                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                                 xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
                                 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
                                 xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
                             </urlset>';

        return $sitemapHeader;
    }

    /**
     * Bulk add urls if they have already been collected.
     * This accepts an array off arrays which MUST have the keys
     * ['url']
     * ['changeFrequency']
     * ['priority']
     *
     * and can optionally have the key(s)
     * ['lastModified']
     *
     * This method iterates over the array and simply calls addUrl() for each url element
     * See addUrl method for explanation of these;
     *
     * $url, $lastModified = null, $changeFrequency = null, $priority = null
     * @param array $urls
     * @throws \InvalidArgumentException
     */
    public function addUrls(array $urls)
    {
        if (!is_array($urls)){
            throw new InvalidArgumentException("Provided argument is not an array.");
        }

        foreach($urls as $url){
            if(!in_array(array('url','changeFrequency'),array_keys($url))){
                throw new InvalidArgumentException("Provided array does not have required keys in all elements.");
            }

            $this->addUrl(
                $url['url'],
                $url['changeFrequency'],
                $url['priority'],
                (isset($url['lastModified']) ? $url['lastModified'] : null)
            );
        }
    }


    /**
     * Checks the parameters passed are of the correct type and valid.
     * creates a stdClass object which is passed to addToUrls
     *
     * @param $url                - the qualified url
     * @param $priority           - the priority that should be associated to this url
     * @param $changeFrequency    - the frequency the page at this url changes
     * @param mixed $lastModified - the date the page was last modified. If null the current
     *                              date will be used.
     *
     * @throws \InvalidArgumentException
     */
    public function addUrl($url, $priority, $changeFrequency, $lastModified = null) {
        if(!in_array($changeFrequency,$this->getAllowedFrequencies())){
            throw new InvalidArgumentException(
                sprintf("The provided change frequency of %s is not an allowed frequency. Must be one of %s",
                    $changeFrequency,
                    implode(', ',$this->getAllowedFrequencies())
                )
            );
        }

        if(!in_array((string)$priority,$this->getAllowedPriorities())){
            throw new InvalidArgumentException(
                sprintf("The provided priority of %s is not an allowed priority. Must be one of %s",
                    $priority,
                    implode(', ',$this->getAllowedPriorities())
                )
            );
        }

        //TODO length
        $currentUrl = new \stdClass();
        $currentUrl->loc = $url;
        $currentUrl->priority = $priority;
        $currentUrl->changefreq = $changeFrequency;
        if(isset($lastModified)){
            $currentUrl->lastmod = $lastModified;
        }else{
            $currentUrl->lastmod = date('c');
        }

        $this->addUrlToUrls($currentUrl);
    }

    /**
     * Creates an XML sitemap from the Urls that have been added to the Url array.
     * Checks there are Urls and the number does not exceed the maximum permitted
     *
     * @throws \LengthException
     * @throws \Exception
     */
    public function createSiteMap()
    {
        if(!is_array($this->getUrls()) || !count($this->getUrls())){
            throw new Exception('There are no Urls to process');
        }

        if(count($this->getUrls()) > self::MAX_URLS_PER_SITEMAP){
            throw new Exception('Too many Urls');
        }

        //Everything is ok so far - generate the xml sitemap
        $xmlSiteMap = new SimpleXMLElement($this->getSitemapHeader());

        foreach($this->getUrls() as $url) {

            //create a url element
            $row = $xmlSiteMap->addChild('url');

            //add the child elements to the url element - the url information
            $row->addChild('loc',htmlspecialchars($url->loc,ENT_QUOTES,'UTF-8'));
            $row->addChild('priority',$url->priority);
            $row->addChild('changefreq',$url->changefreq);
            $row->addChild('lastmod', $url->lastmod);

        }

        //check the file is an acceptable size
        if (strlen($xmlSiteMap->asXML()) > 10485760)
            throw new LengthException("Sitemap > 10MB, will not be indexed unless it is smaller ( < 10MB )");
        $this->addSitemap($xmlSiteMap->asXML());

        $this->setSitemapFullURL(sprintf("%s/%s",$this->getBaseUrl(),self::SITEMAP_FILE_NAME));

    }

    /**
     * Create the sitemap-index.xml file
     * which contains links to individual sitemaps
     */
    public function createSitemapIndex()
    {
        $sitemapIndex = new SimpleXMLElement($this->getSitemapHeader());
        foreach($this->getSitemaps() as $sitemap){
            $row = $sitemapIndex->addChild('sitemap');
            $row->addChild('loc',$this->getBaseUrl().htmlentities($sitemap[0]));
            $row->addChild('lastmod', date('c'));
        }

        $this->sitemapFullURL = $this->getBaseUrl().'sitemap-index.xml';
        $this->sitemapIndex = array(
            'sitemap-index.xml',
            $sitemapIndex->asXML()
        );
    }

    /**
     * Wrapper for writeToFile() that writes the sitemap-index.xml file and sitemap.xml to the predefined locations
     *
     * @throws \BadMethodCallException
     */
    public function writeSitemap()
    {
        if (!isset($this->sitemaps)) {
            throw new BadMethodCallException("No sitemap to write. Call createSitemap function first.");
        }

        if($this->getSitemapIndex()){
            $this->writeFile($this->getSitemapIndex(), $this->getBasePath(), self::SITEMAP_INDEX_FILE_NAME);
        }

        if($this->getSitemaps()){
            $this->writeFile($this->getSitemaps(),$this->getBasePath(),self::SITEMAP_FILE_NAME);
        }



    }

    /**
     * Writes file
     *
     * @param $content
     * @param $filePath
     * @param $fileName
     * @return bool
     */
    private function writeFile($content,$filePath,$fileName)
    {
        $file = fopen($filePath."/".$fileName, 'w');
        fwrite($file, $content[0]);
        return fclose($file);
    }


    /**
     * Notifies search engines of updated sitemap files
     *
     * @return array
     * @throws \BadMethodCallException
     */
    public function notifySearchEngines()
    {
        if(!$this->getSitemaps()) {
            throw new BadMethodCallException("No Sitemap to submit. To submit sitemap, call createSitemap function first.");
        }
        if(!extension_loaded('curl')){
            throw new BadMethodCallException("cURL library is required and not loaded.");
        }

        $result = array();
        foreach($this->getSearchEngines() as $searchEngine){

            $submitSite = curl_init($searchEngine.htmlspecialchars($this->getSitemapFullURL(),ENT_QUOTES,'UTF-8'));

            $responseContent = curl_exec($submitSite);
            $response = curl_getinfo($submitSite);

            $submitSiteShort = array_reverse(explode(".",parse_url($searchEngine, PHP_URL_HOST)));

            $result[] = array("site"=>$submitSiteShort[1].".".$submitSiteShort[0],
                "fullsite"=>$searchEngine.htmlspecialchars($this->sitemapFullURL, ENT_QUOTES,'UTF-8'),
                "http_code"=>$response['http_code'],
                "message"=>str_replace("\n", " ", strip_tags($responseContent)));
        }
        return $result;
    }
}


