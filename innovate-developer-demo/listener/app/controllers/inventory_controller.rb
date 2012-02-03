class InventoryController < ApplicationController
  def update  
      for header in request.env.select {|k,v| k.match("^HTTP.*") }
        headers["#{header[0].split('_',2)[1]}"] = "#{header[1]}"
      end
      if !headers["X_XC_MESSAGE_GUID"].nil?
        file = HTTParty.get("#{headers["X_XC_SCHEMA_URI"]}")
        #let us support custom schema urls
          schema = Avro::Schema.parse(file.parsed_response.to_s.gsub(/\=\>/,':')) 
          stringreader = StringIO.new(request.body.string)
          decoder = Avro::IO::BinaryDecoder.new(stringreader)
          datumreader = Avro::IO::DatumReader.new(schema)
          read_value = datumreader.read(decoder)
          read_value["Items"].each do |i|
            #print it to console
            p i
            if i["dealOfTheDay"] == "true"
              g = Koala::Facebook::GraphAPI.new('132108403524260|0ada55050099c7bd421d8fd9.1-100002957378571|TzQd_MATDEidCGP798RByy95Vy8...')
              g.put_wall_post("Deal of the day!\n" + i["title"] + "\n" + i["url"])
            end
          end
      end
      render :json => {}  
  end
end

