<?php

require_once 'avro.php';

class Xcommerce_Cse_Block_ListProducts extends Mage_Adminhtml_Block_Template
{
	protected function _toHtml()
	{
		$message = Mage::helper('cse/data')->generateAvroCseMessage();
		// Initialize a cURL session
		$ch = curl_init();
		try
		{
			// Set the cURL options for this session
			curl_setopt($ch, CURLOPT_URL, "https://localhost:8080/cse/offers/create"); // URL of the target resource. This URL is the host:port of the Fabric, with the topic appended
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // stop cURL from verifying the peer's certificate when the https protocol is used
			curl_setopt($ch, CURLOPT_HEADER, true); // TRUE to include the header in the output
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
			curl_setopt($ch, CURLOPT_TIMEOUT, 10); // maximum number of seconds to allow cURL functions to execute
			curl_setopt($ch, CURLOPT_POST, true); // TRUE to do a regular HTTP POST. This POST is the normal application/x-www-form-urlencoded kind, most commonly used by HTML forms.
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: avro/binary",
				"Authorization: Bearer QUkAAb+C7nO0IUEsnZicHpET77pyJRvdSErTtbrLH23JWCR36/3d5y+qJFU3Nu289k3Ymw==",
				"X-XC-SCHEMA-VERSION: 1.0.0",
				"X-XC-SCHEMA-URI: http://localhost/fabric_post/contracts/cse.avpr")); // An array of HTTP header fields to set, in the format array('Content-type: text/plain', 'Content-length: 100')

			// Add the binary-encoded, serialized CSE message to the HTTP message as the message body
			curl_setopt($ch, CURLOPT_POSTFIELDS, $message); // The full data to post in an HTTP "POST" operation.

			// POST the HTTP request to the Fabric and print the response returned by the Fabric
			$response = curl_exec($ch);
			echo $response;
		}
		catch (Exception $e)
		{
			echo "Error POSTing message to Fabric!";
			echo "Exception object:" . $e;
		} // end - try block
		return "Done!";
	}


}