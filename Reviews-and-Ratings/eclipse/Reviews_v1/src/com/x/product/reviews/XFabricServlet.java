package com.x.product.reviews;

import com.x.product.reviews.*;

import java.io.IOException;
import java.net.URISyntaxException;
import java.security.KeyManagementException;
import java.security.NoSuchAlgorithmException;
import java.util.ResourceBundle;

import javax.servlet.ServletConfig;
import javax.servlet.ServletException;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import com.x.xfabric.helper.XFabricMessage;
import com.x.xfabric.helper.XFabricMessageHelper;

public class XFabricServlet extends HttpServlet {

	private static final long serialVersionUID = 1L;

	@Override
	public void init(ServletConfig config) throws ServletException {
		super.init(config);
	}


	protected void doPost(HttpServletRequest request,
			HttpServletResponse response) throws ServletException, IOException {
			
		XFabricMessage message = new XFabricMessage(request);
		String topic = message.getTopicName();
		
		//TODO: Replace the log statement with your own logger
		System.out.println("Received message for topic:" + topic);
		
		//TODO: update to read Bearer Tokens from your preferred location 
		//      (properties file or database or a config file)
		ResourceBundle rb = ResourceBundle.getBundle("capability");
		String bearerToken = rb.getString("bearerToken");
		String fabricBearerToken = rb.getString("fabricBearerToken");
		String tenantBearerToken = rb.getString("tenantBearerToken");
		
		//TODO: Authenticate the message - if the authorization header doesn't match
		//      the expected bearer token, you MUST drop the message and return error.
		//disable the error checking. only fabric token is returned. tenant id is not for self req.
		/*
		if(message.isFabricSystemMessage()){
			if(!message.isAuthorized(fabricBearerToken)){
				System.out.println("***System Message received from the " +
						"Fabric but the Bearer token doesn't match!***");
				System.out.println("Expected Fabric Bearer Token:" + fabricBearerToken);
				System.out.println("Received Fabric Bearer Token:" + message.getBearerToken());
			}
		} else {
			if(!message.isAuthorized(tenantBearerToken)){
				System.out.println("***Message Received from a tenant but " +
						"the Bearer token doesn't match!***");	
				System.out.println("Expected Tenant Bearer Token:" + tenantBearerToken);
				System.out.println("Received Tenant Bearer Token:" + message.getBearerToken());			
			}
		}*/
		
		if (topic.equals("/product/reviews/find")) {
			onProductReviewsFind(message);
		}
		if (topic.equals("/product/reviews/find/success")) {
			onProductReviewsFindSuccess(message);
		}
		if (topic.equals("/product/reviews/search")) {
			onProductReviewsSearch(message);
		}
		if (topic.equals("/product/reviews/search/success")) {
			onProductReviewsSearchSuccess(message);
		}
		if (topic.equals("/product/reviews/update/success")) {
			onProductReviewsUpdateSuccess(message);
		}
		
		//TODO - to send message to XFabric you can use the following helper method
		//int responseCode = XFabricMessageHelper.sendAvroMessage( bearerToken, "<destination-id>", "<topic-name>", <message-record-object>);
	}
	
							
	/**
	 * Handle message received on topic /product/reviews/find
	 */
	protected void onProductReviewsFind(XFabricMessage message) {
	
		//TODO: Auto-generated code - Add application code
				//Return one review
				IndividualReview review = null;
				try {
					/** Step 1. load the items object **/
					review = (IndividualReview) message.getMessage(IndividualReview.SCHEMA$);
					System.out.println("Received items to publish:" + review.toString());

					/** Step 2. Write your code to process the request **/
					
					//TODO - uncomment the following lines to post a message to your twitter account
					//	    NOTE: obtain your twitter api credentials from http://developer.twitter.com
					

					/** Step 3: Send an inventory publish success message back to the publisher **/
					//TODO: update to read Bearer Tokens from your preferred location 
					//      (properties file or database or a config file)
					ResourceBundle rb = ResourceBundle.getBundle("capability");
					String bearerToken = rb.getString("bearerToken");
					XFabricMessageHelper.sendAvroMessage(
							bearerToken,
							null, "/product/reviews/find/success", review);

				} catch (IOException e) {
					// TODO Auto-generated catch block
					e.printStackTrace();
				} catch (KeyManagementException e) {
					// TODO Auto-generated catch block
					e.printStackTrace();
				} catch (NoSuchAlgorithmException e) {
					// TODO Auto-generated catch block
					e.printStackTrace();
				} catch (URISyntaxException e) {
					// TODO Auto-generated catch block
					e.printStackTrace();
				}		
	}
			
	/**
	 * Handle message received on topic /product/reviews/find/success
	 */
	protected void onProductReviewsFindSuccess(XFabricMessage message) {
	
		//TODO: Auto-generated code - Add application code
		
		try {
			System.out.println(message.getMessageAsJsonString());
		} catch (IOException e) {
			e.printStackTrace();
		}		
	}
			
	/**
	 * Handle message received on topic /product/reviews/search
	 */
	protected void onProductReviewsSearch(XFabricMessage message) {
	
		ReviewCollection review = null;
		try {
			/** Step 1. load the items object **/
			review = (ReviewCollection) message.getMessage(ReviewCollection.SCHEMA$);
			System.out.println("Received items to publish:" + review.toString());

			/** Step 2. Write your code to process the request **/
			
			//TODO - uncomment the following lines to post a message to your twitter account
			//	    NOTE: obtain your twitter api credentials from http://developer.twitter.com
			

			/** Step 3: Send an inventory publish success message back to the publisher **/
			//TODO: update to read Bearer Tokens from your preferred location 
			//      (properties file or database or a config file)
			ResourceBundle rb = ResourceBundle.getBundle("capability");
			String bearerToken = rb.getString("bearerToken");
			XFabricMessageHelper.sendAvroMessage(
					bearerToken,
					null, "/product/reviews/search/success", review);

		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		} catch (KeyManagementException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		} catch (NoSuchAlgorithmException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		} catch (URISyntaxException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}				
	}
			
	/**
	 * Handle message received on topic /product/reviews/search/success
	 */
	protected void onProductReviewsSearchSuccess(XFabricMessage message) {
	
		//TODO: Auto-generated code - Add application code
		
		try {
			System.out.println(message.getMessageAsJsonString());
		} catch (IOException e) {
			e.printStackTrace();
		}		
	}
			
	/**
	 * Handle message received on topic /product/reviews/update/success
	 */
	protected void onProductReviewsUpdateSuccess(XFabricMessage message) {
	
		//TODO: Auto-generated code - Add application code
		
		try {
			System.out.println(message.getMessageAsJsonString());
		} catch (IOException e) {
			e.printStackTrace();
		}		
	}
		
}	