<?php

include_once("envioEmail.php");
include_once("MarketplaceWebService/Samples/GetFeedSubmissionResultSample.php");

include_once('MarketplaceWebService/Samples/SubmitFeedSample.php');
include_once('helper/constantes.php');
require_once('../app/Mage.php');
umask(0);
Mage::app();

/**
* Gera XML apartir da lista de produtos do magento
* @return XML com os produtos gerados.
*/
class GeraXML
{
	function geraProduto($skuPost)
	{

		$doc = new DomDocument('1.0' , 'utf-8');
		$doc->formatOutput = true;

		$root = $doc->appendChild($doc->createElement('AmazonEnvelope'));
		$root->appendChild($doc->createAttribute('xmlns:xsi'))
		->appendChild($doc->createTextNode('http://www.w3.org/2001/XMLSchema-instance'));
		$root->appendChild($doc->createAttribute('xsi:noNamespaceSchemaLocation'))
		->appendChild($doc->createTextNode('amzn-envelope.xsd'));


		$head = new DOMElement('Header');
		$root->appendChild($head);

		$DocumentVersion = new DOMElement('DocumentVersion','1.01');
		$head->appendChild($DocumentVersion);

		$MerchantIdentifier = new DOMElement('MerchantIdentifier','A147A61KSAHFTB');
		$head->appendChild($MerchantIdentifier);

		$MessageType = new DOMElement('MessageType','Product');
		$root->appendChild($MessageType);

		$PurgeAndReplace = new DOMElement('PurgeAndReplace','false');
		$root->appendChild($PurgeAndReplace);

		#############################################################
		# Definindo uma colletions para pesquisa de produto MAGENTO #
		#############################################################

		//$productSkuString = 'TD920309-01L';
		$date = now('Y-m-d 00:00:00');
		if($skuPost != ''){
			$collection = Mage::getModel('catalog/product')
			->getCollection()
			->addAttributeToSort('created_at', 'DESC')
			//->addAttributeToFilter('updated_at',array('gteq' => $date))
			->addAttributeToFilter('sku', array( 'in' => $skuPost))
			->addAttributeToFilter('amazon_feed' , '1')
			->addAttributeToFilter('status' , '1')
			->addAttributeToSelect('*')->setPageSize(10000);
			$collection->getSelect();

			$indice = 0;
			for ($i=1; $i <= $collection->getLastPageNumber(); $i++) {
				if ($collection->isLoaded()) {
					$collection->clear();
					$collection->setPage($i);
					$collection->setPageSize(10000);
				}

				foreach ($collection as $product) {
					$indice++; //Contador
					//Recebe os produtos
					$sku  		  = $product->getSku();
					$printsku = $sku .',';
					//titulo do produto -> maximo 100 caracteres
					$name         = str_replace('&', '&amp;', $product->getData('name'));
					$condition    = $product->getAttributeText('condition');
					$price 		  = str_replace(',', '',$product->getPrice());
					$explodeDesc  = explode('.', $product->getData('short_description'));
					$description  = substr( $explodeDesc[0] . $explodeDesc[1], 0, 2000);

					//Remove html Special Character
					$Content = preg_replace("/&#?[a-z0-9]{2,8};/i","",$description);
					$res 		  = str_replace("&", " ", strip_tags($Content));

					$upc 		  = $product->getUpc();
					$brand     	  = $product->getAttributeText('manufacturer');
					$manufacturer = $brand;
					$mfrPartNum   = $product->getData('part_number');

					//Dimensões do produto
					$length_Prod = number_format($product->getLength(),2);
					$width_Prod  = number_format($product->getWidth(),2);
					$height_Prod = number_format($product->getHeight(),2);
					$weight_Prod = number_format($product->getWeight(),2);

					//Dimensões de Embalagem
					$length_Pack = $length_Prod;
					$width_Pack  = $width_Prod;
					$height_Pack = $height_Prod;
					$weight_Pack = $weight_Prod;

					$msrp 		 = str_replace(',', '',number_format($product->getMsrp(),2));


					//Inicio Varias Mensagem ( amazon feed )
					$Message = new DOMElement('Message');
					$root->appendChild($Message);

					$MessageID = new DOMElement('MessageID',$indice);
					$Message->appendChild($MessageID);
					$OperationType = new DOMElement('OperationType', op_update);
					$Message->appendChild($OperationType);
					$Product = new DOMElement('Product');
					$Message->appendChild($Product);

					$Sku = new DOMElement('SKU', $sku);
					$Product->appendChild($Sku);

					$StandardProductID = new DOMElement('StandardProductID');
					$Product->appendChild($StandardProductID);

					$asin = $product->getAsin();

					if ($asin != '') {
						$Type = new DOMElement('Type', ASIN);
						$StandardProductID->appendChild($Type);

						$Value = new DOMElement('Value', $asin);
						$StandardProductID->appendChild($Value);
					}else{
						$Type = new DOMElement('Type', UPC);
						$StandardProductID->appendChild($Type);

						$Value = new DOMElement('Value', $upc);
						$StandardProductID->appendChild($Value);
					}

					$Condition = new DOMElement('Condition');
					$Product->appendChild($Condition);

					$itemPackageQuant = $product->getData('item_package_quantity');
					if ($itemPackageQuant != '') {

						$NumberOfItems = new DOMElement('NumberOfItems', $itemPackageQuant);
						$Product->appendChild($NumberOfItems);
					}

					$ConditionType = new DOMElement('ConditionType', $condition);
					$Condition->appendChild($ConditionType);

					$DescriptionData = new DOMElement('DescriptionData');
					$Product->appendChild($DescriptionData);

					$Title = new DOMElement('Title', $name);
					$DescriptionData->appendChild($Title);

					$Brand = new DOMElement('Brand', $brand);
					$DescriptionData->appendChild($Brand);

					$Description = new DOMElement('Description' , $res);
					$DescriptionData->appendChild($Description);

					$ItemDimensions = new DOMElement('ItemDimensions');
					$DescriptionData->appendChild($ItemDimensions);

					//Definindo unidade de Medidas e Peso.
					$Length = new DOMElement('Length' , $length_Prod);
					$ItemDimensions->appendChild($Length);
					$Length->setAttribute('unitOfMeasure', unMedida_IN);

					$Width = new DOMElement('Width' , $width_Prod);
					$ItemDimensions->appendChild($Width);
					$Width->setAttribute('unitOfMeasure', unMedida_IN);

					$Height = new DOMElement('Height' , $height_Prod);
					$ItemDimensions->appendChild($Height);
					$Height->setAttribute('unitOfMeasure', unMedida_IN);

					$Weight = new DOMElement('Weight' , $weight_Prod);
					$ItemDimensions->appendChild($Weight);
					$Weight->setAttribute('unitOfMeasure', unPeso_LB);


					$PackageDimensions = new DOMElement('PackageDimensions');
					$DescriptionData->appendChild($PackageDimensions);

					//Definindo unidade de Medidas e Peso.
					$Length = new DOMElement('Length' , $length_Pack);
					$PackageDimensions->appendChild($Length);
					$Length->setAttribute('unitOfMeasure', unMedida_IN);

					$Width = new DOMElement('Width' , $width_Pack);
					$PackageDimensions->appendChild($Width);
					$Width->setAttribute('unitOfMeasure', unMedida_IN);

					$Height = new DOMElement('Height' , $height_Pack);
					$PackageDimensions->appendChild($Height);
					$Height->setAttribute('unitOfMeasure', unMedida_IN);


					$ShippingWeight = new DOMElement('ShippingWeight' , $weight_Pack);
					$DescriptionData->appendChild($ShippingWeight);
					$ShippingWeight->setAttribute('unitOfMeasure', unPeso_LB);

					$MSRP = new DOMElement('MSRP' , $msrp);
					$DescriptionData->appendChild($MSRP);
					$MSRP->setAttribute('currency', unMoeda_USD);

					$Manufacturer = new DOMElement('Manufacturer' , $manufacturer);
					$DescriptionData->appendChild($Manufacturer);

					$MfrPartNumber = new DOMElement('MfrPartNumber' , $mfrPartNum);
					$DescriptionData->appendChild($MfrPartNumber);

					$catgory = Mage::getModel('catalog/product')->load($product->getId());
					$catName = '';
					$catgory = $product->getCategoryIds();
					foreach ($catgory as $category_id) {
						$_cat = Mage::getModel('catalog/category')->load($category_id) ;
						$catName = $_cat->getData('amazon_category');

					}

					$catUltima = explode('>', $catName);
					$contCat = 0;
					foreach ($catUltima as $categories){
						$contCat++;
					}
					$ItemType = new DOMElement('ItemType', $catUltima[$contCat -  NUM_1]);
					$DescriptionData->appendChild($ItemType);
				}
			}

		}else{
			$collection = Mage::getModel('catalog/product')
			->getCollection()
			->addAttributeToSort('created_at', 'DESC')
			//->addAttributeToFilter('updated_at',array('gteq' => $date))
			->addAttributeToFilter('amazon_feed' , '1')
			->addAttributeToFilter('status' , '1')
			->addAttributeToSelect('*')->setPageSize(10000);
			$collection->getSelect();

			$indice = 0;
			for ($i=1; $i <= $collection->getLastPageNumber(); $i++) {
				if ($collection->isLoaded()) {
					$collection->clear();
					$collection->setPage($i);
					$collection->setPageSize(10000);
				}

				foreach ($collection as $product) {
					$indice++; //Contador
					//Recebe os produtos
					$sku  		  = $product->getSku();
					$printsku = $sku .',';
					//titulo do produto -> maximo 100 caracteres
					$name         = str_replace('&', '&amp;', $product->getData('name'));
					$condition    = $product->getAttributeText('condition');
					$price 		  = str_replace(',', '',$product->getPrice());
					$explodeDesc  = explode('.', $product->getData('short_description'));
					$description  = substr( $explodeDesc[0] . $explodeDesc[1], 0, 2000);

					//Remove html Special Character
					$Content = preg_replace("/&#?[a-z0-9]{2,8};/i","",$description);
					$res 		  = str_replace("&", " ", strip_tags($Content));

					$upc 		  = $product->getUpc();
					$brand     	  = $product->getAttributeText('manufacturer');
					$manufacturer = $brand;
					$mfrPartNum   = $product->getData('part_number');

					//Dimensões do produto
					$length_Prod = number_format($product->getLength(),2);
					$width_Prod  = number_format($product->getWidth(),2);
					$height_Prod = number_format($product->getHeight(),2);
					$weight_Prod = number_format($product->getWeight(),2);

					//Dimensões de Embalagem
					$length_Pack = $length_Prod;
					$width_Pack  = $width_Prod;
					$height_Pack = $height_Prod;
					$weight_Pack = $weight_Prod;

					$msrp 		 = str_replace(',', '',number_format($product->getMsrp(),2));


					//Inicio Varias Mensagem ( amazon feed )
					$Message = new DOMElement('Message');
					$root->appendChild($Message);

					$MessageID = new DOMElement('MessageID',$indice);
					$Message->appendChild($MessageID);
					$OperationType = new DOMElement('OperationType', op_update);
					$Message->appendChild($OperationType);
					$Product = new DOMElement('Product');
					$Message->appendChild($Product);

					$Sku = new DOMElement('SKU', $sku);
					$Product->appendChild($Sku);

					$StandardProductID = new DOMElement('StandardProductID');
					$Product->appendChild($StandardProductID);

					$asin = $product->getAsin();

					if ($asin != '') {
						$Type = new DOMElement('Type', ASIN);
						$StandardProductID->appendChild($Type);

						$Value = new DOMElement('Value', $asin);
						$StandardProductID->appendChild($Value);
					}else{
						$Type = new DOMElement('Type', UPC);
						$StandardProductID->appendChild($Type);

						$Value = new DOMElement('Value', $upc);
						$StandardProductID->appendChild($Value);
					}

					$Condition = new DOMElement('Condition');
					$Product->appendChild($Condition);

					$ConditionType = new DOMElement('ConditionType', $condition);
					$Condition->appendChild($ConditionType);

					$itemPackageQuant = $product->getData('item_package_quantity');
					if ($itemPackageQuant != '') {

						$NumberOfItems = new DOMElement('NumberOfItems', $itemPackageQuant);
						$Product->appendChild($NumberOfItems);
					}


					$DescriptionData = new DOMElement('DescriptionData');
					$Product->appendChild($DescriptionData);

					$Title = new DOMElement('Title', $name);
					$DescriptionData->appendChild($Title);

					$Brand = new DOMElement('Brand', $brand);
					$DescriptionData->appendChild($Brand);

					$Description = new DOMElement('Description' , $res);
					$DescriptionData->appendChild($Description);

					$ItemDimensions = new DOMElement('ItemDimensions');
					$DescriptionData->appendChild($ItemDimensions);

					//Definindo unidade de Medidas e Peso.
					$Length = new DOMElement('Length' , $length_Prod);
					$ItemDimensions->appendChild($Length);
					$Length->setAttribute('unitOfMeasure', unMedida_IN);

					$Width = new DOMElement('Width' , $width_Prod);
					$ItemDimensions->appendChild($Width);
					$Width->setAttribute('unitOfMeasure', unMedida_IN);

					$Height = new DOMElement('Height' , $height_Prod);
					$ItemDimensions->appendChild($Height);
					$Height->setAttribute('unitOfMeasure', unMedida_IN);

					$Weight = new DOMElement('Weight' , $weight_Prod);
					$ItemDimensions->appendChild($Weight);
					$Weight->setAttribute('unitOfMeasure', unPeso_LB);


					$PackageDimensions = new DOMElement('PackageDimensions');
					$DescriptionData->appendChild($PackageDimensions);

					//Definindo unidade de Medidas e Peso.
					$Length = new DOMElement('Length' , $length_Pack);
					$PackageDimensions->appendChild($Length);
					$Length->setAttribute('unitOfMeasure', unMedida_IN);

					$Width = new DOMElement('Width' , $width_Pack);
					$PackageDimensions->appendChild($Width);
					$Width->setAttribute('unitOfMeasure', unMedida_IN);

					$Height = new DOMElement('Height' , $height_Pack);
					$PackageDimensions->appendChild($Height);
					$Height->setAttribute('unitOfMeasure', unMedida_IN);


					$ShippingWeight = new DOMElement('ShippingWeight' , $weight_Pack);
					$DescriptionData->appendChild($ShippingWeight);
					$ShippingWeight->setAttribute('unitOfMeasure', unPeso_LB);

					$MSRP = new DOMElement('MSRP' , $msrp);
					$DescriptionData->appendChild($MSRP);
					$MSRP->setAttribute('currency', unMoeda_USD);

					$Manufacturer = new DOMElement('Manufacturer' , $manufacturer);
					$DescriptionData->appendChild($Manufacturer);

					$MfrPartNumber = new DOMElement('MfrPartNumber' , $mfrPartNum);
					$DescriptionData->appendChild($MfrPartNumber);

					$catgory = Mage::getModel('catalog/product')->load($product->getId());
					$catName = '';
					$catgory = $product->getCategoryIds();
					foreach ($catgory as $category_id) {
						$_cat = Mage::getModel('catalog/category')->load($category_id) ;
						$catName = $_cat->getData('amazon_category');

					}

					$catUltima = explode('>', $catName);
					$contCat = 0;
					foreach ($catUltima as $categories){
						$contCat++;
					}
					$ItemType = new DOMElement('ItemType', $catUltima[$contCat -  NUM_1]);
					$DescriptionData->appendChild($ItemType);
				}
			}
		}

		$dateSave = date("YmdHms");
		$doc->save("log/ProductXML-".$dateSave.".xml");

		$EnviaNovoFeed = new EnviaNovoFeed();
		$feedID = $EnviaNovoFeed->recebeXML($doc->savexml() , '_POST_PRODUCT_DATA_' );
		echo 'Request Feed ID Produto: ' . $feedID;
		echo "<br>";
		$feedType = 'Produto';
		$relatorioFeed = new ResponseFeed();
		$retornoFeed = $relatorioFeed->getResponseFeed($feedID,$feedType);
		return $feedID;
	}

