/*
Copyright (c) 2011, X.Commerce

All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the 
following conditions are met:

Redistributions of source code must retain the above copyright notice, this list of conditions and the following
disclaimer.  Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the
following disclaimer in the documentation and/or other materials provided with the distribution.  Neither the name of
the nor the names of its contributors may be used to endorse or promote products derived from this software without
specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
package com.x.devcamp.auction;

import java.io.IOException;
import java.math.BigDecimal;
import java.math.RoundingMode;

import org.apache.avro.util.Utf8;

import com.x.devcamp.common.AvroEncDecoder;
import com.x.devcamp.common.MessagePublisher;
import com.x.service.marketplace.message.CurrencyAmount;
import com.x.service.marketplace.message.Listing;
import com.x.service.marketplace.message.ProductDetails;

public class AuctionListing {
    private static final int FIVE_MINUTES = 5 * 60 * 1000;
    private static final BigDecimal NICKEL = new BigDecimal("0.05");
    private Listing listing;
    private Bid highBid;
    private String highBidder;
    private boolean started;
    private long endTime;
    private MessagePublisher messagePublisher;

    public AuctionListing(String listingId, String title, double startPrice) {
        this.listing = new Listing();
        this.listing.xId = new Utf8(listingId);
        this.listing.title = title;
        this.listing.price = new CurrencyAmount();
        this.listing.price.amount = startPrice;
        this.listing.price.code = new Utf8("USD");
        this.listing.product = new ProductDetails();
        this.listing.product.sku = new Utf8(listingId);

        this.highBid = new Bid();
        this.highBid.bidAmount = new CurrencyAmount();
        this.highBid.bidAmount.amount = startPrice;
        this.highBid.bidAmount.code = new Utf8("USD");
        this.highBid.listingId = new Utf8(listingId);

        this.highBidder = null;
        this.started = false;
        this.endTime = -1;
    }

    public void setMessagePublisher(MessagePublisher publisher) {
        this.messagePublisher = publisher;
    }

    public boolean isStarted() {
        return this.started;
    }

    public long getEndTime() {
        return endTime;
    }

    public void startAuction() throws IOException {
        this.started = true;
        this.endTime = System.currentTimeMillis() + FIVE_MINUTES;

        System.out.println("Starting auction: " + this.listing.xId + " -- " + this.listing.title);
        this.messagePublisher.publishMessage("/experimental/auction/started", null, AvroEncDecoder.encode(this.listing, Listing.SCHEMA$));
    }

    public void endAuction() throws IOException {
        if (this.started) {
            this.endTime = System.currentTimeMillis();
            System.out.println("Ending auction: " + this.listing.xId);
            this.messagePublisher.publishMessage("/experimental/auction/ended", null, AvroEncDecoder.encode(this.listing, Listing.SCHEMA$));
            if (this.highBidder != null) {
                System.out.println("Auction " + this.listing.xId + " won by " + this.highBidder);
                this.messagePublisher.publishMessage("/experimental/bid/won", this.highBidder, AvroEncDecoder.encode(this.highBid, Bid.SCHEMA$));
            }
        }
    }

    public void broadcastState() throws IOException {
        this.messagePublisher.publishMessage("/experimental/auction/updated", null, AvroEncDecoder.encode(this.listing, Listing.SCHEMA$));
    }

    public void takeBid(Bid bid, String tenantId, String continuationGuid) throws IOException {
        System.out.println("Processing bid $" + bid.bidAmount.amount + " from " + tenantId);
        CurrencyAmount bidAmount = bid.bidAmount;
        if (!this.started) {
            this.messagePublisher.publishMessage("/experimental/bid/rejected", tenantId, continuationGuid, AvroEncDecoder.encode(bid, Bid.SCHEMA$));
            return;
        }
        if (System.currentTimeMillis() > this.endTime) {
            this.messagePublisher.publishMessage("/experimental/bid/rejected", tenantId, continuationGuid, AvroEncDecoder.encode(bid, Bid.SCHEMA$));
            return;
        }
        // Currency is wrong
        if (!bidAmount.code.equals(this.listing.price.code)) {
            this.messagePublisher.publishMessage("/experimental/bid/rejected", tenantId, continuationGuid, AvroEncDecoder.encode(bid, Bid.SCHEMA$));
            return;
        }
        // Amount isn't in the right increments
        BigDecimal normalizedAmount = new BigDecimal(bidAmount.amount);
        normalizedAmount.setScale(2, RoundingMode.HALF_UP);
        if (normalizedAmount.remainder(NICKEL).compareTo(BigDecimal.ZERO) != 0) {
            this.messagePublisher.publishMessage("/experimental/bid/rejected", tenantId, continuationGuid, AvroEncDecoder.encode(bid, Bid.SCHEMA$));
            return;
        }
        // Bid is not higher than highest bid
        if (bidAmount.amount < this.highBid.bidAmount.amount) {
            this.messagePublisher.publishMessage("/experimental/bid/rejected", tenantId, continuationGuid, AvroEncDecoder.encode(bid, Bid.SCHEMA$));
        }
        // Bid is higher than current high
        if (bidAmount.amount > this.listing.price.amount) {
            // Not changing highBidder, so don't change price
            if (highBidder != null && !highBidder.equals(tenantId)) {
                // New bid is the highest bid and it's a new bidder
                if (bidAmount.amount > this.highBid.bidAmount.amount) {
                    this.listing.price.amount = this.highBid.bidAmount.amount + NICKEL.doubleValue();
                    this.messagePublisher.publishMessage("/experimental/bid/outbid", highBidder, continuationGuid,
                            AvroEncDecoder.encode(this.highBid, Bid.SCHEMA$));
                    this.messagePublisher.publishMessage("/experimental/bid/accepted", tenantId, continuationGuid, AvroEncDecoder.encode(bid, Bid.SCHEMA$));
                    this.highBidder = tenantId;
                    this.highBid.bidAmount = bidAmount;
                } else { // New bid just extends other bid
                    this.listing.price.amount = Math.min(this.highBid.bidAmount.amount, bidAmount.amount + NICKEL.doubleValue());
                    this.messagePublisher.publishMessage("/experimental/bid/extended", highBidder, continuationGuid,
                            AvroEncDecoder.encode(this.highBid, Bid.SCHEMA$));
                }
            } else {
                this.messagePublisher.publishMessage("/experimental/bid/accepted", tenantId, continuationGuid, AvroEncDecoder.encode(bid, Bid.SCHEMA$));
                this.highBidder = tenantId;
                this.highBid.bidAmount = bidAmount;
                this.listing.price.amount = Math.max(this.listing.price.amount, NICKEL.doubleValue());
            }
            this.broadcastState();
        }

    }

    public CharSequence getListingId() {
        return this.listing.xId;
    }

    public double getCurrentPrice() {
        return this.listing.price.amount;
    }

    public String getHighBidder() {
        return this.highBidder;
    }

}
