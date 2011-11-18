class FabricController < ApplicationController
  def update
    #This method gets called when a tenant gets added to this capability via the Fabric.
    #NOrmally the authorization has a bearer token, which can use used to handle something like creating a user, 
    #in the local database, etc.
    #For this example, we are not using this.
    render :json => {}.to_json , :status => 200 
  end

end
