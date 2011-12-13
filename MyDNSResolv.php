<?php
//require pear
require_once('Net/DNS.php');

class MyDNSResolv
{

    /**
     * NSレコード取得
     */
    static function lookup_ns_record( $domain )
    {
        $dns     = new Net_DNS_Resolver();
        $response = $dns->query( $domain, "NS" );
        if ( ! $response )
        {
            return null;
        }

        $ns = array();
        foreach( $response->answer as $rr )
        {
            if( $rr->type == "NS" )
            {
                $ns[] = $rr->nsdname;
            }
        }

        if( count( $ns ) == 0 )
        {
            return null;
        }
        return $ns;
    }


    /**
     * MXレコード取得
     */
    static function lookup_mx_record( $domain )
    {
        $dns     = new Net_DNS_Resolver();
        $response = $dns->query( $domain, "MX" );
        if ( ! $response )
        {
            return null;
        }

        $mx = array();
        foreach( $response->answer as $rr )
        {
            if( $rr->type == "MX" )
            {
                $mx[] = $rr->exchange;
            }
        }

        if( count( $mx ) == 0 )
        {
            return null;
        }
        return $mx;
    }


    /**
     * 正引き
     */
    static function lookup_a_record( $domain )
    {
        $dns     = new Net_DNS_Resolver();
        $response = $dns->query( $domain, "A" );
        if ( ! $response )
        {
            return null;
        }

        $rr = $response->answer[0];
        if( $rr->type == "A" )
        {
            return $rr->address;
        }

        return $null;
    }


    /**
     * TXTレコード
     */
    static function lookup_txt_record( $domain )
    {
        $dns     = new Net_DNS_Resolver();
        $response = $dns->query( $domain, "TXT" );
        if ( ! $response )
        {
            return null;
        }

        $rr = $response->answer[0];
        if( $rr->type == "TXT" )
        {
            return $rr->text[0];
        }

        return null;

    }

}
