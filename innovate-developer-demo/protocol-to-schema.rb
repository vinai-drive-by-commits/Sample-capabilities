#!/usr/bin/env ruby

require 'stringio'
require 'rubygems'
require 'avro'
require 'json'
require 'set'

def write_type(buf, schema, delim, written, namespace = nil)
  buf.print delim if delim

  if schema.class == Avro::Schema::UnionSchema
    buf.print '['
    delim = ''
    nullable = false
    schema.schemas.each do |subschema|
      nullable = nullable || (subschema.to_s == '"null"')
      write_type(buf, subschema, delim, written)
      delim = ','
    end
    buf.print ']'
    if nullable
      buf.print ',"default":null'
    end
  elsif schema.class == Avro::Schema::RecordSchema
    if !written.include?(schema.name)
      written.add(schema.name)

      namespace = schema.namespace if schema.namespace

      buf.print "{\"type\":\"record\",\"name\":\"#{schema.name}\", \"version\":\"#{schema.props['version']}\""
      if namespace
        buf.print ",\"namespace\":\"#{namespace}\""
      end
      buf.print ",\"fields\":["
      
      delim = ''
      
      schema.fields.each do |field|
        buf.print delim
        buf.print "{\"name\":\"#{field.name}\",\"type\":"
        write_type(buf, field.type, '', written)
        buf.print '}'
        delim = ','
      end

      buf.print "]}"
    else
      buf.print "\"#{schema.namespace}.#{schema.name}\""
    end
  elsif schema.class == Avro::Schema::ArraySchema
    buf.print "{\"type\":\"array\",\"items\":"

    write_type(buf, schema.items, '', written)

    buf.print "}"
  else
    json = schema.to_json()[1..-2].gsub('"\\"', '"').gsub('\\"', '"')
    buf.print json
  end
end

protocol_file = File.open(ARGV[0])
protocol_text = protocol_file.read

protocol = Avro::Protocol.parse(protocol_text)

protocol.types.each do |type|
  if type.class == Avro::Schema::RecordSchema && type.props['version']
    buf = StringIO.new

    written = Set.new

    p type.name

    namespace = type.namespace ? type.namespace : protocol.namespace

    write_type(buf, type, '', written, namespace)

    buf.rewind

    out = File.open(namespace + '.' + type.name + '.avsc', 'w')
    out.print buf.read
    out.close
  end
end

