<?php

include_once 'avro.php';

class Xcommerce_InventoryPublisher_Model_Observer {
  public function saveInventoryData($observer) {
    $p = $observer->getEvent()->getProduct();

    // Load the Avro protocol definition
    //shown as an example. we are using the same contract from innovate demo.
    $schema = AvroSchema::parse(file_get_contents("/tmp/innovate_sample.avpr"));
	$datum_writer= new AvroIODatumWriter($schema);
	$write_io = new AvroStringIO();
	$encoder = new AvroIOBinaryEncoder($write_io);
	$attributes = $p->toArray();
	// Set up an item
	
	$message = array("Items" => array(array("sku" => $p->getSku(), "title" => $p->getName(),"currentPrice" => $p->getPrice(), "url" => $p->getProductUrl(), "dealOfTheDay" => $attributes["deal_of_the_day"])) );
    
	$datum_writer->write($message,$encoder);
	$ch = curl_init();

    // The URL is the host:port for the XFabric, plus the topic
    curl_setopt($ch, CURLOPT_URL, "https://localhost:8080/inventory/updated");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    //note that we are defining schema uri ourselves here for the listening capability
    //for a standard contract message this will be automatically created.
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: avro/binary", "Authorization: Bearer QVVUSElELTEA/u4OWAoV2/U2vinS0mC8B22S/ejfKnXRz8tKqtscwB4=", "X-XC-SCHEMA-VERSION: 1.0.0", "X-XC-SCHEMA-URI: http://localhost/Items.avsc"));


    // Our message
    curl_setopt($ch, CURLOPT_POSTFIELDS, $write_io->string());
    $response = curl_exec($ch);

Mage::log($response);

    $error = curl_error($ch);
    $result = array('header' => '',  'body' => '',  'curl_error' => '',
                    'http_code' => '',  'last_url' => '');
    if ( $error != "" ) {
       $result['curl_error'] = $error;
    }
    curl_close($ch);


  }
}