	function geraStock()
	{

		$doc = new DomDocument('1.0' , 'utf-8');
		$doc->formatOutput = true;

		$root = $doc->appendChild($doc->createElement('AmazonEnvelope'));
		$root->appendChild($doc->createAttribute('xmlns:xsi'))
		->appendChild($doc->createTextNode('http://www.w3.org/2001/XMLSchema-instance'));
		$root->appendChild($doc->createAttribute('xsi:noNamespaceSchemaLocation'))
		->appendChild($doc->createTextNode('amzn-envelope.xsd'));


		$head = new DOMElement('Header');
		$root->appendChild($head);

		$DocumentVersion = new DOMElement('DocumentVersion','1.01');
		$head->appendChild($DocumentVersion);

		$MerchantIdentifier = new DOMElement('MerchantIdentifier','M_SELLER_354577');
		$head->appendChild($MerchantIdentifier);

		$MessageType = new DOMElement('MessageType','Inventory');
		$root->appendChild($MessageType);

		#############################################################
		# Definindo uma colletions para pesquisa de produto MAGENTO #
		#############################################################
		//$productSkuString = 'TD920309-01L';
		$date = now('Y-m-d 00:00:00');
		$productIds = explode(',', $productSkuString);
		$collection = Mage::getModel('catalog/product')
		->getCollection()
		->addAttributeToSort('created_at', 'DESC')
		->addAttributeToFilter('updated_at',array('gteq' => $date))
		//->addAttributeToFilter('sku', array('in' => $productIds))
		->addAttributeToFilter('amazon_feed' , '1')
		->addAttributeToFilter('status' , '1')
		->addAttributeToSelect('*')->setPageSize(10000);
		$collection->getSelect();

		$indice = 0;

		for ($i=1; $i <= $collection->getLastPageNumber(); $i++) {
			if ($collection->isLoaded()) {
				$collection->clear();
				$collection->setPage($i);
				$collection->setPageSize(10000);
			}

			foreach ($collection as $product) {
				$indice++; //Contador

				//Atributos
				$sku        = $product->getData('sku');
				$amazonFlag = $product->getData('amazon_feed');

				$stocklevel = (int)Mage::getModel('cataloginventory/stock_item')
				->loadByProduct($product->getID())->getQty();

				if ($amazonFlag == 0) {
					$stocklevel = 0;
				}
				if ($stocklevel <= 0) {
					$stocklevel = 0;
				}

	            //Inicio Varias Mensagem ( amazon feed )
				$Message = new DOMElement('Message');
				$root->appendChild($Message);

				$MessageID = new DOMElement('MessageID',$indice);
				$Message->appendChild($MessageID);
				$OperationType = new DOMElement('OperationType', op_update);
				$Message->appendChild($OperationType);

				$Inventory = new DOMElement('Inventory');
				$Message->appendChild($Inventory);

				$SKU = new DOMElement('SKU' , $sku);
				$Inventory->appendChild($SKU);


				$Quantity = new DOMElement('Quantity' , $stocklevel);
				$Inventory->appendChild($Quantity);

				/**
				 * @todo pegar handling_time( magento )
				 */

				if ($product->getAttributeText('handling_time')) {
					$handling_time = $product->getAttributeText('handling_time');
					$FulfillmentLatency = new DOMElement('FulfillmentLatency' , $handling_time);
					$Inventory->appendChild($FulfillmentLatency);
				}else{
					$FulfillmentLatency = new DOMElement('FulfillmentLatency' , NUM_5);
					$Inventory->appendChild($FulfillmentLatency);
				}
			}
		}
		$dateSave = date("YmdHms");
		$doc->save("log/ProductStockXML-".$dateSave.".xml");

		$EnviaNovoFeed = new EnviaNovoFeed();
		$feedID = $EnviaNovoFeed->recebeXML($doc->savexml() , '_POST_INVENTORY_AVAILABILITY_DATA_' );
		echo 'Request Feed ID Stock : ' . $feedID;
		echo "<br>";
		$feedType = 'Stock';
		$relatorioFeed = new ResponseFeed();
		$retornoFeed = $relatorioFeed->getResponseFeed($feedID,$feedType);
		return $feedID;
	}

