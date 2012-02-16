
Comparison shopping engine
=============

This example shows how one can easily take a list of products from a marketplace API like Google and publish it to a comparison shopping engine running via X.commerce. If you are an entrepreneur/developer, it is easy to build a custom comparison shopping engine (CSE). You just need to worry about managing the data (store, pre-processing, information extraction, etc). When you are connected to the X.commerce platform, any merchant can easily send you their data. As a reminder, X.commerce Fabric is a messaging architecture that allows publishing and subscribing to approved topics. In simple terms, a capability is something that sends and receives messages from the X.commerce fabric. The messages have to adhere to approved schemas which can be found in the X.commerce git repo

The example is very simple. A basic CSE capability has been created and registered with the X.commerce sandbox for the sake of this example. Here, our CSE capability will be listening on a topic called /CSE/OFFERS/CREATE . A publishing capability (or merchant) will POST a message with a list of products from Google Marketplace on this topic. The CSE capability, will receive this message, validate if it is following the proper schema, and store it. Once, this operation is successful, you will be able to see a second search bar, that allows you to search for the products that was fetched from Google.

Behind the scenes, these are the steps that are getting executed

1. Google marketplace message is converted to an Avro binary message that implements the CSE contract
2. This message is POSTED to /CSE/OFFERS/CREATE topic, which our example CSE capability is listening to.
3. The CSE capability stores this data and the query string in its database. Storing the query string is not a common thing to do. We do it here just as an illustration and to recreate the same search effect on a local data.
4. Now when the user searches for the same product (using the same query string), it retrieves that data from its local DB.

Demo
-------
A demo can be found here http://xamples.herokuapp.com/


Contributing
------------

1. Fork it.
2. Add your capability. Commit your changes (`git commit -am "Added Awesomeness$
3. Submit a pull request                            
4. Create an [Issue][1] with a link to your branch
