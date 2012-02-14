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
import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.net.MalformedURLException;
import java.net.URISyntaxException;
import java.util.HashMap;
import java.util.Map;
import java.util.Properties;

import javax.servlet.Servlet;
import javax.servlet.ServletException;
import javax.servlet.http.HttpServlet;
import javax.servlet.http.HttpServletRequest;
import javax.servlet.http.HttpServletResponse;

import org.apache.avro.Schema.Field;
import org.apache.avro.file.DataFileReader;
import org.apache.avro.file.SeekableByteArrayInput;
import org.apache.avro.file.SeekableInput;
import org.apache.avro.generic.GenericDatumReader;
import org.apache.avro.generic.GenericRecord;
import org.mortbay.jetty.Connector;
import org.mortbay.jetty.Server;
import org.mortbay.jetty.handler.ContextHandlerCollection;
import org.mortbay.jetty.nio.SelectChannelConnector;
import org.mortbay.jetty.servlet.ServletHolder;
import org.mortbay.jetty.webapp.WebAppContext;
import org.mortbay.resource.FileResource;

import com.x.devcamp.common.XFabricPublisher;

public class AuctionHouse extends Server {
    private WebAppContext mainContext;

    AuctionHouse(int portNum) throws MalformedURLException, IOException, URISyntaxException {
        mainContext = new WebAppContext();
        mainContext.setContextPath("/");

        ContextHandlerCollection contexts = new ContextHandlerCollection();
        this.setHandler(contexts);
        mainContext.setBaseResource(new FileResource(new File(".").toURI().toURL()));
        contexts.addHandler(mainContext);

        Connector connector = new SelectChannelConnector();
        connector.setPort(portNum);
        addConnector(connector);
        mainContext.setConnectorNames(new String[] { connector.getName() });
    }

    void addServlet(String path, Servlet handler) {
        ServletHolder requestServlet = new ServletHolder(handler);
        mainContext.addServlet(requestServlet, path);
    }

    public static void main(String[] args) throws Exception {
        if (args.length != 2 && args.length != 3) {
            System.err.println("usage: java AuctionHouse xFabricUrl portNum [propsFile]");
            return;
        }
        String xFabricUrl = args[0];
        int portNum = Integer.parseInt(args[1]);
        String propsFile = null;
        if (args.length > 2) {
            propsFile = args[2];
        }

        AuctionHouse server = new AuctionHouse(portNum);

        Properties props = new Properties();
        InputStream propsStream = null;
        if (propsFile == null) {
            propsStream = AuctionHouse.class.getClassLoader().getResourceAsStream("auctionhouse.properties");
        } else {
            propsStream = new FileInputStream(propsFile);
        }
        props.load(propsStream);

        // Loading tenant tokens
        Map<String, String> tenants = new HashMap<String, String>();
        for (Object key : props.keySet()) {
            String keyName = key.toString();
            if (keyName.startsWith("token.tenant.")) {
                String tenantId = keyName.substring("token.tenant.".length());
                String token = props.getProperty(keyName);
                tenants.put(tenantId, token);
            }
        }

        XFabricPublisher publisher = new XFabricPublisher(xFabricUrl, props.getProperty("token.main"), tenants);
        Auctioneer auctioneer = new Auctioneer(publisher);

        server.addServlet("/bid/placed", new BidProcessor(auctioneer, publisher, props.getProperty("token.xfabric")));
        server.addServlet("/message/failed", new MessageLogger());

        // Loading product info
        for (Object key : props.keySet()) {
            String keyName = key.toString();
            if (keyName.startsWith("product.")) {
                String productId = keyName.substring("product.".length());
                String productInfo = props.getProperty(keyName);
                int price = Integer.parseInt(productInfo.substring(0, productInfo.indexOf(",")));
                String description = productInfo.substring(productInfo.indexOf(",") + 1);
                auctioneer.addListing(new AuctionListing(productId, description, price));
            }
        }

        server.start();
        auctioneer.start();
    }

    private static class MessageLogger extends HttpServlet {

        @Override
        protected void doPost(HttpServletRequest req, HttpServletResponse resp) throws ServletException, IOException {
            System.out.println("Topic: " + req.getRequestURI());

            byte[] data = getMessageBody(req);
            GenericDatumReader<GenericRecord> reader = new GenericDatumReader<GenericRecord>();
            SeekableInput in = new SeekableByteArrayInput(data);
            DataFileReader<GenericRecord> dfr = new DataFileReader<GenericRecord>(in, reader);
            GenericRecord result = dfr.next();
            dfr.close();

            System.out.print("{");
            for (Field field : result.getSchema().getFields()) {
                System.out.print(field.name() + ": " + result.get(field.name()) + ", ");
            }
            System.out.println("}");
        }

        private static byte[] getMessageBody(HttpServletRequest request) throws IOException {
            // Attempt to pre-allocate a sufficient buffer
            int length = request.getContentLength();
            if (length < 0) {
                length = 4096;
            }
            ByteArrayOutputStream baos = new ByteArrayOutputStream(length);
            byte[] buffer = new byte[length];
            int n;
            InputStream in = request.getInputStream();
            while ((n = in.read(buffer)) > 0) {
                baos.write(buffer, 0, n);
            }
            return baos.toByteArray();
        }
    }
}
