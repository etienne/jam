<?php
/*
ENKODER PLUGIN FOR MEDIAWIKI
Obfuscates email addresses and other content by transforming into JavaScript.

The Enkoder is originally authored by Automatic Corp. / Hivelogic in Ruby. It
has been ported to PHP and packaged as a MediaWiki extension by Trevor
Wennblom.

Author: Trevor Wennblom <trevor@corevx.com>
Updated: March 27, 2007
Location:
  http://ninecoldwinters.com/code/enkoder-mediawiki
  
Ruby version:
  http://hivelogic.com/enkoder

License:

Original License for the Ruby version of The Enkoder:

  Copyright (c) 2006, Automatic Corp.
  All rights reserved.

  Redistribution and use in source and binary forms, with or without
  modification, are permitted provided that the following conditions are met:

  	1. Redistributions of source code must retain the above copyright notice,
  	this list of conditions and the following disclaimer.

  	2. Redistributions in binary form must reproduce the above copyright notice,
  	this list of conditions and the following disclaimer in the documentation
  	and/or other materials provided with the distribution.

  	3. Neither the name of AUTOMATIC CORP. nor the names of its contributors
    may be used to endorse or promote products derived from this software
    without specific prior written permission.

  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
  AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
  IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
  ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
  LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
  CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
  SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
  INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
  CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
  ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
  POSSIBILITY OF SUCH DAMAGE.

License for the PHP MediaWiki version of The Enkoder remains the same.

Usage:

- Put enkoder.php and enkoder-mw.php into the 'extensions' folder of your Mediawiki installation.

- Add to 'LocalSettings.php' the following:
    include("extensions/enkoder-mw.php");

- There are two tags added to MediaWiki. The first is '<enkode>' which takes
no attributes and will apply JavaScript obfuscation to the content. The
second is '<enkodemail>' which returns a mailto link. This tag takes the
attributes 'to', 'subject', and 'title'.

- Examples:

  <enkodemail
    to="person@example.com"
    subject="Please advise."
    title="Hover over me">
    Imaginary Person
  </enkodemail>

  <enkodemail to="person@example.com">Email me here</enkodemail>
  
  <enkode>Babel</enkode>
  
	<enkode><>&  &<></enkode>
	
See also:
  http://ninecoldwinters.com/code/enkoder-mediawiki
  http://hivelogic.com/enkoder
  
*/

class Enkode
{
  function __construct()
  {
  }
  
  function enkode( $html, $max_length=1024 )
  {
    $kodes[] = array(
      'php' => create_function( '$s', 'return strrev($s);' ),
      'js' => ";kode=kode.split('').reverse().join('')"
    );

    $kodes[] = array(
      'php' => create_function( '$s',
        '$result = "";
        for($i=0;$i<strlen($s);$i++)
        {
          $b = ord( $s[$i] );
          $b += 3;
          if( $b > 127 )
          {
            $b -= 128;
          }
          $result .= chr( $b );
        }
        return $result;'),
      'js' =>   (
           ";x='';for(i=0;i<kode.length;i++){c=kode.charCodeAt(i)-3;" .
           "if(c<0)c+=128;x+=String.fromCharCode(c)}kode=x")
    );

    $kodes[] = array(
      'php' => create_function( '$s',
        '$x = floor( strlen($s) / 2 ) - 1;
        for($i=0;$i<=$x;$i++)
        {
          $y = $s[ $i * 2 ];
          $z = $s[ ($i * 2) + 1 ];
          $s[ $i * 2 ] = $z;
          $s[ ($i * 2) + 1 ] = $y;
        }
        return $s;'),
      'js' => (
         ";x='';for(i=0;i<(kode.length-1);i+=2){" .
         "x+=kode.charAt(i+1)+kode.charAt(i)}" .
         "kode=x+(i<kode.length?kode.charAt(kode.length-1):'');"
       )
    );

    $kode = "document.write(" . $this->js_dbl_quote($html) . ");";

    if( $max_length <= strlen( $kode ) )
    {
      $max_length = strlen( $kode ) + 1;
    }

    $result = '';
    $code_count = count( $kodes );
    while( strlen( $kode ) < $max_length )
    {
      $idx = rand( 0, $code_count - 1 );
      $kode = $kodes[$idx]['php']($kode);
    
      $kode = "kode=" . $this->js_dbl_quote($kode) . $kodes[$idx]['js'];

      if( strlen( $result ) <= $max_length )
      {
        $js = "var kode=\n" . $this->js_wrap_quote( $this->js_dbl_quote($kode), 79 );
        $js = $js . "\n;var i,c,x;while(eval(kode));";
        $js = "function hivelogic_enkoder(){" . $js . "}hivelogic_enkoder();";
        $js = '<script type="text/javascript">' . "\n/* <![CDATA[ */\n" . $js;
        $js = $js . "\n/* ]]> */\n</script>\n";
        $result = $js;
      }
    }

    return $result;
  }

  function enkode_mail( $email, $link_text, $title_text=null, $subject=null)
  {
    $str = '<a href="mailto:' . $email;
    if( $subject != null )
    {
      $str .= '?subject=' . $subject;
    }
    $str .= '" title="';
    if( $title_text != null )
    {
      $str .= $title_text;
    }
    $str .= '">' . $link_text . '</a>';
    return $this->enkode($str);
  }

  function js_dbl_quote( $str ) {
    return '"' . addcslashes($str, "\\\"\0..\37\177..\377") . '"';
  }

  function js_wrap_quote( $str, $max_line_length )
  {
    $max_line_length -= 3;
    $lineLen = 0;
    $result = '';
    $chunk = '';
    while( strlen( $str ) > 0 )
    {
      if( preg_match("/^\\\[0-7]{3}/", $str) )
      {
        $chunk = substr( $str, 0, 4 );
        $str = substr( $str, 4 );
      } elseif( preg_match("/^\\\./", $str) ) {
        $chunk = substr( $str, 0, 2 );
        $str = substr( $str, 2 );
      } else {
        $chunk = $str[0];
        $str = substr( $str, 1 );
      }
    
      if( ( $lineLen + strlen( $chunk ) ) >= $max_line_length )
      {
        $result = $result . "\"+\n\"";
        $lineLen = 1;
      }
    
      $lineLen += strlen( $chunk );
      $result .= $chunk;
    }
    return $result;
  }

}

?>
