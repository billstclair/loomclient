/*
 * Client to the webapp at http://loom.cc/
 * See https://loom.cc/?function=help&topic=grid_tutorial&mode=advanced
 *
 * Requires Java 5 generics.
 * Port to earlier versions by removing <String,String> from the
 * KV class definition.
 */

package cc.loom;

import java.util.LinkedHashMap;
import java.util.Vector;
import java.util.Iterator;
import java.util.StringTokenizer;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.URL;
import java.net.URLConnection;

/**
 * A client interface to the webapp at http://loom.cc
 */
public class LoomClient {

    /**
     * The beginning of the URL to access the server.
     * Defaults to https://loom.cc/
     */
    String url_prefix;              // Who you gonna call?

    /**
     * No-arg constructor. Defaults url_prefix to https://loom.cc/
     */
    public LoomClient() {
        this("https://loom.cc/");
    }

    /**
     * Constructor to explicitly specify the url_prefix.
     * @param prefix The url_prefix
     */
    public LoomClient(String prefix) {
        if (!prefix.endsWith("/")) prefix = prefix + "/";
        url_prefix = prefix;
    }

    /**
     * This is all you really need to call.
     * The functions below are just syntactic sugar for calling get()
     * @param keys Map argument name strings to their value strings
     */
    public KV get(KV keys) {
        return get(keys, null);
    }

    /**
     * Implementation of get(KV) and debugging version
     * @param keys Map argument name strings to their value strings
     * @param urlv index 0 filled on output with the URL string
     */
    public KV get(KV keys, String[] urlv) {
        String url = url(keys);
        if (urlv != null) urlv[0] = url;
        String kv = fetchURLString(url);
        return parsekv(kv);
    }

    /**
     * Buy a grid location
     * @param type the asset type, an ID (32 character hex string)
     * @param location the location to buy, an ID
     * @param usage the usage location to debit, an ID
     */
    public KV buy(String type, String location, String usage) {
        return buy(type, location, usage, null);
    }

    /**
     * Buy a grid location, implementation and debugging version
     * @param type the asset type, an ID (32 character hex string)
     * @param location the location to buy, an ID
     * @param usage the usage location to debit, an ID
     * @param urlv index 0 filled on output with the URL string
     */
    public KV buy(String type, String location, String usage, String[] urlv) {
        KV keys = new KV();
        keys.put("function", "grid");
        keys.put("action", "buy");
        keys.put("type", type);
        keys.put("loc", location);
        keys.put("usage", usage);
        return get(keys, urlv);
  }

    /**
     * Sell a grid location
     * @param type the asset type, an ID (32 character hex string)
     * @param location the location to buy, an ID
     * @param usage the usage location to debit, an ID
     */
    public KV sell(String type, String location, String usage) {
        return sell(type, location, usage, null);
    }

    /**
     * Sell a grid location - implementation and debugging version
     * @param type the asset type, an ID (32 character hex string)
     * @param location the location to buy, an ID
     * @param usage the usage location to debit, an ID
     * @param urlv index 0 filled on output with the URL string
     */
    public KV sell(String type, String location, String usage, String[] urlv) {
        KV keys = new KV();
        keys.put("function", "grid");
        keys.put("action", "sell");
        keys.put("type", type);
        keys.put("loc", location);
        keys.put("usage", usage);
        return get(keys, urlv);
    }

    /**
     * Change the issuer of a grid location, implementation and debugging version
     * @param type the asset type, an ID (32 character hex string)
     * @param orig the origin issuer location, an ID
     * @param dest the destination issuer location, an ID
     */
    public KV issuer(String type, String orig, String dest) {
        return issuer(type, orig, dest, null);
    }

    /**
     * Change the issuer of a grid location, implementation and debugging version
     * @param type the asset type, an ID (32 character hex string)
     * @param orig the origin issuer location, an ID
     * @param dest the destination issuer location, an ID
     * @param urlv index 0 filled on output with the URL string
     */
    public KV issuer(String type, String orig, String dest, String[] urlv) {
        KV keys = new KV();
        keys.put("function", "grid");
        keys.put("action", "issuer");
        keys.put("type", type);
        keys.put("orig", orig);
        keys.put("dest", dest);
        return get(keys, urlv);
    }

    /**
     * Return the value of a grid location
     * @param type the asset type, an ID (32 character hex string)
     * @param location the location to read
     */
    public KV touch(String type, String location) {
        return touch(type, location, null);
    }

