<?php

namespace h4de5\BabyDol;

use Google_Service_Customsearch;
use Google_Client;


/**
 * Retrieves a simple set of google results for a given plant id.
 * see: https://stackoverflow.com/questions/41592249/how-to-use-php-client-for-google-custom-search-engine
 */
class Search /*implements \IteratorAggregate*/ {

    // Create one or more API keys at https://console.developers.google.com/apis/credentials
    
    private $apiKey;

    /* The search engine id is specific to each "custom search engine"
    * you have configured at https://cse.google.com/cse/all     

    * Remember that you must have enabled Custom Search API for the project that
    * contains your API Key.  You can do this at the following url:
    * https://console.developers.google.com/apis/api/customsearch.googleapis.com/overview?project=vegfetch-v01&duration=PT1H    

    * If you fail to enable the Custom Search API before you try to execute a search
    * the exception that is thrown will indicate this.  */
    private $gcse;

    // Holds the GoogleService for reuse
    private $service;

    // Holds the optParam for our search engine id
    private $optParamSEID;

    /**
     * Creates a service object for our Google Custom Search.  The API key is 
     * permiently set, but the search engine id may be changed when performing 
     * searches in case you want to search across multiple pre-prepared engines.
     * 
     * @param string $appName       Optional name for this google search
     */
    public function __construct($appName = "Search", $configPath = "apikey-token.json") {

        $this->loadConfig($configPath);

        $client = new Google_Client();

        // application name is an arbitrary name
        $client->setApplicationName($appName);

        // the developer key is the API Key for a specific google project
        // $client->setDeveloperKey(self::GCSE_API_KEY);
        $client->setDeveloperKey($this->apiKey);
        

        // create new service
        $this->service = new Google_Service_Customsearch($client);

        // You must specify a custom search engine.  You can do this either by setting
        // the element "cx" to the search engine id, or by setting the element "cref"
        // to the public url for that search engine.
        // 
        // For a full list of possible params see https://github.com/google/google-api-php-client-services/blob/master/src/Google/Service/Customsearch/Resource/Cse.php
        // $this->optParamSEID = array("cx"=>self::GCSE_SEARCH_ENGINE_ID);
        $this->optParamSEID = array("cx"=>$this->gcse);
    }

    public function loadConfig($file) {
        if(\file_exists($file)) {
            $config = \json_decode(file_get_contents($file));

            $this->apiKey = $config->apikey;
            $this->gcse = $config->cx;
        }
    }

    /**
     * A simplistic function to take a search term & search options and return an 
     * array of results.  You may want to 
     * 
     * @param string    $searchTerm     The term you want to search for
     * @param array     $optParams      See: For a full list of possible params see https://github.com/google/google-api-php-client-services/blob/master/src/Google/Service/Customsearch/Resource/Cse.php
     * @return array                                An array of search result items
     */
    public function getSearchResults($searchTerm, $optParams = array()){
        // return array containing search result items
        $items = array();

        // Merge our search engine id into the $optParams
        // If $optParams already specified a 'cx' element, it will replace our default
        $optParams = array_merge($this->optParamSEID, $optParams);

        //var_dump($optParams);

        // set search term & params and execute the query
        $results = $this->service->cse->listCse($searchTerm, $optParams);

        // return $results->getItems();

        // Since cse inherits from Google_Collections (which implements Iterator)
        // we can loop through the results by using `getItems()`
        foreach($results->getItems() as $k => $item) {
            
            // if(!empty($item->link)) {
            if(!empty($item->image->thumbnailLink)) {
                // var_dump($item);
                $items[] = $item;
            }
        }

        return $items;
    }
}