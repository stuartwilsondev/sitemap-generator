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

class SiteMapGeneratorV2 {

    const SITEMAP_FILE_NAME = 'sitemap.xml';
    const SITEMAP_INDEX_FILE_NAME = 'sitemap-index.xml';
    const ROBOTS_FILE_NAME = 'robots.txt';
    const MAX_URLS_PER_SITEMAP = 50000;
    const CURRENT_VERSION = '2.0';
    const URL_LENGTH = 2048;

    protected static $allowedFrequencies = array(
        'always',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'never'
    );

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

    private $createGZipFile = false;
    private $baseUrl;
    private $basePath;
    private $urls;
    private $sitemaps;
    private $sitemapIndex;
    private $sitemapFullURL;

    private $searchEngines = array(
        array("http://search.yahooapis.com/SiteExplorerService/V1/updateNotification?appid=USERID&url=",
            "http://search.yahooapis.com/SiteExplorerService/V1/ping?sitemap="),
        "http://www.google.com/webmasters/tools/ping?sitemap=",
        "http://submissions.ask.com/ping?sitemap=",
        "http://www.bing.com/webmaster/ping.aspx?siteMap="
    );

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



    private function addUrlToUrls(\stdClass $url){
        array_push($this->urls,$url);
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

        if(!in_array($priority,$this->getAllowedPriorities())){
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
            $currentUrl->lastMod = $lastModified;
        }else{
            $currentUrl->lastMod = date('c');
        }

        $this->addUrlToUrls($currentUrl);
    }

    public function createSiteMap()
    {
        if(!is_array($this->getUrls()) || !count($this->getUrls())){
            throw new Exception('There are no Urls to process');
        }

        if(count($this->getUrls()) > self::MAX_URLS_PER_SITEMAP){
            throw new Exception('Too many Urls');
        }
    }
}


