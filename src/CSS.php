<?php

namespace Aelora;

class CSS {

  private static $registered = [];
  private static $queued = [];

  /**
   * Setup the fields. This one just hands off to the init() method 
   * in the JavaScript class. 
   */
  public static function init() {
    JS::init();
  }

  public static function register( $url, $name = false, $version = false, $dependencies = [], $attributes = [], $init = true ) {
    if ( $init ) {
      self::init();
    }
    if ( empty( $name ) ) {
      $name = md5( $name );
    }

    self::$registered[ $name ] = [
        'url' => $url,
        'name' => $name,
        'version' => $version,
        'attributes' => $attributes,
        'dependencies' => $dependencies
    ];
  }

  /**
   * 
   * @param string $name Can either be a registered style by name or a url. Using a 
   *  URL doesn't do any dependency checking. 
   */
  public static function enqueue( $name, $init = true ) {
    if ( $init ) {
      self::init();
    }
    if ( !isset( self::$registered[ $name ] ) ) {
      if ( strpos( $name, '/' ) !== false ) {
        throw new \Exception( $name . ' not found in registered styles and does not appear to be a URL' );
      }
      $to_queue = [
          'url' => $name,
          'name' => md5( $name ),
          'version' => false,
          'attributes' => [],
          'dependencies' => []
      ];
    }
    else {
      $to_queue = self::$registered[ $name ];
    }

    if ( isset( $to_queue[ 'dependencies' ] ) && is_array( $to_queue[ 'dependencies' ] ) && count( $to_queue[ 'dependencies' ] ) > 0 ) {
      foreach ( $to_queue[ 'dependencies' ] as $dep ) {
        if ( !self::is_queued( $dep ) ) {
          self::enqueue( $dep );
        }
      }
    }

    self::$queued[ $name ] = $to_queue;
  }

  public static function dequeue( $name ) {
    self::init();
    if ( isset( self::$queued[ $name ] ) ) {
      unset( self::$queued[ $name ] );
    }
    else if ( isset( self::$queued[ md5( $name ) ] ) ) {
      unset( self::$queued[ md5( $name ) ] );
    }
  }

  public static function write() {
    self::init();
    if ( !empty( self::$queued ) ) {
      foreach ( self::$queued as $css ) {
        $ver = '';
        if ( $css[ 'version' ] !== false ) {
          $ver = (strpos( $css[ 'url' ], '?' ) === false ? '?' : '&') . $css[ 'version' ];
        }
        echo '<link rel="stylesheet" type="text/css" href="' . $css[ 'url' ] . $ver . '"';
        echo!empty( $css[ 'name' ] ) ? ' id="' . $css[ 'name' ] . '"' : '';
        if ( is_array( $css[ 'attributes' ] ) && count( $css[ 'attributes' ] ) > 0 ) {
          foreach ( $css[ 'attributes' ] as $k => $v ) {
            echo ' ' . $k . '="' . htmlentities( $v ) . '"';
          }
        }
        echo '>';
      }
    }
  }

  private static function is_queued( $name ) {
    return isset( self::$queued[ $name ] );
  }

}
