<?php
include_once 'avro.php';
//Google product search adapter.
//Doing a simple product search for some product in the US -
//https://www.googleapis.com/shopping/search/v1/public/products/?key=KEY&q=QUERY&country=US
//
function getGoogleProducts($apikey, $query) 
{
	$url = "https://www.googleapis.com/shopping/search/v1/public/products/?key=".$apikey."&q=".$query."&country=US";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data = json_decode(curl_exec($ch), true);
	return $data["items"];
}

//function takes the data returned by google shopping search api and
//converts it into a CSE message
function convertGoogleDataToCSEMessage($items)
{
	//create a test message for cse
	$products = array();
	foreach ($items as $item) {
		$product = $item["product"];
		//add a condition for if inventories exist -> determines sale price
		//get the first item in the inventory
		$inventory_item = array_shift($product["inventories"]);
		$price = $inventory_item["price"];
		$currency = $inventory_item["currency"];
		$availability = ucfirst($inventory_item["availability"]);
		//from google, some product availability is returned as unknown
		//that will make the schema fail.
		if ($availability == "Unknown") { $availability = "InStock"; }
		
		$ind_product = array("sku" => $product["googleId"],
						"tile" => $product["title"], 
						"description" => $product["description"],
						"manufacturer" => $product["author"]["name"], 
						"MPN" => "NA", 
						"GTIN" => "NA", 
						"brand" => $product["brand"], 
						"category" => "NA", 
						"images" => array(),//$product["images"], 
						"link" => $product["link"], 
						"condition"=>ucfirst($product["condition"]), 
						"salePrice" => array("amount" => $price, "code" =>$currency), 
						"originalPrice" => array("amount" => $price, "code" =>$currency), 
						"availability" => $availability, 
						"taxRate" =>array("country" => "US", "region" => "TX", "rate"=> 8.5, "taxShipping" => false), 
						"shipping" => array("country" => "US", "region" => "TX", "service" => "UPS", "price" => array("amount" => 3.99, "code" =>"USD")), 
						"shippingWeight" => 0.0, 
						"attributes" => array(), 
						"variations" => array(), 
						"offerId" => "NA", 
						"cpc" => array("amount" => 0.0, "code" =>"USD"));
		array_push($products,$ind_product);
		
	}
	$message = array("products" => $products, "productFeedType" => "Full");
	
	//$message = array("products" => array(array("sku" => "123","tile" => "iphone", "description" => "test iphone","manufacturer" => "apple", "MPN" => "123", "GTIN" => "123", "brand" => "apple", "category" => "electronics", "images" => array(), "link" => "", "condition"=>"New", "salePrice" => array("amount" => 500.0, "code" =>"dollars"), "originalPrice" => array("amount" => 500.0, "code" =>"dollars"), "availability" => "InStock", "taxRate" =>array("country" => "US", "region" => "TX", "rate"=> 8.5, "taxShipping" => false), "shipping" => array("country" => "US", "region" => "TX", "service" => "UPS", "price" => array("amount" => 10.0, "code" =>"dollars")), "shippingWeight" => 10.0, "attributes" => array(), "variations" => array(), "offerId" => " ", "cpc" => array("amount" => 500.0, "code" =>"dollars"))), "productFeedType" => "Full");
	return $message;	
}


//$content = file_get_contents("https://ocl.xcommercecloud.com/marketplace/profile/delete/1.0.0");
//this content should be parsed from a hosted url. 
$content = file_get_contents("http://localhost/fabric_post/contracts/cse.avpr");
$testGoogleAPIKey = "YOUR KEY HERE";

if ($content !== false) {
	echo "Successfully parsed schema!\n";	
}

$schema = AvroSchema::parse($content);
$datum_writer= new AvroIODatumWriter($schema);
$write_io = new AvroStringIO();
$encoder = new AvroIOBinaryEncoder($write_io);

//get the google products list
$product_array = getGoogleProducts($testGoogleAPIKey, "iphone");
//convert the google product list to CSE message
$message = convertGoogleDataToCSEMessage($product_array);
echo print_r($message,true);

//create a test message for cse
//$message = array("products" => array(array("sku" => "123","tile" => "iphone", "description" => "test iphone","manufacturer" => "apple", "MPN" => "123", "GTIN" => "123", "brand" => "apple", "category" => "electronics", "images" => array(), "link" => "", "condition"=>"New", "salePrice" => array("amount" => 500.0, "code" =>"dollars"), "originalPrice" => array("amount" => 500.0, "code" =>"dollars"), "availability" => "InStock", "taxRate" =>array("country" => "US", "region" => "TX", "rate"=> 8.5, "taxShipping" => false), "shipping" => array("country" => "US", "region" => "TX", "service" => "UPS", "price" => array("amount" => 10.0, "code" =>"dollars")), "shippingWeight" => 10.0, "attributes" => array(), "variations" => array(), "offerId" => " ", "cpc" => array("amount" => 500.0, "code" =>"dollars"))), "productFeedType" => "Full");
try {
	$datum_writer->write($message,$encoder);
	//send the test message to the local fabric
	$ch = curl_init();
	
	// The URL is the host:port for the XFabric, plus the topic
	curl_setopt($ch, CURLOPT_URL, "https://localhost:8080/cse/offers/create");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_POST, 1);
	//curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: avro/binary", "Authorization: Bearer QUkAAV4C7dkO5NH/c1IGdFeZeaqMbeiZe1yoGp9QHxVrXpMFwdBc0hfiBE+uNVI3nT1vlA==","X-XC-SCHEMA-VERSION: 1.0.0", "X-XC-SCHEMA-URL: http://localhost/fabric_post/contracts/cse.avpr"));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: avro/binary", "Authorization: Bearer QUkAAZORF/BpTsy9+pKy6iFIjPu1Wc+/XKDZQCjve56KgXxpXvdIzTHykSlSbC4bdwsQ1A==","X-XC-SCHEMA-VERSION: 1.0.0", "X-XC-SCHEMA-URI: http://localhost/fabric_post/contracts/cse.avpr"));
	

	// Send the message
	curl_setopt($ch, CURLOPT_POSTFIELDS, $write_io->string());

	$response = curl_exec($ch);
	print $response;
	
} catch (Exception $e) {
	echo "Message does not adhere to schema!";
}




?>