    /**
     * Return the value of a grid location, implementation and debugging version
     * @param type the asset type, an ID (32 character hex string)
     * @param location the location to read, an ID
     * @param urlv index 0 filled on output with the URL string
     */
    public KV touch(String type, String location, String[] urlv) {
        KV keys = new KV();
        keys.put("function", "grid");
        keys.put("action", "touch");
        keys.put("type", type);
        keys.put("loc", location);
        return get(keys, urlv);
    }

    /**
     * Return the value of a grid location
     * @param type the asset type, an ID (32 character hex string)
     * @param hash the hash of the location to read, a 64 character hex string
     */
    public KV look(String type, String hash) {
        return look(type, hash, null);
    }

    /**
     * Return the value of a grid location, implementation and debugging version
     * @param type the asset type, an ID (32 character hex string)
     * @param hash the hash of the location to read, a 64 character hex string
     * @param urlv index 0 filled on output with the URL string
     */
    public KV look(String type, String hash, String[] urlv) {
        KV keys = new KV();
        keys.put("function", "grid");
        keys.put("action", "look");
        keys.put("type", type);
        keys.put("hash", hash);
        return get(keys, urlv);
    }

    /**
     * Move assets from one location to another
     * @param type the asset type, an ID (32 character hex string)
     * @param qty The quantity to move, a string of decimal digits
     * @param orig the location to move from, an ID
     * @param dest the location to move to, an ID
     */
    public KV move(String type, String qty, String orig, String dest) {
        return move(type, qty, orig, dest, null);
    }

    /**
     * Move assets from one location to another, implementation and debugging version
     * @param type the asset type, an ID (32 character hex string)
     * @param qty The quantity to move, a string of decimal digits
     * @param orig the location to move from, an ID
     * @param dest the location to move to, an ID
     * @param urlv index 0 filled on output with the URL string
     */
    public KV move(String type, String qty, String orig, String dest, String[] urlv) {
        KV keys = new KV();
        keys.put("function", "grid");
        keys.put("action", "move");
        keys.put("type", type);
        keys.put("qty", qty);
        keys.put("orig", orig);
        keys.put("dest", dest);
        return get(keys, urlv);
    }

    /*
     * Archive functions
     */

    /**
     * Buy an archive location
     * @param loc The location to buy
     * @param usage The location of at least one usage token with which to pay
     */
    public KV buyArchive(String loc, String usage) {
        return buyArchive(loc, usage, null);
    }

    /**
     * Buy an archive location, implementation and debugging version
     * @param loc The location to buy
     * @param usage The location of at least one usage token with which to pay
     * @param urlv index 0 filled on output with the URL string
     */
    public KV buyArchive(String loc, String usage, String[] urlv) {
        KV keys = new KV();
        keys.put("function", "archive");
        keys.put("action", "buy");
        keys.put("loc", loc);
        keys.put("usage", usage);
        return get(keys, urlv);
    }

    /**
     * Sell an archive location.
     * The location must be empty. Write "" to it before selling.
     * @param loc The location to sell
     * @param usage The location into which to put a usage token
     */
    public KV sellArchive(String loc, String usage) {
        return sellArchive(loc, usage, null);
    }

    /**
     * Sell an archive location, implementation and debugging version
     * The location must be empty. Write "" to it before selling.
     * @param loc The location to sell
     * @param usage The location into which to put a usage token.
     * @param urlv index 0 filled on output with the URL string
     */
    public KV sellArchive(String loc, String usage, String[] urlv) {
        KV keys = new KV();
        keys.put("function", "archive");
        keys.put("action", "sell");
        keys.put("loc", loc);
        keys.put("usage", usage);
        return get(keys, urlv);
    }

    /**
     * Touch an archive location.
     * @param loc The location to touch
     */
    public KV touchArchive(String loc) {
        return touchArchive(loc, null);
    }

    /**
     * Touch an archive location, implementation and debugging version
     * @param loc The location to touch
     * @param urlv index 0 filled on output with the URL string
     */
    public KV touchArchive(String loc, String[] urlv) {
        KV keys = new KV();
        keys.put("function", "archive");
        keys.put("action", "touch");
        keys.put("loc", loc);
        return get(keys, urlv);
    }

    /**
     * Look at an archive location, using its location's hash
     * @param hash The hash of the location to look at.
     */
    public KV lookArchive(String hash) {
        return lookArchive(hash, null);
    }

