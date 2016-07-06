<?php

/**
* Classe que busca resultado do feed amazon e envia email para os destinatarios;
*/
class ResponseFeed
{

  function getResponseFeed($feedId , $feedType)
  {

    include_once ('.config.inc.php');
    include_once('../../envioEmail.php');

    $resposta = false;
    while ($resposta != true) {

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

      $parameters = array (
        'Merchant' => MERCHANT_ID,
        'FeedSubmissionId' => $feedId,
        'FeedSubmissionResult' => @fopen('php://memory', 'rw+'),
      //'MWSAuthToken' => '<MWS Auth Token>', // Optional
        );

      $request = new MarketplaceWebService_Model_GetFeedSubmissionResultRequest($parameters);

      $request = new MarketplaceWebService_Model_GetFeedSubmissionResultRequest();
      $request->setMerchant(MERCHANT_ID);
      $request->setFeedSubmissionId($feedId);
      $request->setFeedSubmissionResult(@fopen('php://memory', 'rw+'));

      $responseFeed = new ResponseFeed();
      $resposta = $responseFeed->invokeGetFeedSubmissionResult($service, $request, $feedType);
    }
    return $feedId;
  }


  /**
  * Get Feed Submission Result Action Sample
  * retrieves the feed processing report
  *
  * @param MarketplaceWebService_Interface $service instance of MarketplaceWebService_Interface
  * @param mixed $request MarketplaceWebService_Model_GetFeedSubmissionResult or array of parameters
  */
  function invokeGetFeedSubmissionResult(MarketplaceWebService_Interface $service, $request, $feedType) 
  {
    $mensagemEmail= '['.$feedType.']' . "<br>";
    $fileHandle = fopen('php://memory', 'rw+');
    $request->setFeedSubmissionResult($fileHandle);
    try {
      $response = $service->getFeedSubmissionResult($request);
      rewind($fileHandle);
      $responseStr = stream_get_contents($fileHandle);
      $responseXML = new SimpleXMLElement($responseStr);
      foreach ($responseXML->Message as $children) {
        $role = $children->ProcessingReport;

        if ($role->StatusCode != 'Complete') {
          sleep(300);
        }else{

          foreach ($role->ProcessingSummary as  $value) {
            foreach ($value as $key => $value) {
              $mensagemEmail .= "[".$key."] => " .$value ."<br>";
            }
          }

          echo "<br>";
          foreach ($role->Result as  $value) {
           foreach ($value as $key => $value) {
             $mensagemEmail .= $key . ' => ' . $value . '<br>';
             if ($key == 'AdditionalInfo') {
              $mensagemEmail .= '[SKU]' . ' => ' .$value->SKU. '<br>';
             }
           }
          }
          $destinatario = array('Higor' => 'webmaster@polyhousestore.com' , 'Contato' => 'contato@polihouse.com.br', 'Neide' => 'neide@polyhousestore.com', 'Caio' => 'caio@polyhousestore.com', 'Loui' => 'accounts@polyhousestore.com');
          $assunto      = 'Feed Amazon';
          $mensagem     = $mensagemEmail;
          $enviaEmail   = new Email();
          $enviaEmail->enviaEmail($destinatario, $assunto, $mensagem);
          return true;
        }
      }
    }catch (MarketplaceWebService_Exception $ex) {
        /*
        echo("<br>Caught Exception: " . $ex->getMessage() . "\n");
        echo("<br>Response Status Code: " . $ex->getStatusCode() . "\n");
        echo("<br>Error Code: " . $ex->getErrorCode() . "\n");
        echo("<br>Error Type: " . $ex->getErrorType() . "\n");
        if ($ex->getErrorType() == 'Unknown Request ID') {
          return true;
        }
        if ($ex->getErrorType() == 'Request is throttled') {
          sleep(300);
        }
        echo("<br>Request ID: " . $ex->getRequestId() . "\n");
        echo("<br>XML: " . $ex->getXML() . "\n");
        echo("<br>ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\n");
        */
        return false;
    }
  }
}