	function geraPrice()
	{
		$doc = new DomDocument('1.0' , 'utf-8');
		$doc->formatOutput = true;

		$root = $doc->appendChild($doc->createElement('AmazonEnvelope'));
		$root->appendChild($doc->createAttribute('xmlns:xsi'))
		->appendChild($doc->createTextNode('http://www.w3.org/2001/XMLSchema-instance'));
		$root->appendChild($doc->createAttribute('xsi:noNamespaceSchemaLocation'))
		->appendChild($doc->createTextNode('amzn-envelope.xsd'));


		$head = new DOMElement('Header');
		$root->appendChild($head);

		$DocumentVersion = new DOMElement('DocumentVersion','1.01');
		$head->appendChild($DocumentVersion);

		$MerchantIdentifier = new DOMElement('MerchantIdentifier','M_SELLER_354577');
		$head->appendChild($MerchantIdentifier);

		$MessageType = new DOMElement('MessageType','Price');
		$root->appendChild($MessageType);

		#############################################################
		# Definindo uma colletions para pesquisa de produto MAGENTO #
		#############################################################
		//$productSkuString = 'TD920309-01L';
		$date = now('Y-m-d 00:00:00');
		$productIds = explode(',', $productSkuString);
		$collection = Mage::getModel('catalog/product')
		->getCollection()
		->addAttributeToSort('created_at', 'DESC')
		->addAttributeToFilter('updated_at',array('gteq' => $date))
		//->addAttributeToFilter('sku', array('in' => $productIds))
		->addAttributeToFilter('amazon_feed' , '1')
		->addAttributeToFilter('automate_pricing' , array('eq' => '0'))
		->addAttributeToFilter('status' , '1')
		->addAttributeToSelect('*')->setPageSize(10000);
		$collection->getSelect();

		$indice = 0;
		for ($i=1; $i <= $collection->getLastPageNumber(); $i++) {
			if ($collection->isLoaded()) {
				$collection->clear();
				$collection->setPage($i);
				$collection->setPageSize(10000);
			}
			foreach ($collection as $product) {
				$indice++; //Contador

				//Atributos
				$sku        = $product->getData('sku');
				$price 		= str_replace(',', '', number_format($product->getData('amazon_price'),2));

				//Inicio Varias Mensagem ( amazon feed )
				$Message = new DOMElement('Message');
				$root->appendChild($Message);

				$MessageID = new DOMElement('MessageID',$indice);
				$Message->appendChild($MessageID);
				$OperationType = new DOMElement('OperationType', op_update);
				$Message->appendChild($OperationType);

				$Inventory = new DOMElement('Price');
				$Message->appendChild($Inventory);

				$SKU = new DOMElement('SKU' , $sku);
				$Inventory->appendChild($SKU);


				$Price = new DOMElement('StandardPrice' , $price);
				$Inventory->appendChild($Price);
				$Price->setAttribute('currency', unMoeda_USD);
			}
		}

		$dateSave = date("YmdHms");
		$doc->save("log/ProductPriceXML-".$dateSave.".xml");

		$EnviaNovoFeed = new EnviaNovoFeed();
		$feedID = $EnviaNovoFeed->recebeXML($doc->savexml() , '_POST_PRODUCT_PRICING_DATA_' );
		echo 'Request Feed ID Price : ' . $feedID;
		echo "<br>";
		$feedType = 'Preço';
		$relatorioFeed = new ResponseFeed();
		$retornoFeed = $relatorioFeed->getResponseFeed($feedID,$feedType);
		return $feedID;

	}

