<?php
/**
 * 国土地理院標準メッシュコードのutility
 */
class MeshCodeUtility
{

    /**
     * 1次メッシュ取得
     */
    static function get_1st_mesh( $lat, $lon )
    {
        $src = array();

        $left_operator  = floor( $lat * 15 / 10 );
        $right_operator = floor( $lon - 100 );
        $src["mesh_code"] = sprintf( "%s%s", $left_operator, $right_operator );
        //南西端のlat,lon
        $lat = $left_operator / 15 * 10;
        $lon = $right_operator + 100;
        $src["lat"] = $lat;
        $src["lon"] = $lon;

        return $src;

    }

    /**
     * 2次メッシュ取得
     */
    static function get_2nd_mesh( $lat, $lon )
    {
        $first_mesh = self::get_1st_mesh( $lat, $lon );

        //2次メッシュは緯度方向 5分(5/60=0.0833333)区切り
        $left_operator  = floor( ( $lat - $first_mesh["lat"] ) * 100000  / 8333 );
        //経度方向 7分30秒(7/60+30/60/60=0.1166666+0.00833333=0.1249)区切り
        $right_operator = floor( ( $lon - $first_mesh["lon"] ) * 1000  / 125 );
        //南西端のlat,lon
        $lat = ( $left_operator * 8333 / 100000 ) + $first_mesh["lat"];
        $lon = ( $right_operator * 125 / 1000   ) + $first_mesh["lon"];

        $src = array();
        $src["mesh_code"] = sprintf( "%s-%s%s", $first_mesh["mesh_code"], $left_operator, $right_operator );
        $src["lat"] = $lat;
        $src["lon"] = $lon;
        return $src;

    }


    /**
     * 3次メッシュ取得
     */
    static function get_3rd_mesh( $lat, $lon )
    {
        $second_mesh = self::get_2nd_mesh( $lat, $lon );

        //3次メッシュは緯度方向 30秒(30/60/60=0.00833333)区切り
        $left_operator  = floor( ( $lat - $second_mesh["lat"] ) * 1000000  / 8333 );
        //経度方向 45秒(45/60/60=0.0125)区切り
        $right_operator = floor( ( $lon - $second_mesh["lon"] ) * 10000  / 125 );
        //南西端のlat,lon
        $lat = ( $left_operator * 8333 / 1000000 ) + $second_mesh["lat"];
        $lon = ( $right_operator * 125 / 10000   ) + $second_mesh["lon"];

        $src = array();
        $src["mesh_code"]  = sprintf( "%s-%s%s", $second_mesh["mesh_code"], $left_operator, $right_operator );
        $src["lat"] = $lat;
        $src["lon"] = $lon;

        return $src;

    }

    /**
     * メッシュコードから南西端の緯度経度取得
     */
    static function get_gps( $first, $second = false, $third = false )
    {

        $src = array();
        list( $left_operator, $right_operator ) = sscanf( $first, "%02d%02d");
        $f_lat = $left_operator / 15 * 10;
        $f_lon = $right_operator + 100;
        $src["lat"] = $f_lat;
        $src["lon"] = $f_lon;
        if( $second == false )
        {
            return $src;
        }

        list( $left_operator, $right_operator ) = sscanf( $second, "%01d%01d");
        $s_lat = ( $left_operator * 8333 / 100000 ) + $f_lat;
        $s_lon = ( $right_operator * 125 / 1000   ) + $f_lon;
        $s_lat = ceil( $s_lat * 10000 ) / 10000;
        $s_lon = ceil( $s_lon * 10000 ) / 10000;
        $src["lat"] = $s_lat;
        $src["lon"] = $s_lon;
        if( $third == false )
        {
            return $src;
        }

        list( $left_operator, $right_operator ) = sscanf( $third, "%01d%01d");
        $t_lat = ( $left_operator * 8333 / 1000000 ) + $s_lat;
        $t_lon = ( $right_operator * 125 / 10000   ) + $s_lon;
        $t_lat = ceil( $t_lat * 10000 ) / 10000;
        $t_lon = ceil( $t_lon * 10000 ) / 10000;
        $src["lat"] = $t_lat;
        $src["lon"] = $t_lon;

        return $src;

    }

}

