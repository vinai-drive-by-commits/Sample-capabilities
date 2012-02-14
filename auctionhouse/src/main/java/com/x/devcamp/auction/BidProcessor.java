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

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.nio.ByteBuffer;

import javax.servlet.ServletException;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import org.apache.http.HttpStatus;
import org.mortbay.jetty.HttpHeaders;

import com.x.devcamp.common.AvroEncDecoder;
import com.x.devcamp.common.MessagePublishException;
import com.x.devcamp.common.MessagePublisher;
import com.x.error.MessageDeliveryFailed;

public class BidProcessor extends HttpServlet {
    private static final long serialVersionUID = 1L;

    private final Auctioneer auctioneer;
    private final String expectedAuthHeader;
    private final MessagePublisher publisher;

    public BidProcessor(Auctioneer auctioneer, MessagePublisher publisher, String authToken) {
        this.auctioneer = auctioneer;
        this.expectedAuthHeader = "Bearer " + authToken;
        this.publisher = publisher;
    }

    @Override
    protected void doPost(HttpServletRequest req, HttpServletResponse resp) throws ServletException, IOException {
        String authToken = req.getHeader(HttpHeaders.AUTHORIZATION);
        if (!this.expectedAuthHeader.equals(authToken)) {
            resp.sendError(HttpStatus.SC_UNAUTHORIZED, "Invalid Authorization token");
        }
        String tenantId = req.getHeader("X-XC-TENANT-ID");
        String messageGuid = req.getHeader("X-XC-MESSAGE-GUID");
        byte[] messageData = null;
        try {
            messageData = getMessageBody(req);
            Bid bid = AvroEncDecoder.decode(messageData, Bid.SCHEMA$);
            AuctionListing listing = this.auctioneer.getListing(bid.listingId);
            listing.takeBid(bid, tenantId, messageGuid);
        } catch (InvalidListingException e) {
            publishError("Invalid Listing ID: " + e.getListingId(), messageData, messageGuid, tenantId);
        } catch (IOException e) {
            publishError("Error processing bid:" + e.getMessage(), messageData, messageGuid, tenantId);
        }
    }

    private void publishError(String errorCause, byte[] originalMessage, String originalGuid, String tenantId) throws MessagePublishException, IOException {
        MessageDeliveryFailed failMsg = new MessageDeliveryFailed();
        failMsg.errorCause = errorCause;
        failMsg.errorId = 300;
        failMsg.message = ByteBuffer.allocate(originalMessage.length);
        failMsg.message.put(originalMessage);
        failMsg.messageGuid = originalGuid;
        failMsg.topicName = "/bid/placed";
        failMsg.httpStatus = 500;
        this.publisher.publishMessage("/message/failed", tenantId, originalGuid, AvroEncDecoder.encode(failMsg, MessageDeliveryFailed.SCHEMA$));
    }

    private static byte[] getMessageBody(HttpServletRequest request) throws IOException {
        // Attempt to pre-allocate a sufficient buffer
        int length = request.getContentLength();
        if (length < 0) {
            length = 4096;
        }
        ByteArrayOutputStream baos = new ByteArrayOutputStream(length);
        byte[] buffer = new byte[4096];
        int n;
        InputStream in = request.getInputStream();
        while ((n = in.read(buffer)) > 0) {
            baos.write(buffer, 0, n);
        }
        return baos.toByteArray();
    }

}