	function geraImg()
	{
		$doc = new DomDocument('1.0' , 'utf-8');
		$doc->formatOutput = true;

		$root = $doc->appendChild($doc->createElement('AmazonEnvelope'));
		$root->appendChild($doc->createAttribute('xmlns:xsi'))
		->appendChild($doc->createTextNode('http://www.w3.org/2001/XMLSchema-instance'));
		$root->appendChild($doc->createAttribute('xsi:noNamespaceSchemaLocation'))
		->appendChild($doc->createTextNode('amzn-envelope.xsd'));


		$head = new DOMElement('Header');
		$root->appendChild($head);

		$DocumentVersion = new DOMElement('DocumentVersion','1.01');
		$head->appendChild($DocumentVersion);

		$MerchantIdentifier = new DOMElement('MerchantIdentifier','A147A61KSAHFTB');
		$head->appendChild($MerchantIdentifier);

		$MessageType = new DOMElement('MessageType','ProductImage');
		$root->appendChild($MessageType);

		#############################################################
		# Definindo uma colletions para pesquisa de produto MAGENTO #
		#############################################################
		//$productSkuString = 'TD920309-01L';
		$date = now('Y-m-d 00:00:00');
		$productIds = explode(',', $productSkuString);
		$collection = Mage::getModel('catalog/product')
		->getCollection()
		->addAttributeToSort('created_at', 'DESC')
		->addAttributeToFilter('updated_at',array('gteq' => $date))
		//->addAttributeToFilter('sku', array('in' => $productIds))
		->addAttributeToFilter('amazon_feed' , '1')
		->addAttributeToFilter('status' , '1')
		->addAttributeToSelect('*')->setPageSize(10000);
		$collection->getSelect();
		//$collection->load();

		$indice = 0;
		for ($i=1;$i <= $collection->getLastPageNumber(); $i++) {
			if ($collection->isLoaded()) {
				$collection->clear();
				$collection->setPage($i);
				$collection->setPageSize(10000);
			}
			foreach ($collection as $product) {
				$indice++; //Contador

				$sku                = $product->getSku();
				$productMediaConfig = Mage::getModel('catalog/product_media_config');
				$product_imgUrl     = $productMediaConfig->getMediaUrl($product->getImage());

				$Message = new DOMElement('Message');
				$root->appendChild($Message);

				$MessageID = new DOMElement('MessageID',$indice);
				$Message->appendChild($MessageID);
				$OperationType = new DOMElement('OperationType', op_update);
				$Message->appendChild($OperationType);

				$ProductImg = new DOMElement('ProductImage');
				$Message->appendChild($ProductImg);

				$Sku = new DOMElement('SKU' , $sku);
				$ProductImg->appendChild($Sku);

				$ImageType = new DOMElement('ImageType', 'Main');
				$ProductImg->appendChild($ImageType);

				$ImageLocation = new DOMElement('ImageLocation', $product_imgUrl);
				$ProductImg->appendChild($ImageLocation);

			}
		}
		$dateSave = date("YmdHms");
		$doc->save("log/ProductImgXML-".$dateSave.".xml");

		$EnviaNovoFeed = new EnviaNovoFeed();
		$feedID = $EnviaNovoFeed->recebeXML($doc->savexml() , '_POST_PRODUCT_IMAGE_DATA_' );
		echo 'Request Feed ID Imagem : ' . $feedID;
		echo "<br>";
		$feedType = 'Imagem';
		$relatorioFeed = new ResponseFeed();
		$retornoFeed = $relatorioFeed->getResponseFeed($feedID,$feedType);
		return $feedID;

	}

