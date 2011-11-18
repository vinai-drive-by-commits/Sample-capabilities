/**
 * Autogenerated by Avro
 * 
 * DO NOT EDIT DIRECTLY
 */
package com.x.product.reviews;  
@SuppressWarnings("all")
public class ProductReviewsCollectionMessage extends org.apache.avro.specific.SpecificRecordBase implements org.apache.avro.specific.SpecificRecord {
  public static final org.apache.avro.Schema SCHEMA$ = org.apache.avro.Schema.parse("{\"type\":\"record\",\"name\":\"ProductReviewsCollectionMessage\",\"namespace\":\"com.x.product.reviews\",\"fields\":[{\"name\":\"Results\",\"type\":{\"type\":\"array\",\"items\":{\"type\":\"record\",\"name\":\"ReviewCollection\",\"fields\":[{\"name\":\"ratingCount\",\"type\":\"int\"},{\"name\":\"average\",\"type\":\"float\"},{\"name\":\"bestRating\",\"type\":[\"null\",\"int\"]},{\"name\":\"worstRating\",\"type\":[\"null\",\"int\"]},{\"name\":\"collection\",\"type\":{\"type\":\"array\",\"items\":{\"type\":\"record\",\"name\":\"IndividualReview\",\"fields\":[{\"name\":\"review\",\"type\":\"string\"},{\"name\":\"by\",\"type\":\"string\"},{\"name\":\"date\",\"type\":\"string\"},{\"name\":\"rating\",\"type\":[\"null\",\"int\"]}]}}}]}}}],\"topic\":\"/product/reviews/search/success\"}");
  public java.util.List<com.x.product.reviews.ReviewCollection> Results;
  public org.apache.avro.Schema getSchema() { return SCHEMA$; }
  // Used by DatumWriter.  Applications should not call. 
  public java.lang.Object get(int field$) {
    switch (field$) {
    case 0: return Results;
    default: throw new org.apache.avro.AvroRuntimeException("Bad index");
    }
  }
  // Used by DatumReader.  Applications should not call. 
  @SuppressWarnings(value="unchecked")
  public void put(int field$, java.lang.Object value$) {
    switch (field$) {
    case 0: Results = (java.util.List<com.x.product.reviews.ReviewCollection>)value$; break;
    default: throw new org.apache.avro.AvroRuntimeException("Bad index");
    }
  }
}
