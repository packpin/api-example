<?php
error_reporting(E_ALL);
require_once "packpin.class.php";

// Don't forget to fill in you API Key (http://panel.packpin.com)
$apiKey = '';

$packpin = new PackpinREST($apiKey);

echo '<pre>';
try {
    // Get a list of all trackers on Packpin
    // https://packpin.com/docs/carriers/carrier-collection-get
    // API Docs URL : https://packpin.com/docs/trackings/trackings-collection-post
    $carriers = $packpin->execRequest('/carriers', 'GET');

    // Create one tracking code in the system
    // The created code is updated automatically, so you don't need to recreate it every time!
    // API Docs URL : https://packpin.com/docs/trackings/trackings-collection-post
    $createTracking = $packpin->execRequest('/trackings', 'POST', array(
       'code'       => '058200005422993',   // The code provided by the Carrier
       'carrier'    => 'dpd',               // Packpin Carrier ID/Code (GET /carriers -> code)
    ));

    // If you want to create multiple codes at a time
    // Use PUT /trackings/batch
    // API Docs URL : https://packpin.com/docs/trackings/trackings-batch-insertion-post
    /*
     $createBatchTrackings = $packpin->execRequest('/trackings', 'POST', array(
        array(
            'code'       => '058200005422993',   // The code provided by the Carrier
            'carrier'    => 'dpd',               // Packpin Carrier ID/Code (GET /carriers -> code)
        ),
        array(
            'code'       => 'RC000994148CN',     // The code provided by the Carrier
            'carrier'    => 'postcn',            // Packpin Carrier ID/Code (GET /carriers -> code)
        )
     ));
    */

    // You can get the latest status of more than one code created before using this method
    // The created code is updated automatically, so you don't need to retrieve info about it for status to change!
    // The added parameters are OPTIONAL!
    // API Docs URL : https://packpin.com/docs/trackings/trackings-collection-get
    $multipleTrackingCodes = $packpin->execRequest('/trackings/', 'GET', array(
        'page'  => 1,
        'limit' => 100
    ));

    // You can get the latest status of the tracking code you created before using this method
    // The created code is updated automatically, so you don't need to retrieve info about it for status to change!
    // API Docs URL : https://packpin.com/docs/trackings/single-tracking-item-get
    $singleTrackingCode = $packpin->execRequest('/trackings/dpd/058200005422993', 'GET');

    // You can delete the tracking code from the system using this method
    // No data is needed
    // API Docs URL : https://packpin.com/docs/trackings/trackings-collection-get
    $deleteSingleTrackingCode = $packpin->execRequest('/trackings/dpd/058200005422993', 'DELETE');

} catch (Exception $e){
   echo $e;
}

echo "\n\n--- carriers --\n\n";
print_r($carriers);

echo "\n\n--- createTracking --\n\n";
print_r($createTracking);

echo "\n\n--- multipleTrackingCodes --\n\n";
print_r($multipleTrackingCodes);

echo "\n\n--- deleteSingleTrackingCode --\n\n";
print_r($deleteSingleTrackingCode);

echo '</pre>';