	function geraFreeShipping()
	{
		$doc = new DomDocument('1.0' , 'utf-8');
		$doc->formatOutput = true;

		$root = $doc->appendChild($doc->createElement('AmazonEnvelope'));
		$root->appendChild($doc->createAttribute('xmlns:xsi'))
		->appendChild($doc->createTextNode('http://www.w3.org/2001/XMLSchema-instance'));
		$root->appendChild($doc->createAttribute('xsi:noNamespaceSchemaLocation'))
		->appendChild($doc->createTextNode('amzn-envelope.xsd'));


		$head = new DOMElement('Header');
		$root->appendChild($head);

		$DocumentVersion = new DOMElement('DocumentVersion','1.01');
		$head->appendChild($DocumentVersion);

		$MerchantIdentifier = new DOMElement('MerchantIdentifier','A147A61KSAHFTB');
		$head->appendChild($MerchantIdentifier);

		$MessageType = new DOMElement('MessageType','Override');
		$root->appendChild($MessageType);

		#############################################################
		# Definindo uma colletions para pesquisa de produto MAGENTO #
		#############################################################
		//$productSkuString = 'TD920309-01L';
		$date = now('Y-m-d 00:00:00');
		$productIds = explode(',', $productSkuString);
		$collection = Mage::getModel('catalog/product')
		->getCollection()
		->addAttributeToSort('created_at', 'DESC')
		->addAttributeToFilter('updated_at',array('gteq' => $date))
		//->addAttributeToFilter('sku', array('in' => $productIds))
		->addAttributeToFilter('amazon_feed' , '1')
		->addAttributeToFilter('status' , '1')
		->addAttributeToSelect('*')->setPageSize(10000);
		$collection->getSelect();
		//$collection->load();

		$indice = 0;
		for ($i=1;$i <= $collection->getLastPageNumber(); $i++) {
			if ($collection->isLoaded()) {
				$collection->clear();
				$collection->setPage($i);
				$collection->setPageSize(10000);
			}
			foreach ($collection as $product) {
				$indice++; //Contador

				$sku                = $product->getSku();

				$Message = new DOMElement('Message');
				$root->appendChild($Message);

				$MessageID = new DOMElement('MessageID',$indice);
				$Message->appendChild($MessageID);

				if($product->getData('free_shipping_amazon') == NUM_1){
					$OperationType = new DOMElement('OperationType',op_update);
					$Message->appendChild($OperationType);
				}else{
					$OperationType = new DOMElement('OperationType',op_delete);
					$Message->appendChild($OperationType);
				}

				$Override = new DOMElement('Override');
				$Message->appendChild($Override);

				$Sku = new DOMElement('SKU', $sku);
				$Override->appendChild($Sku);

				$ShippingOverride = new DOMElement('ShippingOverride');
				$Override->appendChild($ShippingOverride);

				$ShipOption = new DOMElement('ShipOption', 'Std Cont US Street Addr');
				$ShippingOverride->appendChild($ShipOption);


				$Type = new DOMElement('Type', 'Exclusive');
				$ShippingOverride->appendChild($Type);


				$ShipAmount = new DOMElement('ShipAmount', '0.00');
				$ShippingOverride->appendChild($ShipAmount);
				$ShipAmount->setAttribute('currency', unMoeda_USD);
			}
		}
		$dateSave = date("YmdHms");
		$doc->save("log/ProductFreeShipping-".$dateSave.".xml");
		$EnviaNovoFeed = new EnviaNovoFeed();
		$feedID = $EnviaNovoFeed->recebeXML($doc->savexml() , '_POST_PRODUCT_OVERRIDES_DATA_' );
		echo 'Request Feed ID Free Shipping : ' . $feedID;
		echo "<br>";
		$feedType = 'FreeShipping';
		$relatorioFeed = new ResponseFeed();
		$retornoFeed = $relatorioFeed->getResponseFeed($feedID,$feedType);
		return $feedID;
	}

