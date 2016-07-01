<?php

include_once("geraXml.php");

/**
* Atualiza Preço e Stock da Amazon
*/
class AtualizaPrecoStock
{

	public function atualizaPreco()
	{

		$GeraXML = new GeraXML;
		$GeraXML->geraPrice();
	}

	public function atualizaStock()
	{
		$GeraXML = new GeraXML;
		$GeraXML->geraStock();
	}

	public function atualizaImg()
	{
		$GeraXML = new GeraXML;
		$GeraXML->geraImg();
	}

	public function freeShipping()
	{
		$GeraXML = new GeraXML;
		$GeraXML->geraFreeShipping();
	}
}

$atualizaStock = new AtualizaPrecoStock();
$atualizaStock->atualizaPreco();
$atualizaStock->atualizaStock();
$atualizaStock->atualizaImg();
$atualizaStock->freeShipping();