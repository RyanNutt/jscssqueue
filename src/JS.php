<?php

namespace Aelora;

class JS {

  private static $registered = [];
  private static $queued = [];

  /**
   * Whether this class and its CSS buddy have been initialized.
   * @var boolean
   */
  public static $hasInit = false;

  /**
   * Full path to the config file. If it's empty, a few other checks
   * are made before giving up that there's not a config file. 
   * 
   * @var string
   */
  public static $configFile = '';

  public static function init() {
    if ( self::$hasInit ) {
      return;
    }
    if ( !empty( self::$configFile ) && file_exists( self::$configFile ) ) {
      /* Specified config file before first call */
      self::readConfig( self::$configFile );
    }
    else if ( function_exists( 'config_path' ) && file_exists( config_path( 'jscssqueue.php' ) ) ) {
      /* Laravel, or own config_path function */
      self::readConfig( config_path( 'jscssqueue.php' ) );
    }
    else if ( file_exists( __DIR__ . '/jscssqueue.php' ) ) {
      /* Config is next to classes */
      self::readConfig( __DIR__ . '/jscssqueue.php' );
    }
    self::$hasInit = true;
  }

  /**
   * Allows you to reset by loading another config file
   * @param type $config_file
   */
  public function reset( $configFile = '' ) {
    self::$configFile = $configFile;
    self::$hasInit = false;
    self::$queued = [];
    self::$registered = [];
    CSS::reset();

    self::init();
  }

  private static function readConfig( $path ) {
    $config = include($path);
    if ( isset( $config[ 'register' ][ 'js' ] ) && is_array( $config[ 'register' ][ 'js' ] ) ) {
      foreach ( $config[ 'register' ][ 'js' ] as $k => $script ) {
        if ( is_array( $config[ 'register' ][ 'js' ][ $k ] ) ) {
          $toAdd = array_merge( [ 'name' => $k, 'url' => '', 'version' => false, 'dependencies' => [], 'attributes' => [] ], $script );
        }
        else {
          $toAdd = [ 'name' => $k, 'url' => $script, 'version' => false, 'dependencies' => [], 'attributes' => [] ];
        }
        self::register( $toAdd[ 'url' ], $toAdd[ 'name' ], $toAdd[ 'version' ], $toAdd[ 'dependencies' ], $toAdd[ 'attributes' ], false );
      }
    }
    if ( isset( $config[ 'enqueue' ][ 'js' ] ) && is_array( $config[ 'enqueue' ][ 'js' ] ) ) {
      foreach ( $config[ 'enqueue' ][ 'js' ] as $name ) {
        self::enqueue( $name, false );
      }
    }


    if ( isset( $config[ 'register' ][ 'css' ] ) && is_array( $config[ 'register' ][ 'css' ] ) ) {
      foreach ( $config[ 'register' ][ 'css' ] as $k => $script ) {
        if ( is_array( $config[ 'register' ][ 'css' ][ $k ] ) ) {
          $toAdd = array_merge( [ 'name' => $k, 'url' => '', 'version' => false, 'dependencies' => [], 'attributes' => [] ], $script );
        }
        else {
          $toAdd = [ 'name' => $k, 'url' => $script, 'version' => false, 'dependencies' => [], 'attributes' => [] ];
        }
        CSS::register( $toAdd[ 'url' ], $toAdd[ 'name' ], $toAdd[ 'version' ], $toAdd[ 'dependencies' ], $toAdd[ 'attributes' ], false );
      }
    }

    if ( isset( $config[ 'enqueue' ][ 'css' ] ) && is_array( $config[ 'enqueue' ][ 'css' ] ) ) {
      foreach ( $config[ 'enqueue' ][ 'css' ] as $name ) {
        CSS::enqueue( $name, false );
      }
    }
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
   * @param string $name Can either be a registered script by name or a url. Using a 
   *  URL doesn't do any dependency checking. 
   */
  public static function enqueue( $name, $init = true ) {
    if ( $init ) {
      self::init();
    }
    if ( !isset( self::$registered[ $name ] ) ) {

      if ( strpos( $name, '/' ) === false ) {
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

  /**
   * Clears out the queue of scripts
   */
  public function clear() {
    self::$queued = [];
  }

  private static function is_queued( $name ) {
    return isset( self::$queued[ $name ] );
  }

}
