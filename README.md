Sample Capabilities for XFabric
=============

This repository contains list of sample capabilities that are intended to serve as examples for any developers who wish to write capabilities for the X.commerce platform.

You can find more details about the individual capabilities by navigating to the respective folders.

Examples
-------
1. Comparison shopping engine
a. Written in PHP. Utilizes the message contract from https://github.com/xcommerce/X.commerce-Contracts/blob/master/comparisonshoppingengine/src/main/avro/ComparisonShoppingEngine.avdl to simulate a comparison shopping engine. This code uses Google's commerce search API to publish a feed to a comparison shopping engine registered with X.commerce.

2. Auctionhouse Bidder
a. This example is written in Java and was part of the Innovate 2011 workshop and illustrates an auctionhouse. The XFabric message flow is illustrated using bidding messages for any particular item.


Contributing
------------

1. Fork it.
2. Create a branch (`git checkout -b my_changes`). 
3. Add your capability. Commit your changes (`git commit -am "Added Awesomeness"`)
4. Push to the branch (`git push origin my_changes`)
5. Create an [Issue][1] with a link to your branch
6. Enjoy a nice cup of coffee
