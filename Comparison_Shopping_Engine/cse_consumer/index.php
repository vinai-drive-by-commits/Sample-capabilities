<?php

require __DIR__.'/lib/base.php';
include_once 'avro.php';



F3::route('GET /','home');
	function home() {
		echo "Hello, X.commerce! You should be posting to /cse/offers/create\n";
		
	}
	

F3::route('GET /testphp' ,
	function() {
		echo print_r(get_loaded_extensions(),true);
		echo print_r(phpInfo(),true);
	}
);
	
F3::route('POST /cse/offers/create','cse');
	function cse() {
		//writing a log file to dump our outputs
		$fp = fopen('test.log', 'at');
		
		$headers = getallheaders();
		//get it from X-XC-SCHEMA-URI, the default location
                $content = file_get_contents($headers['X-XC-SCHEMA-URI']);		
		$post_data = file_get_contents("php://input");
		$schema = AvroSchema::parse($content);
		//fwrite($fp, $schema);
		//fwrite($fp, "\n");
		$datum_reader = new AvroIODatumReader($schema);
		$read_io = new AvroStringIO($post_data);
		$decoder = new AvroIOBinaryDecoder($read_io);
		$message = $datum_reader->read($decoder);
		//fwrite($fp, $post_data);
		fwrite($fp, "\n");
		//fwrite($fp, print_r($message, true));
		fwrite($fp, print_r($headers,true));
		
		
		//write to mongodb
		$conn = new Mongo('localhost');
		// access database
	    $db = $conn->cse_data;
	    // access collection
	    $collection = $db->google;
	    // insert a new document
	   $item = $message["products"];
	   $collection->insert($item);
	
		//write to log file for our sake
	   fwrite($fp, print_r($item, true));
	   fwrite($fp,"Inserted document with ID: " . $item['_id']);
	   fclose($fp);
	   // disconnect from server
	   $conn->close();
		
	}
F3::run();

?>
