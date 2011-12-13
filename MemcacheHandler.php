<?php 
/**
 * MemcacheHandler
 * @author letitride
 */
class MemcacheHandler
{

    static private $memcache = null;
    static private $hosts = array(
        "host","host",
    );

    /**
     * connect
     */
    static private function connect( $hosts, $port = 11211 )
    {

        $memcache = new Memcache();
        if( ! is_array( $hosts ) )
        {
            $hosts = array( $hosts );
        }

        foreach( $hosts as $address )
        {
            $memcache->addServer( $address, $port );
        }

        self::$memcache = $memcache;
    }

    /**
     * get
     */
    static public function get( $key )
    {
        if( null == self::$memcache ){
            self::connect( self::$hosts );
        }
        return self::$memcache->get( $key );
    }

    /**
     * set
     */
    static public function set( $key, $value, $expire = 0, $compressed = 0 )
    {
        if( null == self::$memcache ){
            self::connect( self::$hosts );
        }
        return self::$memcache->set( $key, $value, $compressed, $expire );
    }

}