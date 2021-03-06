/**
 * Autogenerated by Avro
 * 
 * DO NOT EDIT DIRECTLY
 */
package com.x.service.marketplace.message;  
@SuppressWarnings("all")
public class Address extends org.apache.avro.specific.SpecificRecordBase implements org.apache.avro.specific.SpecificRecord {
  public static final org.apache.avro.Schema SCHEMA$ = org.apache.avro.Schema.parse("{\"type\":\"record\",\"name\":\"Address\",\"namespace\":\"com.x.service.marketplace.message\",\"fields\":[{\"name\":\"street1\",\"type\":\"string\"},{\"name\":\"street2\",\"type\":[\"null\",\"string\"]},{\"name\":\"city\",\"type\":\"string\"},{\"name\":\"county\",\"type\":[\"null\",\"string\"]},{\"name\":\"stateOrProvince\",\"type\":\"string\"},{\"name\":\"country\",\"type\":\"string\"},{\"name\":\"postalCode\",\"type\":\"string\"}]}");
  public java.lang.CharSequence street1;
  public java.lang.CharSequence street2;
  public java.lang.CharSequence city;
  public java.lang.CharSequence county;
  public java.lang.CharSequence stateOrProvince;
  public java.lang.CharSequence country;
  public java.lang.CharSequence postalCode;
  public org.apache.avro.Schema getSchema() { return SCHEMA$; }
  // Used by DatumWriter.  Applications should not call. 
  public java.lang.Object get(int field$) {
    switch (field$) {
    case 0: return street1;
    case 1: return street2;
    case 2: return city;
    case 3: return county;
    case 4: return stateOrProvince;
    case 5: return country;
    case 6: return postalCode;
    default: throw new org.apache.avro.AvroRuntimeException("Bad index");
    }
  }
  // Used by DatumReader.  Applications should not call. 
  @SuppressWarnings(value="unchecked")
  public void put(int field$, java.lang.Object value$) {
    switch (field$) {
    case 0: street1 = (java.lang.CharSequence)value$; break;
    case 1: street2 = (java.lang.CharSequence)value$; break;
    case 2: city = (java.lang.CharSequence)value$; break;
    case 3: county = (java.lang.CharSequence)value$; break;
    case 4: stateOrProvince = (java.lang.CharSequence)value$; break;
    case 5: country = (java.lang.CharSequence)value$; break;
    case 6: postalCode = (java.lang.CharSequence)value$; break;
    default: throw new org.apache.avro.AvroRuntimeException("Bad index");
    }
  }
}
