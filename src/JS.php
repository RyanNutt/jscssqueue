<?php

namespace Aelora;

class JS {

  private static $registered = [];
  private static $queued = [];

  public static function register( $url, $name = false, $version = false, $dependencies = [], $attributes = [] ) {
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
   * @param string $name Can either be a registered script by name or a url. Using a 
   *  URL doesn't do any dependency checking. 
   */
  public static function enqueue( $name ) {
    if ( !isset( self::$registered[ $name ] ) ) {
      if ( strpos( $name, '/' ) !== false ) {
        throw new \Exception( $name . ' not found in registered scripts and does not appear to be a URL' );
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

  public static function write() {
    if ( !empty( self::$queued ) ) {
      foreach ( self::$queued as $js ) {
        $ver = '';
        if ( $js[ 'version' ] !== false ) {
          $ver = (strpos( $js[ 'url' ], '?' ) === false ? '?' : '&') . $js[ 'version' ];
        }
        echo '<script type="text/javascript" src="' . $js[ 'url' ] . $ver . '"';
        echo!empty( $js[ 'name' ] ) ? ' id="' . $js[ 'name' ] . '"' : '';
        if ( is_array( $js[ 'attributes' ] ) && count( $js[ 'attributes' ] ) > 0 ) {
          foreach ( $js[ 'attributes' ] as $k => $v ) {
            echo ' ' . $k . '="' . htmlentities( $v ) . '"';
          }
        }
        echo '></script>';
      }
    }
  }

  private static function is_queued( $name ) {
    return isset( self::$queued[ $name ] );
  }

}