    /**
     * Look at an archive location, using its location's hash.
     * Implementation and debugging version.
     * @param hash The hash of the location to look at.
     * @param urlv index 0 filled on output with the URL string
     */
    public KV lookArchive(String hash, String[] urlv) {
        KV keys = new KV();
        keys.put("function", "archive");
        keys.put("action", "look");
        keys.put("hash", hash);
        return get(keys, urlv);
    }

    /**
     * Write at an archive location.
     * @param loc The location to write
     * @param usage The location of 1 usage token per 16 bytes written (delta from old value).
     * @param content The content to write
     */
    public KV writeArchive(String loc, String usage, String content) {
        return writeArchive(loc, usage, content, null);
    }

    /**
     * Write at an archive location, implementation and debugging version.
     * @param loc The location to write
     * @param usage The location of 1 usage token per 16 bytes written (delta from old value).
     * @param content The content to write
     */
    public KV writeArchive(String loc, String usage, String content, String[] urlv) {
        KV keys = new KV();
        keys.put("function", "archive");
        keys.put("action", "write");
        keys.put("loc", loc);
        keys.put("usage", usage);
        keys.put("content", content);
        return get(keys, urlv);
    }

    /**
     * Return the URL corresponding to a set of keys
     * @param keys the keys
     */
    public String url(KV keys) {
        StringBuffer str = new StringBuffer(url_prefix);
        String delim = "?";
        Iterator i = keys.keySet().iterator();
        while (i.hasNext()) {
            String key = (String)i.next();
            String value = (String)keys.get(key);
            str.append(delim).append(key).append("=").append(urlencode(value));
            delim = "&";
        }
        return str.toString();
    }

    /**
     * Parse the KV string returned by Loom
     * @param kv The KV string
     * @return null if there is no opening left paren on the first line.
     */
    // This needs to un-c-code the return.
    // e.g. "\n" -> newline
    public static KV parsekv(String kv) {
        StringTokenizer tok = new StringTokenizer(kv, "\n");
        boolean first = true;
        KV res = null;
        String key = "";
        String value = "";
        while(tok.hasMoreTokens()) {
            String line = tok.nextToken();
            if (first) {
                if (!line.equals("(")) return res;
                res = new KV();
            }
            first = false;
            if (line.equals(")")) return res;
            if (line.charAt(0) == ':') key = line.substring(1);
            else if (line.charAt(0) == '=') {
                value = unquoteCString(line.substring(1));
                res.put(key, value);
            }
        }
        return res;
    }

    /**
     * CQuote a string. Opposite of unquoteCstring()
     * @param cstring the string to quote
     */
    public static String quoteCString(String cstring) {
        StringBuffer buf = new StringBuffer();
        for (int i=0; i<cstring.length(); i++) {
            char chr = cstring.charAt(i);
            if (chr == '\n') buf.append("\\n");
            else if (chr == '"') buf.append("\\\"");
            else if (chr == '\t') buf.append("\\t");
            else if (chr == '\\') buf.append("\\\\");
            else if (chr < ' ' || chr > '~') {
                buf.append('\\').append(String.format("%03o", (int)chr));
            } else buf.append(chr);
        }
        return buf.toString();
    }

    /**
     * Un-CQuote a string. The opposite of quoteCString()
     * @param cstring The string to unquote.
     */
    public static String unquoteCString(String cstring) {
        StringBuffer buf = new StringBuffer();
        int len = cstring.length();
        int i = 0;
        while (i < len) {
            char chr = cstring.charAt(i);
            if (chr == '\\') {
                i++;
                if (i >= len) {
                    buf.append(chr);
                    break;
                }
                chr = cstring.charAt(i);
                if (chr == 'n') buf.append('\n');
                else if (chr == '"') buf.append('"');
                else if (chr == 't') buf.append('\t');
                else if (chr == '\\') buf.append('\\');
                else if (chr >= '0' && chr <= '9') {
                    if (len < (i + 3)) {
                        buf.append(cstring.substring(i-1));
                        break;
                    }
                    buf.append((char)Integer.parseInt(cstring.substring(i, i+3), 8));
                    i += 2;
                } else buf.append('\\').append(chr);
            } else {
                buf.append(chr);
            }
            i++;
        }
        return buf.toString();
    }

    /**
     * Fetch the contents of a URL as a string
     */
    public static String fetchURLString(String address) {
        //println("URL: " + address);
        char[] buf = new char[4096];
        try {
            URL url = new URL(address);
            URLConnection connection = url.openConnection();
            InputStreamReader in = new InputStreamReader(connection.getInputStream());
            StringBuffer all = new StringBuffer();
            int size = 0;
            while(size >= 0) {
                size = in.read(buf, 0, 4096);
                if (size > 0) all.append(buf, 0, size);
            }
            //println(all.toString());
            return all.toString();
        } catch (Exception e) {
            return "()";
        }
    }

