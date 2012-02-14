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
package com.x.devcamp.common;

import java.io.IOException;
import java.util.Collections;
import java.util.Map;

import org.apache.http.HttpHeaders;
import org.apache.http.HttpResponse;
import org.apache.http.client.ClientProtocolException;
import org.apache.http.client.HttpClient;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.entity.ByteArrayEntity;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.message.BasicHeader;

public class XFabricPublisher implements MessagePublisher {
    private final String url;
    private final String capabilityToken;
    private final Map<String, String> tenantTokens;
    private final HttpClient client;

    public XFabricPublisher(String url) {
        this(url, null, Collections.EMPTY_MAP);
    }

    public XFabricPublisher(String url, String capToken, Map<String, String> tenantTokens) {
        this.url = url;
        this.capabilityToken = capToken;
        this.tenantTokens = tenantTokens;
        this.client = new DefaultHttpClient();
    }

    public String publishMessage(String topic, String tenant, byte[] data) throws MessagePublishException {
        return publishMessage(topic, tenant, null, data);
    }

    public String publishMessage(String topic, String tenant, String continuationGuid, byte[] data) throws MessagePublishException {
        final HttpPost method = new HttpPost(this.url + topic);
        final String authToken = tenant == null ? this.capabilityToken : tenantTokens.get(tenant);
        method.addHeader(new BasicHeader(HttpHeaders.AUTHORIZATION, (authToken == null ? tenant : authToken)));
        method.addHeader(new BasicHeader(HttpHeaders.CONTENT_TYPE, "avro/binary"));
        method.addHeader(new BasicHeader("X-XC-SCHEMA-VERSION", "1.0"));
        method.addHeader(new BasicHeader("X-XC-SCHEMA-URI", "http://localhost/1.0" + topic));
        if (continuationGuid != null) {
            method.addHeader(new BasicHeader("X-XC-MESSAGE-GUID-CONTINUATION", continuationGuid));
        }
        method.setEntity(new ByteArrayEntity(data));
        try {
            final HttpResponse response = this.client.execute(method);
            if (response.getStatusLine().getStatusCode() != 200) {
                throw new MessagePublishException(response.getStatusLine().toString());
            }
            response.getEntity().getContent().close(); // needed to be able to
                                                       // reuse the connection
            String guid = response.getHeaders("X-XC-MESSAGE-GUID")[0].getValue();
            System.out.println("Message: " + guid);
            return guid;
        } catch (ClientProtocolException e) {
            throw new MessagePublishException(e);
        } catch (IOException e) {
            throw new MessagePublishException(e);
        }
    }

}
