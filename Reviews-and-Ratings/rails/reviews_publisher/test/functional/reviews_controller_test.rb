require 'test_helper'

class ReviewsControllerTest < ActionController::TestCase
  test "should get find" do
    get :find
    assert_response :success
  end

  test "should get search" do
    get :search
    assert_response :success
  end

  test "should get update" do
    get :update
    assert_response :success
  end

end
