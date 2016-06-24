<?php
class EnviaNovoFeed
{

  function recebeXML($xml , $feed_type)
  {

    include_once('.config.inc.php');

    $feed = $xml;

    $marketplaceIdArray = array("Id" => array('ATVPDKIKX0DER'));

    // United States:
    $serviceUrl = "https://mws.amazonservices.com";

    $config = array (
    'ServiceURL' => $serviceUrl,
    'ProxyHost' => null,
    'ProxyPort' => -1,
    'MaxErrorRetry' => 3,
    );

    $service = new MarketplaceWebService_Client(
     AWS_ACCESS_KEY_ID, 
     AWS_SECRET_ACCESS_KEY, 
     $config,
     APPLICATION_NAME,
     APPLICATION_VERSION);


    $feedHandle = @fopen('php://temp', 'rw+');
    fwrite($feedHandle, $feed);
    rewind($feedHandle);
    $parameters = array (
      'Merchant' => MERCHANT_ID,
      'MarketplaceIdList' => $marketplaceIdArray,
      'FeedType' => $feed_type,
      'FeedContent' => $feedHandle,
      'PurgeAndReplace' => false,
      'ContentMd5' => base64_encode(md5(stream_get_contents($feedHandle), true)),
      //'MWSAuthToken' => '<MWS Auth Token>', // Optional
      );

    rewind($feedHandle);

    $request = new MarketplaceWebService_Model_SubmitFeedRequest($parameters);
    $invoke = new EnviaNovoFeed();
    $feedID = $invoke->invokeSubmitFeed($service, $request);
    @fclose($feedHandle);
    return $feedID;
  }

  function invokeSubmitFeed(MarketplaceWebService_Interface $service, $request){
      try {
        $response = $service->submitFeed($request);
        if ($response->isSetSubmitFeedResult()) {
          $submitFeedResult = $response->getSubmitFeedResult();
          if ($submitFeedResult->isSetFeedSubmissionInfo()) {
            $feedSubmissionInfo = $submitFeedResult->getFeedSubmissionInfo();
            if ($feedSubmissionInfo->isSetFeedSubmissionId())
            {
              return $feedSubmissionInfo->getFeedSubmissionId();
            }
          }
        }

      } catch (MarketplaceWebService_Exception $ex) {
        echo("Caught Exception: " . $ex->getMessage() . "\n");
        echo("Response Status Code: " . $ex->getStatusCode() . "\n");
        echo("Error Code: " . $ex->getErrorCode() . "\n");
        echo("Error Type: " . $ex->getErrorType() . "\n");
        echo("Request ID: " . $ex->getRequestId() . "\n");
        echo("XML: " . $ex->getXML() . "\n");
        echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
      }
    }

}

