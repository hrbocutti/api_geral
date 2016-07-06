<?php

include_once('geraXml.php');

/**
*
*/
class AdicionaProdutoAmazon
{
	public function adiciona()
	{

		if(isset($_POST['sku']) && $_POST['sku'] != '' ){

			$sku = explode(',', $_POST['sku']);
			$skus = array();
			foreach ($sku as $value) {
				array_push($skus, $value);
			}

			$GeraXML       = new GeraXML();
			$returnProduto = $GeraXML->geraProduto($skus);
			if ($returnProduto != '') {
				header('Location: index.php?successSku=1&feedId='.$returnProduto);
			}else{
				header('Location: index.php?erroSkuEmpty=1');
			}
		}
	}
}

$addProd = new AdicionaProdutoAmazon();
$addProd->adiciona();
