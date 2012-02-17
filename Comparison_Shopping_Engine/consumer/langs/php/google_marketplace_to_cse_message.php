<?php
include_once 'avro.php';

//
// This program implements both an Google product search marketplace adapter
// and an X.commerce capability that publishes the normalized Google products information to the Fabric
//

// This function gets product information from the Google product search site using the supplied API key and query string
function getGoogleProducts($apikey, $query) 
{
    // Build a URL that retrieves product info from the Google product search site
	// model URL - https://www.googleapis.com/shopping/search/v1/public/products/?key=KEY&q=QUERY&country=US
	$url = "https://www.googleapis.com/shopping/search/v1/public/products/?key=".$apikey."&q=".$query."&country=US";
	
	// Initialize a cURL session
	$ch = curl_init();
	
	// Set cURL transfer options
	curl_setopt($ch, CURLOPT_URL, $url); // URL of the target resource. This URL is the host of the Google product search API + a query string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it directly.
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // stop cURL from verifying the peer's certificate when the https protocol is used
	
	// Send a GET HTTP request containing the URL constructed above
    // If the request is successful, the returned product information is saved in $json.
	$json = curl_exec($ch);
	
	// Close the cURL session
	curl_close($ch);
	
	// Convert the returned product information (which is in JSON format) into an associative array
	$data = json_decode($json, true);
	
	// Return the value for the "items" key in the product info associative array
	return $data["items"];
} // end - getGoogleProducts


// This function plugs the information for each product returned by the Google product search API into 
// a ProductDetails structure. Each such ProductDetails structure is placed in an array.
// Finally, the function builds a CSE message, which consists of the array of ProductDetails structures 
// followed by a constant that the identifies the type of product feed - either Full or Incremental
function convertGoogleDataToCSEMessage($items)
{
	// Create an associative array to hold each cse_product_details structure built by the loop that follows 
	$cse_product_details = array();
	
	// For each Google product search item in the array passed in ...
	foreach ($items as $item) {
		// Assign the value of the product key in the Google item to $google_product
		$google_product = $item["product"];
		
		// Add a condition to handle the case in which inventories exist -> determines sale price
		// Get the first item in the inventory
		$inventory_item = array_shift($google_product["inventories"]);
		$price = $inventory_item["price"];
		$currency = $inventory_item["currency"];
		$availability = ucfirst($inventory_item["availability"]);
		
		// For some products, Google sets the availability field to "Unknown"
		// This value causes Avro encoding to fail due to a conflict with the CSE Avro schema
		// As a workaround, set availablity to InStock in this case.
		if ($availability == "Unknown") { $availability = "InStock"; }
		
		// For some products, Google does not initialized the brand field.
		// This causes Avro encoding to fail due to a conflict with the CSE Avro schema
		// As a workaround, if brand is unitialized, set it to the string "Brand unitialized"
		if (!isset($google_product["brand"])) $google_product["brand"] = "Brand unitialized";
		
		// Populate the cse_product_details array with data for the current product
		$cse_product_detail = array(
				"sku" => $google_product["googleId"],
				"title" => $google_product["title"], 
				"description" => $google_product["description"],
				"manufacturer" => $google_product["author"]["name"], 
				"MPN" => "NA", 
				"GTIN" => "NA", 
				"brand" => $google_product["brand"],
				"category" => "NA", 
				"images" => array(),//$product["images"], 
				"link" => $google_product["link"], 
				"condition"=>ucfirst($google_product["condition"]), 
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
		
		// Push the product info for the current product into the the $cse_product_details array
		array_push($cse_product_details, $cse_product_detail);
	}
	
	// Build the CSE message. 
	// This message is a structure containing an array of ProductDetails structures and 
	// a value that identifies the type of product feed - either Full or Incremental
	$message = array("products" => $cse_product_details, "productFeedType" => "Full");
	
	// Return the CSE message
	return $message;	
} // end - convertGoogleDataToCSEMessage

//
// Main logic
//

$testGoogleAPIKey = "PUT YOUR KEY HERE"; 

// Get a product list from the Google Products server
$product_array = getGoogleProducts($testGoogleAPIKey, "droid");

// Convert the Google product list to a CSE message
$message = convertGoogleDataToCSEMessage($product_array);
echo print_r($message, true);

// Get the CSE Avro schema from a local copy of cse.avpr
// In reality, this file will be hosted on the OCL (Open Commerce Language) website 
// -- $content = file_get_contents("https://ocl.xcommercecloud.com/cse/offer/create/1.0.0");
// $content = file_get_contents("http://localhost/fabric_post/contracts/cse.avpr");
$content = file_get_contents("http://localhost/web/cse_demo/cse.avpr");
if ($content !== false) {
	echo "CSE schema successfully read!\n";	
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

try {
		// Binary-encode and serialize the supplied CSE message using the CSE schema and the supplied encoder
		// The result is stored in the AvroStringIO object $write_io created above
		$datum_writer->write($message, $encoder);
	}
	catch (Exception $e) {
		echo "Message does not adhere to schema!";
		echo "Exception object:" . $e;
	} // end - try block
	
	//
	// Send the CSE message to the Fabric on the topic /cse/offer/create
	//
	
try {
	// Initialize a cURL session
	$ch = curl_init();
	
	// Set the cURL options for this session
	curl_setopt($ch, CURLOPT_URL, "https://localhost:8080/cse/offer/create"); // URL of the target resource. This URL is the host:port of the Fabric, with the topic appended
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // stop cURL from verifying the peer's certificate when the https protocol is used
	curl_setopt($ch, CURLOPT_HEADER, true); // TRUE to include the header in the output
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
	curl_setopt($ch, CURLOPT_TIMEOUT, 10); // maximum number of seconds to allow cURL functions to execute
	curl_setopt($ch, CURLOPT_POST, true); // TRUE to do a regular HTTP POST. This POST is the normal application/x-www-form-urlencoded kind, most commonly used by HTML forms. 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: avro/binary", "Authorization: Bearer QUkAAaM+u42KAGU0d8kb819B9LUtB7G5IWLz//45TKM9au9xlWaen5ZHH1yn5OqlPk+HRQ==","X-XC-SCHEMA-VERSION: 1.0.0", "X-XC-SCHEMA-URI: http://localhost/web/cse_demo/cse.avpr")); // An array of HTTP header fields to set, in the format array('Content-type: text/plain', 'Content-length: 100')
	
	// Add the binary-encoded, serialized CSE message to the HTTP message as the message body
	curl_setopt($ch, CURLOPT_POSTFIELDS, $write_io->string()); // The full data to post in an HTTP "POST" operation.

	// POST the HTTP request to the Fabric and print the response returned by the Fabric
	$response = curl_exec($ch);
	print $response;
	}
	catch (Exception $e) {
		echo "Error POSTing message to Fabric!";
		echo "Exception object:" . $e;
	} // end - try block
	
// end - cse_publisher.php

?>
