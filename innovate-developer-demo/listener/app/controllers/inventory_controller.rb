class InventoryController < ApplicationController
  def update
    buffer = StringIO.new(Base64.decode64(request.body.read))
    reader = Avro::IO::DatumReader.new()
    dr = Avro::DataFile::Reader.new(buffer, reader)
    dr.each do |x|
      x["items"].each do |i|
        if i["dealOfTheDay"] == "1"
          g = Koala::Facebook::GraphAPI.new('132108403524260|0ada55050099c7bd421d8fd9.1-100002957378571|TzQd_MATDEidCGP798RByy95Vy8')
          g.put_wall_post("Deal of the day!\n" + i["title"] + "\n" + i["url"])
        end
      end
    end
   render :json => {}
  end
end

