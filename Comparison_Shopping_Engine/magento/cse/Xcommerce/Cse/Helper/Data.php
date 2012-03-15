<?php
class Xcommerce_Cse_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function generateAvroCseMessage() {
		$collection = Mage::getModel('catalog/product')
		                        ->getCollection()
		                        ->addAttributeToSelect('*');
		$cse_product_details = array();
		foreach ($collection as $p) {
			//$message = array("Items" => array(array("sku" => $p->getSku(), "title" => $p->getName(),"price" => $p->getPrice())) );
			//Mage::log($p);
			// $message = array("Items" => array(array("sku" => $p->getSku(), "title" => $p->getName(),"price" => $p->getPrice())) );
			// 			Mage::log($message);
			$cse_product_detail = array(
					"sku" => $p->getSku(),
					"title" => $p->getName(), 
					"description" => $p->getDescription(),
					"manufacturer" => "NA", 
					"MPN" => "NA", 
					"GTIN" => "NA", 
					"brand" => "NA",
					"category" => "NA", 
					"images" => array(),//$product["images"], 
					"link" => $p->getUrlPath(), 
					"condition"=>"New", 
					"salePrice" => array("amount" => floatval($p->getPrice()), "code" =>"USD"), 
					"originalPrice" => array("amount" => floatval($p->getPrice()), "code" =>"USD"), 
					"availability" => "InStock", 
					"taxRate" =>array("country" => "US", "region" => "TX", "rate"=> 8.5, "taxShipping" => false), 
					"shipping" => array("country" => "US", "region" => "TX", "service" => "UPS", "price" => array("amount" => 3.99, "code" =>"USD")), 
					"shippingWeight" => floatval($p->getWeight()), 
					"attributes" => array(), 
					"variations" => array(), 
					"offerId" => "NA", 
					"cpc" => array("amount" => 0.0, "code" =>"USD"));

			// Push the product info for the current product into the the $cse_product_details array
			array_push($cse_product_details, $cse_product_detail);
			//Mage::log($cse_product_details);
		}
		$content = file_get_contents("http://workshop.dev/fabric_post/contracts/cse.avpr");
		if ($content == false) {
			echo "Error reading schema!\n";	
		}
		
		// Parse the CSE Avro schema and place results in an AvroSchema object
		$schema = AvroSchema::parse($content);
		
		// Create an AvroIODataWriter object for the supplied AvroSchema. 
		// An AvroIODataWriter object handles schema-specific writing of data to the encoder and
		// ensures that each datum written is consistent with the writer's schema.
		$datum_writer = new AvroIODatumWriter($schema);
		
		// Create an AvroStringIO object - this is an AvroIO wrapper for string I/O
		$write_io = new AvroStringIO();
		
		// Create an AvroIOBinaryEncoder object.
		// This object encodes and writes Avro data to the supplied AvroIO object using Avro binary encoding.
		$encoder = new AvroIOBinaryEncoder($write_io);
		
		// The result is stored in the AvroStringIO object $write_io created above
		$message = array("products" => $cse_product_details, "productFeedType" => "Full");
		
		$datum_writer->write($message, $encoder);
		Mage::log($message);
		return $write_io->string();
	}
}