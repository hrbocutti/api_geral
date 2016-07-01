<?php

include_once("geraXml.php");

/**
* Classe que cria ou deleta produto na amazon 
*/
class ProdutosAmazon
{
	
	function criarProduto()
	{
		$GeraXML       = new GeraXML;
		$returnProduto = $GeraXML->geraProduto();
	}

	public function deletaProdutos()
	{
		$GeraXML = new GeraXML;
		$GeraXML->removeProduct();
	}

}

$produto = new ProdutosAmazon();
$produto->criarProduto();
$produto->deletaProdutos();

