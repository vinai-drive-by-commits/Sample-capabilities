<?php

require __DIR__.'/lib/base.php';
include_once 'avro.php';

F3::route('GET /','home');
	function home() {
		echo "Hello, X.commerce! You should be posting to /cse/offer/create\n";
		
	}
	

F3::route('GET /testphp' ,
	function() {
		echo print_r(get_loaded_extensions(),true);
		echo print_r(phpInfo(),true);
	}
);
	
F3::route('POST /cse/offer/create','cse');
function cse() {
		// Open the log file to which to write outputs
		$fp = fopen('test.log', 'at');
		
		// Get all http headers in the received message
		$headers = getallheaders();
		
        // Get the posted message body
        // NOTE: The message body is currently in Avro binary form
		$post_data = file_get_contents("php://input");
		
		// Get the URI of the Avro schema on the OCL server that adheres to the /cse/offer/create contract 
		$schema_uri = $headers['X-XC-SCHEMA-URI'];
		
		// Get the contents of the Avro schema identified by the URI retrieved above
        $content = file_get_contents($schema_uri);

		// Parse the CSE Avro schema and place results in an AvroSchema object
        $schema = AvroSchema::parse($content);
		
		//fwrite($fp, $schema);
		//fwrite($fp, "\n");
		
		// Use Avro to decode and deserialize the binary-encoded message body.
		// The result is the plain text version of the message body
		// The message sender used Avro to binary-encode the text version of the message body before sending the message.
		
		// Create an AvroIODatumReader object for the supplied AvroSchema.
		// An AvroIODatumReader object handles schema-specific reading of data from the decoder and
		// ensures that each datum read is consistent with the reader's schema.		
		$datum_reader = new AvroIODatumReader($schema);
		
		// Create an AvroStringIO object and assign it the encoded message body
		$read_io = new AvroStringIO($post_data);
		
		// Create an AvroIOBinaryDecoder object and assign it the $read_io object
		$decoder = new AvroIOBinaryDecoder($read_io);
		
		// Decode and deserialize the data using the CSE schema and the supplied decoder
		// The data is retrieved from the AvroStringIO object $read_io created above
		// Upon return, $message contains the plain text version of the X.commerce message sent by the publisher
		$message = $datum_reader->read($decoder);
		
		//fwrite($fp, $post_data);
		fwrite($fp, "\n");
		//fwrite($fp, print_r($message, true));
		fwrite($fp, print_r($headers,true));
		
		// Connect to the Mongo server running on your machine
		// NOTE: you must start the Mongo server prior to running this web application 
		$conn = new Mongo('localhost');
		
		// Access the cse_data database 
		// If this database does not exist, Mongo creates it
	    $db = $conn->cse_data;
	    
	    // Access the google collection
	    // If this collection does not exist, Mongo creates it
	    $collection = $db->google;
	    
	    // Insert a new document into the google collection
	    $item = $message["products"];
	    $collection->insert($item);
	
		// Write to log file
	    fwrite($fp, print_r($item, true));
	    fwrite($fp,"Inserted document with ID: " . $item['_id']);
	    fclose($fp);
	   
	    // Disconnect from the MongoDB server
	    $conn->close();
	}
	
F3::run();

?>
