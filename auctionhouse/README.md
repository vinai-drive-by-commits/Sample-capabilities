
AuctionHouse
=============

This example has been modified from the Innovate 2011 workshop. The bidder class was updated to demonstrate passing Avro schemas by reference, as required by the new versions of the XFabric. To run this example using the X.commerce sandbox, please make sure that this application is hosted on a publicly accessible host.

1. Create the following topics:
/auction/ended 
/auction/started 
/auction/updated 
/bid/accepted 
/bid/extended 
/bid/outbid 
/bid/placed 
/bid/rejected 
/bid/won 
2. Define a capability and register the endpoint URL to a public instance of where the auctionhouse demo is running.
3. Subscribe capability to topics (bid/placed)
4. Register tenants with the capability

Eclipse
-------
If you are running this example locally using Eclipse you can follow the following instructions to place a bid.

Run Bidder placing an initial bid 

1.	Right-click the “Bidder” class > Run As > Java Application
2.	The console will output the usage information
3.	Right-click the “Bidder” class again > Run As > Run Configurations..
4.	In the “Name” field, enter Bidder 1
5.	Click the "Arguments" tab
6.	In the Program Arguments field, enter: bidder1 114 5
Note: Replace ‘114’ with whatever listing ID is currently on the block in your AuctionConsole window.
7.	In the AuctionConsole window, you will see a message indicating that the bid was accepted.

Run Bidder again with another bid

1.	Right-click on the “Bidder” class > Run As > Run Configurations..
2.	Right-click on the "Bidder 1" configuration, and select the Duplicate option
3.	Change the "Name" field to Bidder 2
4.	Click on the "Arguments" tab
5.	In the Program Arguments field, enter: bidder2 114 10
6.	The AuctionConsole window will now show the new bid being accepted and the original bid being outbid.



Contributing
------------

1. Fork it.
2. Add your capability. Commit your changes (`git commit -am "Added Awesomeness$
3. Submit a pull request                            
4. Create an [Issue][1] with a link to your branch
