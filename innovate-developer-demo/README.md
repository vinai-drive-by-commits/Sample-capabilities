# Demo

# Setup

Start with the following installed:

* Magento (Apache, PHP, MySQL, etc.) and configured
  * Disable caching: System/Cache Management; disable all
  * Enable logging: System/Configuration/Advanced/Developer/Log Settings; enable
* Ruby
* Rails
* Ruby Gems
  * Avro
    * To do encoding in Ruby, also get the modified [Avro gem](https://github.com/xcommerce/X.commerce-Contracts/tree/master/tools)
  * Koala (Facebook library)
* [Modified Avro PHP](https://github.com/xcommerce/X.commerce-Contracts/tree/master/tools) in Magento lib/
* avrotools from the [Java Avro package](http://www.apache.org/dyn/closer.cgi/avro/)
* MongoDB
* XFabric from the [X.commerce Developer Package](https://www.x.com/fabric-download)

# Why?

Why not just call Facebook from the extension?

* The scenario here really represents two different parties (developers)
* You wouldn't need to do anything on the Magento side, as we're integrating a large set of message publications conforming to the X.commerce [contracts](https://github.com/xcommerce/X.commerce-Contracts)
  * But, a developer that wants to do a new integration could do so in Magento
  * Then, you don't need to know the specifics of the receiver(s)
  * This applies to any storefront/merchant experience/etc.
* On the Facebook side (could be integration with any service), you don't need to know about Magento, just the standard contract

# Steps

## Magento setup

### Create a "deal of the day" attribute

1. Go to the Magento dashboard (e.g. http://localhost/index.php/admin/dashboard)
1. Go to Catalog/Attributes/Manage Attributes
1. Click "Add New Attribute"; under Properties, enter (leave others as defaults)
  * Attribute Code: deal_of_the_day
  * Catalog Input Type for Store Owner: Yes/No
1. Under Manage Label/Options, enter
  * Admin: Deal of the Day
  * Default Store View: Deal of the Day
1. Click "Save Attribute"
1. Go to Catalog/Attributes/Manage Attribute Set
1. Edit attribute set "Default"
1. Drag deal_of_the_day into the General group
1. Click "Save Attribute Set"

### Create a product

1. Go to Catalog/Manage Products
1. Click Add Product
1. Use Attribute Set "Default" and Product Type "Simple Product"
1. Click Continue
1. Assign fields:
  * General
    * Name
    * Description
    * Short Description
    * SKU
    * Weight
    * Status (Enabled)
  * Prices
    * Price
  * Inventory
    * Quantity (anything other than 0)
    * Stock Availability (In Stock)
1. Click Save
1. Assign the product to a store (to be able to click through the Facebook posting back to the Magento storefront)


## Create Magento extension

See files under extension/.  To skip these steps, copy the whole directory into your Magento installation with:

        cd extension; tar cf - app | (cd <YOUR MAGENTO DIRECTORY>; tar xf -)

1. Create app/etc/modules/Xcommerce_InventoryPublisher.xml:

        <?xml version="1.0"?>
        <config>
            <modules>
                <Xcommerce_InventoryPublisher>
                    <active>true</active>
                    <codePool>local</codePool>
                    <depends>
                        <Mage_Adminhtml />
                    </depends>
                </Xcommerce_InventoryPublisher>
            </modules>
        </config>

1. Create app/code/local/Xcommerce/InventoryPublisher/Model and app/code/local/Xcommerce/InventoryPublisher/etc
1. Add app/code/local/Xcommerce/InventoryPublisher/etc/config.xml:

        <?xml version="1.0"?>
        <config>
            <modules>
                <Xcommerce_InventoryPublisher>
                    <version>1.0.0.0</version>
                </Xcommerce_InventoryPublisher>
            </modules>
            <global>
                <models>
                    <xcommerce_inventorypublisher>
                        <class>Xcommerce_InventoryPublisher_Model</class>
                    </xcommerce_inventorypublisher>
                </models>
                <!-- events will go here -->
            </global>
        </config>

1. Add event observer (in the spot noted above):

        <events>
            <catalog_product_save_after>
                <observers>
                    <inventorypublisher>
                        <class>xcommerce_inventorypublisher/observer</class>
                        <method>saveInventoryData</method>
                    </inventorypublisher>
                </observers>
            </catalog_product_save_after>
        </events>

1. Create app/code/local/Xcommerce/InventoryPublisher/Model/Observer.php

        <?php
        class Xcommerce_InventoryPublisher_Model_Observer
        {
            public function saveInventoryData($observer) {
                $p = $observer->getEvent()->getProduct();
                Mage::log(var_export($p->getName(), true));
            }
        }

1. Edit a product in the Magento catalog, save it, then take a look at Magento's var/log/system.log
1. Create a simple contract

        @namespace("com.x.product.inventory")
        protocol Inventory {
            record Item {
                string sku;
                string title;
                string currentPrice;
                string url;
                string dealOfTheDay;
            }
            record Items {
                array<Item> items;
            }
        }

1. Compile with avrotools to /tmp/inventory.avpr:

        java -jar avro-tools-1.5.4.jar idl inventory.avdl > /tmp/inventory.avpr

1. Add avro to Observer.php:

        include_once 'avro.php';

1. Encode message

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
        Mage::log($strio->string());

1. Edit/save a product again and check var/log/system.log for the raw message
1. Create a capability in XManager called inventory_publisher
1. Authorize inventory_publisher for, say, merchant2; copy the bearer token (including "Bearer")
1. Create a topic in XManager called /inventory/updated
1. Send message in Observer.php:

        $ch = curl_init();
        
        // The URL is the host:port for the XFabric, plus the topic
        curl_setopt($ch, CURLOPT_URL, /* TOPIC */);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        
        // Our message, Base64 encoded
        curl_setopt($ch, CURLOPT_POSTFIELDS, base64_encode($strio->string()));
        
        // The bearer token proves that this code can send a message for a specific tenant (merchant).
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/plain", "Authorization: <BEARER TOKEN HERE>"/));
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $result = array( 'header' => '',  'body' => '',  'curl_error' => '',  'http_code' => '',  'last_url' => '');
        if ( $error != "" ) {
            $result['curl_error'] = $error;
        }
        curl_close($ch);

1. Fill in XFabric URL and topic: http://localhost:8080/inventory/updated
1. Fill in bearer token
  * Note: in a production system, tokens are transmitted directly to a capability's endpoint by the XManager/XFabric along with the tentant's ID; the capability is responsible for storing the tenant ID and the bearer token and using it for subsequent messaging.  See "Extra Credit", below.

# Create a listener

Listener could be any language that speaks Avro! - Java, Ruby, Python, PHP, Javascript/Node.js.

To skip these steps, just do the first, then:

  cd listener; tar cf - | (cd <YOUR LISTENER DIRECTORY>; tar xf -)

1. Create listener: rails new inventory_listener --skip-bundle
1. Add route to config/routes.rb:

        match '/inventory/update' => 'inventory#update'

1. Add gem 'avro' to Gemfile
1. Add gem 'koala' to Gemfile
1. Create app/controllers/inventory_controller.rb

        class InventoryController < ApplicationController
            def update
                buffer = StringIO.new(Base64.decode64(request.body.read))
                reader = Avro::IO::DatumReader.new()
                dr = Avro::DataFile::Reader.new(buffer, reader)
                dr.each do |x|
                    p x["items"]
                end
                render :json => {}
            end
        end

1. rails server -p 3000
1. Create a capability in XManager called inventory_listener
1. Fill in its endpoint: http://localhost:3000
1. Authorize capability for the same tenant as in the publisher (e.g. merchant2)
  * Note: you'll see a message sent to the inventory_listener's endpoint notifying the capability of the newly authorized tenant
1. In XManager, create a subscription for inventory_listener on /inventory/updated
1. Facebook
  * Hack around SSL certification validation if necessary

            require 'net/http'
            require 'openssl'
            class Net::HTTP
                alias_method :origConnect, :connect
                def connect
                    @ssl_context.verify_mode = OpenSSL::SSL::VERIFY_NONE
                    origConnect
                end
            end

1. Post

        class InventoryController < ApplicationController
            def update
                buffer = StringIO.new(Base64.decode64(request.body.read))
                reader = Avro::IO::DatumReader.new()
                dr = Avro::DataFile::Reader.new(buffer, reader)
                dr.each do |x|
                    x["items"].each do |i|
                      if i["dealOfTheDay"] == "1"
                        g = Koala::Facebook::API.new() # your Facebook auth token goes here (see Facebook docs)
                        g.put_object("me", "feed", :message => "Deal of the day!\n" + i["title"] + "\n" + i["url"])
                      end
                end
                render :json => {}
            end
        end

1. Edit a product in the Magento catalog, save it, then take a look at Magento's var/log/system.log
1. Visit the Facebook page
1. Click the link in the URL to see the product on the Magento (storefront)

* Note: Under normal circumstances, when the merchant signed up for this service (via. say, an app store), they'd be taken through the Facebook OAuth flow that would give the deal announcer permissions (the auth token) to post to their Facebook page.

# Extra Credit: Tenants

1. Remove merchant2 from inventory_listener
1. Add a route:

        match '/xfabric/tenant/updated' => 'tenant#update'

1. Define a new controller

        class TenantController < ApplicationController
            def update
                db = Mongo::Connection.new.db("mydb")
                coll = db.collection("tenants")
                buffer = StringIO.new(request.body.read)
                reader = Avro::IO::DatumReader.new()
                dr = Avro::DataFile::Reader.new(buffer, reader)
        
                dr.each do |x|
                    p "#{x['tenantName']} -> #{x['bearerToken']}"
                    coll.insert({:tenant => x['tenantName'], :token => x['bearerToken']})
                end
        
                render :json => {}
            end
        end

1. Add merchant2 to inventory_listener
1. You'll see the new controller called and the <tenant ID, bearer token> pair saved to MongoDB
