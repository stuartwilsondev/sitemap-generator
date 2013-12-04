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


class SiteMapGeneratorV2 {

    const SITEMAP_FILE_NAME = 'sitemap.xml';
    const SITEMAP_INDEX_FILE_NAME = 'sitemap-index.xml';
    const ROBOTS_FILE_NAME = 'robots.txt';
    const MAX_URLS_PER = 50000;
    const CURRENT_VERSION = '2.0';

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

  

}


