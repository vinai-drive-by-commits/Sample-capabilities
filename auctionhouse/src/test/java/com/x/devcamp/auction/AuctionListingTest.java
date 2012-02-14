package com.x.devcamp.auction;

import org.apache.avro.util.Utf8;
import org.junit.Assert;
import org.junit.Test;

import com.x.devcamp.common.MessagePublishException;
import com.x.devcamp.common.MessagePublisher;
import com.x.service.marketplace.message.CurrencyAmount;

public class AuctionListingTest {
    private Bid makeBid(double amount) {
        Bid bid = new Bid();
        bid.bidAmount = new CurrencyAmount();
        bid.bidAmount.amount = amount;
        bid.bidAmount.code = new Utf8("USD");
        bid.listingId = "test";
        return bid;
    }

    @Test
    public void testInitialBid() throws Exception {
        AuctionListing listing = new AuctionListing("test", "test", 3);
        MessagePublisher mockPublisher = new MockMessagePublisher();
        listing.setMessagePublisher(mockPublisher);
        listing.startAuction();
        listing.takeBid(makeBid(5), "tester", null);
        Assert.assertEquals(3, listing.getCurrentPrice(), 0.001);
        Assert.assertEquals("tester", listing.getHighBidder());
    }

    @Test
    public void testOutbidSelf() throws Exception {
        AuctionListing listing = new AuctionListing("test", "test", 3);
        MessagePublisher mockPublisher = new MockMessagePublisher();
        listing.setMessagePublisher(mockPublisher);
        listing.startAuction();
        listing.takeBid(makeBid(5), "tester", null);
        listing.takeBid(makeBid(10), "tester", null);
        Assert.assertEquals(3, listing.getCurrentPrice(), 0.001);
        Assert.assertEquals("tester", listing.getHighBidder());
    }

    @Test
    public void testOutbidSomeoneElse() throws Exception {
        AuctionListing listing = new AuctionListing("test", "test", 3);
        MessagePublisher mockPublisher = new MockMessagePublisher();
        listing.setMessagePublisher(mockPublisher);
        listing.startAuction();
        listing.takeBid(makeBid(5), "initial", null);
        listing.takeBid(makeBid(15), "tester", null);
        Assert.assertEquals(5.05, listing.getCurrentPrice(), 0.001);
        Assert.assertEquals("tester", listing.getHighBidder());
    }

    @Test
    public void testBidLowerThanCurrentPrice() throws Exception {
        AuctionListing listing = new AuctionListing("test", "test", 3);
        MessagePublisher mockPublisher = new MockMessagePublisher();
        listing.setMessagePublisher(mockPublisher);
        listing.startAuction();
        listing.takeBid(makeBid(5), "initial", null);
        listing.takeBid(makeBid(1), "tester", null);
        Assert.assertEquals(3, listing.getCurrentPrice(), 0.001);
        Assert.assertEquals("initial", listing.getHighBidder());
    }

    @Test
    public void testBidLowerThanHigh() throws Exception {
        AuctionListing listing = new AuctionListing("test", "test", 5);
        MessagePublisher mockPublisher = new MockMessagePublisher();
        listing.setMessagePublisher(mockPublisher);
        listing.startAuction();
        listing.takeBid(makeBid(10), "initial", null);
        listing.takeBid(makeBid(8), "tester", null);
        Assert.assertEquals(8.05, listing.getCurrentPrice(), 0.001);
        Assert.assertEquals("initial", listing.getHighBidder());
    }

    @Test
    public void testBidAgainstOldListing() throws Exception {
        AuctionListing listing = new AuctionListing("test", "test", 0);
        MessagePublisher mockPublisher = new MockMessagePublisher();
        listing.setMessagePublisher(mockPublisher);
        listing.startAuction();
        listing.endAuction();
        listing.takeBid(makeBid(5), "tester", null);
    }

    @Test
    public void testBidAgainstUnlistedListing() throws Exception {
        AuctionListing listing = new AuctionListing("test", "test", 0);
        MessagePublisher mockPublisher = new MockMessagePublisher();
        listing.setMessagePublisher(mockPublisher);
        listing.takeBid(makeBid(5), "tester", null);
    }

    @Test
    public void testBidInvalidIncrement() throws Exception {
        AuctionListing listing = new AuctionListing("test", "test", 0);
        MessagePublisher mockPublisher = new MockMessagePublisher();
        listing.setMessagePublisher(mockPublisher);
        listing.startAuction();
        listing.takeBid(makeBid(5.12), "tester", null);
    }

    @Test
    public void testBidOnZeroPriceItem() throws Exception {
        AuctionListing listing = new AuctionListing("test", "test", 0);
        MessagePublisher mockPublisher = new MockMessagePublisher();
        listing.setMessagePublisher(mockPublisher);
        listing.startAuction();
        listing.takeBid(makeBid(5), "tester", null);
        Assert.assertEquals(0.05, listing.getCurrentPrice(), 0.001);
        Assert.assertEquals("tester", listing.getHighBidder());

    }

    @Test
    public void testStartAuction() throws Exception {
        AuctionListing listing = new AuctionListing("test", "test", 0);
        MessagePublisher mockPublisher = new MockMessagePublisher();
        listing.setMessagePublisher(mockPublisher);
        listing.startAuction();
        Assert.assertTrue(listing.isStarted());
        Assert.assertTrue(listing.getEndTime() > System.currentTimeMillis());
    }

    @Test
    public void testStartAuctionTwice() throws Exception {
        AuctionListing listing = new AuctionListing("test", "test", 0);
        MessagePublisher mockPublisher = new MockMessagePublisher();
        listing.setMessagePublisher(mockPublisher);
        listing.startAuction();
        listing.startAuction();
        Assert.assertTrue(listing.isStarted());
    }

    @Test
    public void testEndExistingAuction() throws Exception {
        AuctionListing listing = new AuctionListing("test", "test", 0);
        MessagePublisher mockPublisher = new MockMessagePublisher();
        listing.setMessagePublisher(mockPublisher);
        listing.startAuction();
        listing.endAuction();
        Assert.assertTrue(listing.isStarted());
        Assert.assertTrue(listing.getEndTime() <= System.currentTimeMillis());
    }

    @Test
    public void testEndUnlistedAuction() throws Exception {
        AuctionListing listing = new AuctionListing("test", "test", 0);
        MessagePublisher mockPublisher = new MockMessagePublisher();
        listing.setMessagePublisher(mockPublisher);
        listing.endAuction();
        Assert.assertFalse(listing.isStarted());
        Assert.assertEquals(-1, listing.getEndTime());
    }

    @Test
    public void testEndAuctionTwice() throws Exception {
        AuctionListing listing = new AuctionListing("test", "test", 0);
        MessagePublisher mockPublisher = new MockMessagePublisher();
        listing.setMessagePublisher(mockPublisher);
        listing.startAuction();
        listing.endAuction();
        listing.endAuction();
    }

    private static class MockMessagePublisher implements MessagePublisher {

        public String publishMessage(String topic, String tenantId, byte[] data) throws MessagePublishException {
            return null;
        }

        public String publishMessage(String topic, String tenantId, String continuationGuid, byte[] data) throws MessagePublishException {
            return null;
        }
    }
}
