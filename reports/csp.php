<?php
final class CSPHeaders {
  public static $css_nonce = null;
  
  public static function exec() {
    $css_nonce = base64_encode(openssl_random_pseudo_bytes(16));
    
    if ($css_nonce) {
      self::$css_nonce = $css_nonce;
      $css_nonce_str = "; style-src *.4chan.org *.4cdn.org *.4channel.org 'nonce-$css_nonce'";
      
    }
    else {
      self::$css_nonce = '';
      $css_nonce_str = '';
    }
    
    header("Content-Security-Policy: default-src *.4chan.org *.4cdn.org *.4channel.org; font-src data:; frame-ancestors reports.4chan.org$css_nonce_str");
    //header("X-Content-Security-Policy: default-src *.4chan.org *.4cdn.org *.4channel.org; frame-ancestors reports.4chan.org$nonce");
  }
}

CSPHeaders::exec();