    /**
     * Encode a string for a URL
     */
    public static String urlencode(String str) {
        try {
            return java.net.URLEncoder.encode(str, "UTF-8");
        } catch (java.io.UnsupportedEncodingException e) {
            return str;
        }
    }

    /**
     * I don't like typing System.out.println
     */
    public static void println(String line) {
        System.out.println(line);
    }

    /**
     * Print the usage for the command line app
     */
    public static void usage() {
        println("Usage: LoomClient function args...");
        println("  buy type location usage");
        println("  sell type location usage");
        println("  issuer type orig dest");
        println("  touch type location");
        println("  look type hash");
        println("  move type qty orig dest");
        println("  buyarch loc usage");
        println("  sellarch loc usage");
        println("  toucharch loc");
        println("  lookarch hash");
        println("  writearch loc usage content");
    }

    /**
     * Print a key/value table to stdout
     */
    public static void printKV(KV kv) {
        Iterator keys = kv.keySet().iterator();
        println("(");
        while(keys.hasNext()) {
            String key = (String)keys.next();
            String value = kv.get(key);
            println(":" + quoteCString(key));
            println("=" + quoteCString(value));
        }
        println(")");
    }

    /**
     * A little command line example of using the library.
     */
    public static void main(String[] args) {
        if (args.length < 2) {
            usage(); return;
        }
        String function = args[0];
        LoomClient client = new LoomClient();
        String[] urlv = new String[1];
        KV kv = null;
        if (function.equals("buy") || function.equals("sell")) {
            if (args.length != 4) {
                usage(); return;
            }
            String type = args[1];
            String location = args[2];
            String usage = args[3];
            kv = function.equals("buy") ?
                client.buy(type, location, usage, urlv) :
                client.sell(type, location, usage, urlv);
        } else if (function.equals("issuer")) {
            if (args.length != 4) {
                usage(); return;
            }
            String type = args[1];
            String orig = args[2];
            String dest = args[3];
            kv = client.issuer(type, orig, dest, urlv);
        } else if (function.equals("touch")) {
            if (args.length != 3) {
                usage(); return;
            }
            String type = args[1];
            String location = args[2];
            kv = client.touch(type, location, urlv);
        } else if (function.equals("look")) {
            if (args.length != 3) {
                usage(); return;
            }
            String type = args[1];
            String hash = args[2];
            kv = client.look(type, hash, urlv);
        } else if (function.equals("move")) {
            if (args.length != 5) {
                usage(); return;
            }
            String type = args[1];
            String qty = args[2];
            String orig = args[3];
            String dest = args[4];
            kv = client.move(type, qty, orig, dest, urlv);
        } else if (function.equals("buyarch")) {
            if (args.length != 3) {
                usage(); return;
            }
            String loc = args[1];
            String usage = args[2];
            kv = client.buyArchive(loc, usage, urlv);
        } else if (function.equals("sellarch")) {
            if (args.length != 3) {
                usage(); return;
            }
            String loc = args[1];
            String usage = args[2];
            kv = client.sellArchive(loc, usage, urlv);
        } else if (function.equals("toucharch")) {
            if (args.length != 2) {
                usage(); return;
            }
            String loc = args[1];
            kv = client.touchArchive(loc, urlv);
        } else if (function.equals("lookarch")) {
            if (args.length != 2) {
                usage(); return;
            }
            String hash = args[1];
            kv = client.lookArchive(hash, urlv);
        } else if (function.equals("writearch")) {
            if (args.length != 4) {
                usage(); return;
            }
            String loc = args[1];
            String usage = args[2];
            String content = args[3];
            kv = client.writeArchive(loc, usage, content, urlv);
        } else {
            usage();
        }
        if (kv != null) {
            if (urlv[0] != null) {
                println(urlv[0]);
            }
            printKV(kv);
        }

    }

    /**
     * A LinkedHashMap that maps strings to strings
     */
    public static class KV extends LinkedHashMap<String,String> {
        public KV() {
            super();
        }

        public String get(String key) {
            return super.get(key);
        }

        public String put(String key, String value) {
            return super.put(key, value);
        }

    }
}

// Copyright 2008 Bill St. Clair
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions
// and limitations under the License.

