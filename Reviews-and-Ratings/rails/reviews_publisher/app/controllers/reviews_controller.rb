
class ReviewsController < ApplicationController
  def find
    #capability subscribes to /product/reviews/find
    #bearertoken for the message console.
    #replace it with the one of your tenant
    bearer = "Bearer QVVUSElELTEAzxWGINU0RABDeVFsz7ZuqncyLYbBszk"
    
    writer = Avro::IO::DatumWriter.new(SCHEMA)
    file = File.open('/tmp/reviews.tmp', 'wb')
    dw = Avro::DataFile::Writer.new(file, writer, SCHEMA)
    HTTParty.post('http://localhost:8080/product/reviews/find/success', {:body => File.read(file), :headers => {'Content-Type' => 'application/json',  'Authorization' => bearer}})
    render :json => {}.to_json , :status => 200
  end

  def search
    #capability subscribes to /product/reviews/search
    #add your code here similar to above
    HTTParty.post('http://localhost:8080/product/reviews/search/success', {:body => {}.to_json, :headers => {'Content-Type' => 'application/json',  'Authorization' => bearer}})
    render :json => {}.to_json , :status => 200
  end

  def update
    #capability subscribes to /product/reviews/update
    #this method should read the review in AVRO format and update it.
    #do something like the following.
     buffer = StringIO.new(Base64.decode64(request.body.read))
     reader = Avro::IO::DatumReader.new()
     dr = Avro::DataFile::Reader.new(buffer, reader)
     #do something with the "dr" like upate the global reviews table, etc
     
     HTTParty.post('http://localhost:8080/product/reviews/update/success', {:body => {}.to_json, :headers => {'Content-Type' => 'application/json',  'Authorization' => bearer}}
     
  end

end
