<?php

include_once 'avro.php';

class Xcommerce_InventoryPublisher_Model_Observer {
  public function saveInventoryData($observer) {
    $p = $observer->getEvent()->getProduct();

    // Load the Avro protocol definition
    $pr = AvroProtocol::parse(file_get_contents("/tmp/inventory.avpr"));

    // Define our schema
    $s = new AvroUnionSchema(array("Item", "Items"), "com.x.product.inventory", $pr->schemata, true);

    // Set up the message buffer and encoder
    $datum_writer = new AvroIODatumWriter($s);
    $strio = new AvroStringIO();
    $dw = new AvroDataIOWriter($strio, $datum_writer, $s);

    // Set up an item
    $attributes = $p->toArray();
    $item = array('sku' => $p->getSku(),
                  'title' => $p->getName(),
                  'currentPrice' => $p->getPrice(),
                  'url' => $p->getProductUrl(),
                  'dealOfTheDay' => $attributes["deal_of_the_day"]);

    // Write an array (of 1) of items into the message
    $dw->append(array("items" => array($item)));
    $dw->close();

    $ch = curl_init();

    // The URL is the host:port for the XFabric, plus the topic
    curl_setopt($ch, CURLOPT_URL, "http://10.0.2.2:8080/inventory/updated");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/plain", "Authorization: Bearer QVVUSElELTEA/u4OWAoV2/U2vinS0mC8B22S/ejfKnXRz8tKqtscwB4="));


    // Our message, Base64 encoded
    curl_setopt($ch, CURLOPT_POSTFIELDS, base64_encode($strio->string()));

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
