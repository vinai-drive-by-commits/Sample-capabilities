
class ReviewsController < ApplicationController
  def find
    #capability subscribes to /product/reviews/find
    #bearertoken for the message console.
    #replace it with the one of your tenant
    bearer = "Bearer QVVUSElELTEAzxWGINU0RABDeVFsz7ZuqncyLYbBszk"
    stringwriter = StringIO.new
    
    #get the stored schema from whereever it is..
    schema = Avro::Schema.parse("#{Rails.root}/reviews_sample.avpr")
    
    datumwriter = Avro::IO::DatumWriter.new(schema)
    encoder = Avro::IO::BinaryEncoder.new(stringwriter)
    #create a dummy message
    message = JSON.parse('{"reviews" : [{"ratingCount" : 5, "average" :  3.0, "bestRating" :  5, "worstRating" : 1, "collection" : []}]}')
    datumwriter.write(message,encoder)     
    #change it to fabric endpoint, sandbox url. This is shown as an example.
    HTTParty.post('https://localhost:8080/product/reviews/find/success', {:body => stringwriter.string, :headers => {'Content-Type' => 'avro/binary',  'Authorization' => bearer, 'X-XC-SCHEMA-VERSION' => "1.0.0", 'X-XC-SCHEMA-URI' => 'http://localhost/fabric_post/contracts/reviews_sample.avpr'}})
    render :json => {}.to_json , :status => 200
  end

  def search
    #capability subscribes to /product/reviews/search
    #add your code here similar to above
    
    render :json => {}.to_json , :status => 200
  end

  def update
    #capability subscribes to /product/reviews/update
    #this method should read the review in AVRO format and update it.
    #do something like the following.
    headers = {}
     #validate if the message came from the fabric.
      for header in request.env.select {|k,v| k.match("^HTTP.*") }
          #headers += "," unless i == 0
          if header[0].split('_',2)[1].include?("X_XC") || header[0].split('_',2)[1].include?("AUTHORIZATION")
          end
          headers["#{header[0].split('_',2)[1]}"] = "#{header[1]}"
          #i += 1
       end
       if !headers["X_XC_MESSAGE_GUID"].nil?
         
         #get the schema uri
         file = HTTParty.get("#{headers["X_XC_SCHEMA_URI"]}")
         #let us support custom schema urls
         begin
           schema = Avro::Schema.parse(file.parsed_response.to_s.gsub(/\=\>/,':')) 
           stringreader = StringIO.new(request.body.string)
           decoder = Avro::IO::BinaryDecoder.new(stringreader)
           datumreader = Avro::IO::DatumReader.new(schema)
           read_value = datumreader.read(decoder)
           #do something with the read_value like updating the database 
           #updating here...
           
           #post a confirmation..something like this..
           HTTParty.post('http://localhost:8080/product/reviews/update/success', {:body => {}.to_json, :headers => {'Content-Type' => 'avro/binary',  'Authorization' => bearer, 'X-XC-SCHEMA-VERSION' => "1.0.0", 'X-XC-SCHEMA-URI' => 'http://localhost/fabric_post/contracts/reviews_sample.avpr'}}
         rescue
           #handle errors here
         end

  end

end