	function removeProduct()
	{
		$doc = new DomDocument('1.0' , 'utf-8');
		$doc->formatOutput = true;

		$root = $doc->appendChild($doc->createElement('AmazonEnvelope'));
		$root->appendChild($doc->createAttribute('xmlns:xsi'))
		->appendChild($doc->createTextNode('http://www.w3.org/2001/XMLSchema-instance'));
		$root->appendChild($doc->createAttribute('xsi:noNamespaceSchemaLocation'))
		->appendChild($doc->createTextNode('amzn-envelope.xsd'));


		$head = new DOMElement('Header');
		$root->appendChild($head);

		$DocumentVersion = new DOMElement('DocumentVersion','1.01');
		$head->appendChild($DocumentVersion);

		$MerchantIdentifier = new DOMElement('MerchantIdentifier','A147A61KSAHFTB');
		$head->appendChild($MerchantIdentifier);

		$MessageType = new DOMElement('MessageType','Product');
		$root->appendChild($MessageType);

		$PurgeAndReplace = new DOMElement('PurgeAndReplace','false');
		$root->appendChild($PurgeAndReplace);

		#############################################################
		# Definindo uma colletions para pesquisa de produto MAGENTO #
		#############################################################
		//$productSkuString = 'TD920309-01L';

		$date = now('Y-m-d 00:00:00');

		$productIds = explode(',', $productSkuString);
		$collection = Mage::getModel('catalog/product')
		->getCollection()
		->addAttributeToFilter('updated_at',array('gteq' => $date))
		//->addAttributeToFilter('sku', array('in' => $productIds))
		->addAttributeToFilter('amazon_feed' , '0')
		->addAttributeToFilter('status' , '1')
		->addAttributeToSelect('*')->setPageSize(100);
		$collection->getSelect();
		//$collection->load();

		$indice = 0;
		for ($i=1; $i <= $collection->getLastPageNumber(); $i++) {
			if ($collection->isLoaded()) {
				$collection->clear();
				$collection->setPage($i);
				$collection->setPageSize(100);
			}

			foreach ($collection as $product) {
				$indice++; //Contador

				//Recebe os produtos
				$sku  		  = $product->getSku();

				//Inicio Varias Mensagem ( amazon feed )
				$Message = new DOMElement('Message');
				$root->appendChild($Message);

				$MessageID = new DOMElement('MessageID',$indice);
				$Message->appendChild($MessageID);

				$OperationType = new DOMElement('OperationType', op_delete);
				$Message->appendChild($OperationType);
				$Product = new DOMElement('Product');
				$Message->appendChild($Product);

				$Sku = new DOMElement('SKU', $sku);
				$Product->appendChild($Sku);
			}
		}

		$dateSave = date("YmdHms");
		$doc->save("log/ProductDelete-".$dateSave.".xml");
		$EnviaNovoFeed = new EnviaNovoFeed();
		$feedID = $EnviaNovoFeed->recebeXML($doc->savexml() , '_POST_PRODUCT_DATA_' );
		echo 'Request Feed ID Delete : ' . $feedID;
		echo "<br>";
		$feedType = 'Delete';
		$relatorioFeed = new ResponseFeed();
		$retornoFeed = $relatorioFeed->getResponseFeed($feedID,$feedType);
		return $feedID;
	}
}

/*
	$GeraXML       = new GeraXML;
	
	$returnPrice   = $GeraXML->geraPrice();

	$returnProduto = $GeraXML->geraProduto();


	$returnPrice   = $GeraXML->geraPrice();

	$returnStock   = $GeraXML->geraStock();

	$GeraXML->geraImg();

*/