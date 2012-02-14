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
import java.util.HashMap;
import java.util.Map;

import com.x.devcamp.common.XFabricPublisher;

public class Auctioneer extends Thread {
    private static final int TEN_SECONDS = 10 * 1000;
    private final Map<CharSequence, AuctionListing> listings = new HashMap<CharSequence, AuctionListing>();
    private AuctionListing activeListing;
    private final XFabricPublisher messagePublisher;

    public Auctioneer(XFabricPublisher publisher) {
        this.messagePublisher = publisher;
    }

    public AuctionListing getListing(CharSequence listingId) throws InvalidListingException {
        if (listings.containsKey(listingId)) {
            return listings.get(listingId);
        }
        throw new InvalidListingException(listingId, listingId + " is not a valid auction");
    }

    public void addListing(AuctionListing auctionListing) {
        auctionListing.setMessagePublisher(this.messagePublisher);
        this.listings.put(auctionListing.getListingId(), auctionListing);
    }

    @Override
    public void run() {
        try {
            while (!listings.isEmpty()) {
                if (this.activeListing == null) {
                    this.activeListing = this.listings.values().iterator().next();
                    this.activeListing.startAuction();
                }
                try {
                    sleep(TEN_SECONDS);
                } catch (InterruptedException e) {
                    //
                }
                long now = System.currentTimeMillis();
                if (now > this.activeListing.getEndTime()) {
                    this.activeListing.endAuction();
                    this.listings.remove(this.activeListing.getListingId());
                    this.activeListing = null;
                } else {
                    this.activeListing.broadcastState();
                }
            }
        } catch (IOException e) {
            e.printStackTrace();
        }
    }
}
