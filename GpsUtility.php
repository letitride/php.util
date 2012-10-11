<?php
/**
 * GpsUtility 
 */
class GpsUtility extends Super
{

    /**
     * 緯度、経度から2点間の距離を計算
     */
    public static function get_distance($lat1, $lon1, $lat2, $lon2)
    {

        $a = 111000; // 緯度1度（単位：メートル）
        $b = 91000;  // 経度1度（単位：メートル）

        //2点間の差分をとる
        $x = abs(($lat1 - $lat2) * $a);
        $y = abs(($lon1 - $lon2) * $b);
        //直角三角形の斜辺の長さを求める
        $distance = hypot($x, $y);

        //メートルの整数値に変換
        $distance = floor($distance);

        return $distance;
    }

    /**
     * 単位変換(秒->DMS)
     */
    public static function sec_to_dms($sec)
    {
        $d  = floor( $sec / 3600 );
        $n1 = $sec - ($d * 3600);
        $m  = floor( $n1 / 60 );
        $n2 = $n1 - ($m * 60);
        $s  = $n2;

        return "$d.$m.$s";
    }

    /**
     * 単位変換(DMS->秒)
     */
    public static function dms_to_sec($dms)
    {
        list($d, $m, $s, $u) = split("\.|,", $dms, 4);

        $sec = ( ($d * 60 * 60) + ($m * 60) + $s + $u / 100);

        return $sec;
    }

    /**
     * 単位変換(DMS->度)
     */
    public static function dms_to_degree($dms)
    {
        // 度 . 分 . 秒 . 1/100秒
        list($d, $m, $s, $u) = split("\.|,", $dms, 4);

        if( strlen($u) > 2 )
        {
            $u = substr( $u, 0, 2 );
        }

        $deg = $d + $m / 60 + $s / (60*60) + $u / (60*60*100);

        return $deg;
    }

    /**
     * 単位変換(秒->度)
     */
    public static function sec_to_degree($sec)
    {
        $d  = floor( $sec / 3600 );
        $n1 = $sec - ($d * 3600);
        $m  = floor( $n1 / 60 );
        $n2 = $n1 - ($m * 60);
        $s  = floor( $n2 );
        $u  = ($n2 - $s) * 100;

        $deg = $d + $m / 60 + $s / (60*60) + $u / (60*60*100);

        return $deg;
    }

    /**
     * 単位変換(度->DMS)
     */
    public static function degree_to_dms($deg)
    {
        $d = floor( $deg );
        $m = floor( ($deg - $d) * 60);
        $s = floor( ($deg - $d - $m / 60) * 3600 );
        $u = floor( ($deg - $d - $m / 60 - $s / 3600) * 360000 );

        $dms = "$d.$m.$s.$u";

        return $dms;
    }

    /**
     * 単位変換(度->秒)
     */
    public static function degree_to_sec($deg)
    {
        $d = floor( $deg );
        $m = floor( ($deg - $d) * 60);
        $s = floor( ($deg - $d - $m / 60) * 3600 );
        $u = floor( ($deg - $d - $m / 60 - $s / 3600) * 360000 );

        $sec = ( ($d * 60 * 60) + ($m * 60) + $s + $u / 100);

        return $sec;
    }


    /**
     * 測地系変換 (日本 -> 世界)
     */
    public static function tky_to_wgs($b, $l)
    {

        // データム諸元
        // 変換元
        // (Tokyo)
        $a = 6377397.155;
        $f = 1 / 299.152813;
        $e2 = 2*$f - $f*$f;

        // 変換先
        // (WGS 84)
        $a_ = 6378137;           // 赤道半径
        $f_ = 1 / 298.257223;    // 扁平率
        $e2_ = 2*$f_ - $f_*$f_;  // 第1離心率

        // 並行移動量 [m]
        // e.g. $x_ = $x + $dx etc.
        $dx = -148;
        $dy = +507;
        $dz = +681;

        $h = 0.0;
        $xyz = self::llh_to_xyz($b, $l, $h, $a, $e2);
        $blh = self::xyz_to_llh($xyz[0]+$dx, $xyz[1]+$dy, $xyz[2]+$dz, $a_, $e2_);
        $ret = array();
        $ret[0] = $blh[0];
        $ret[1] = $blh[1];

        return $ret;
    }

    /**
     * 測地系変換 (世界 -> 日本)
     */
    public static function wgs_to_tky($b, $l)
    {

        // データム諸元
        // 変換元
        // (Tokyo)
        $a = 6377397.155;
        $f = 1 / 299.152813;
        $e2 = 2*$f - $f*$f;

        // 変換先
        // (WGS 84)
        $a_ = 6378137;           // 赤道半径
        $f_ = 1 / 298.257223;    // 扁平率
        $e2_ = 2*$f_ - $f_*$f_;  // 第1離心率

        // 並行移動量 [m]
        // e.g. $x_ = $x + $dx etc.
        $dx = -148;
        $dy = +507;
        $dz = +681;

        $h = 0.0;
        $xyz = self::llh_to_xyz($b, $l, $h, $a_, $e2_);
        $blh = self::xyz_to_llh($xyz[0]-$dx, $xyz[1]-$dy, $xyz[2]-$dz, $a, $e2);
        $ret = array();
        $ret[0] = $blh[0];
        $ret[1] = $blh[1];

        return $ret;
    }

    /**
     * 楕円体座標 -> 直交座標
     */
    public static function llh_to_xyz($b, $l, $h, $a, $e2)
    {

        $pi  = M_PI;        // 円周率
        $rd  = $pi / 180;   // [ラジアン/度]

        $b *= $rd;
        $l *= $rd;
        $sb = sin($b);
        $cb = cos($b);
        $rn = $a / sqrt(1-$e2*$sb*$sb);

        $x = ($rn+$h) * $cb * cos($l);
        $y = ($rn+$h) * $cb * sin($l);
        $z = ($rn*(1-$e2)+$h) * $sb;

        $ret = array();
        $ret[0] = $x;
        $ret[1] = $y;
        $ret[2] = $z;

        return $ret;
    }

    /**
     * 直交座標 -> 楕円体座標
     */
    public static function xyz_to_llh($x, $y, $z, $a, $e2)
    {

        $pi  = M_PI;        // 円周率
        $rd  = $pi / 180;   // [ラジアン/度]

        $bda = sqrt(1-$e2); // b/a

        $p = sqrt($x*$x+$y*$y);
        $t = atan2($z,$p*$bda);
        $st = sin($t);
        $ct = cos($t);
        $b = atan2($z+$e2*$a/$bda*$st*$st*$st,$p-$e2*$a*$ct*$ct*$ct);
        $l = atan2($y,$x);
        $sb = sin($b);
        $rn = $a / sqrt(1-$e2*$sb*$sb);
        $h = $p/cos($b) - $rn;

        $ret = array();
        $ret[0] = $b/$rd;
        $ret[1] = $l/$rd;
        $ret[2] = $h;

        return $ret;
    }

}

?>
