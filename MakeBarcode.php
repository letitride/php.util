<?php
class MakeBarcode{

    //画像サイズ
    protected $size = 1;
    //fontファイル
    protected $font_file = null;

    //パリティデータ
    protected $parity;
    //画像キャンパスサイズ
    protected $width;
    protected $height;

    /*
     * 先頭数字の左側部パリティパターン表
     * 0:奇数パリティ 1:偶数パリティ
     */
    protected $parity_pattern = array(
        "000000",
        "001011",
        "001101",
        "001110",
        "010011",
        "011001",
        "011100",
        "010101",
        "010110",
        "011010",
    );

    //センター左側奇数パリティパターン
    protected $left_odd_pattern = array(
        "0001101",
        "0011001",
        "0010011",
        "0111101",
        "0100011",
        "0110001",
        "0101111",
        "0111011",
        "0110111",
        "0001011",
    );

    //センター左側偶数パリティパターン
    protected $left_even_pattern = array(
        "0100111",
        "0110011",
        "0011011",
        "0100001",
        "0011101",
        "0111001",
        "0000101",
        "0010001",
        "0001001",
        "0010111",
    );

    //センター右側パリティパターン
    protected $right_pattern = array(
        "1110010",
        "1101100",
        "1101100",
        "1000010",
        "1011100",
        "1001110",
        "1010000",
        "1000100",
        "1001000",
        "1110100",
    );

    //キャンパスの描画マージン この値*指定pxが実際のマージンとなる
    const CANVAS_MARGIN = 2;

    /**
     * バーコードサイズセット
     */
    public function set_size( $size )
    {
        $this->size = $size;
    }

    /**
     * バーコードサイズセット
     */
    public function set_font( $font_file )
    {
        $this->font_file = $font_file;
    }


    /**
     * jpg出力
     */
    public function image_jpeg( $ean_code, $file_name = null )
    {

        //保存ファイル名が与えられない場合は、ean_codeをファイル名とする
        if( $file_name == null )
        {
            $file_name = "{$ean_code}.jpg";
        }

        //パリティ生成(0:白パリティ,1:黒パリティ,2:ガイドバー)
        $this->parity = $this->make_parity( $ean_code );

        //キャンパスサイズ取得
        list( $this->width, $this->height ) = $this->get_canvas_size();

        //バー描画
        $image_src = $this->write_bar_image();

        //フォント指定時は数字描画
        if( $this->font_file != null && file_exists( $this->font_file ) )
        {
            $this->write_number_image( $image_src, $ean_code );
        }

        //jpg出力
        imagejpeg( $image_src, $file_name );
        imagedestroy($image_src);
    }

    /**
     * png出力
     */
    public function image_png( $ean_code, $file_name = null )
    {

        if( $file_name == null )
        {
            $file_name = "{$ean_code}.png";
        }

        $this->parity = $this->make_parity( $ean_code );
        list( $this->width, $this->height ) = $this->get_canvas_size();
        $image_src = $this->write_bar_image();
        if( $this->font_file != null && file_exists( $this->font_file ) )
        {
            $this->write_number_image( $image_src, $ean_code );
        }

        //png出力
        imagepng( $image_src, $file_name );
        imagedestroy($image_src);
    }

    /**
     * パリティデータ生成
     */
    protected function make_parity( $ean_code )
    {
        $ean_characters = str_split( $ean_code );
        $head      = $ean_characters[0];
        $pattern   = $this->parity_pattern[ $head ];

        //レフトガイドバー
        $parity = "202";

        //センター左側部のパリティ計算
        for($i = 0;$i < 6;$i++)
        {

            next( $ean_characters );
            $key = current( $ean_characters );

            $char = $pattern[$i];
            //奇数パリティ
            if( $char == "0" )
            {
                $parity .= $this->left_odd_pattern[ $key ];
                continue;
            }

            //偶数パリティ
            $parity .= $this->left_even_pattern[ $key ];
        }

        //センターガイドバー
        $parity .= "02020";

        //センター右側部のパリティ計算
        for( $i = 0;$i < 6;$i++ )
        {
            next( $ean_characters );
            $key = current( $ean_characters );
            $parity .= $this->right_pattern[ $key ];
        }

        //ライトガイドバー
        $parity .= "202";

        return $parity;

    }

    /**
     * キャンバスサイズの取得
     */
    protected function get_canvas_size()
    {

        $parity = $this->parity;
        $margin = $this->size;
        $height = $margin * 60;
        $width  = strlen( $parity ) * $margin;
        //先頭1文字のマージン
        $width += 7 * $margin;
        //左右の描画マージン
        $width += self::CANVAS_MARGIN * $margin * 2;

        return array( $width, $height );

    }

    /**
     * バー描画
     */
    protected function write_bar_image()
    {
        $parity = $this->parity;
        $margin = $this->size;
        $width  = $this->width;
        $height = $this->height;

        $image = imagecreate( $width, $height );
        //最初のコールで背景色がセットされます。
        $back_ground = imagecolorallocate( $image, 255, 255, 255 );
        //バー、テキストの色(黒)
        $bar_color   = imagecolorallocate( $image, 0, 0, 0 );

        $x = (self::CANVAS_MARGIN * $margin) + (7 * $margin);

        $char_list = str_split( $parity );
        foreach( $char_list as $char )
        {

            //パリティ0は描画しない(白色パリティ)
            if( $char == 0 )
            {
                $x += $margin;
                continue;
            }

            //描画座標計算
            $dest_x = $x + $margin - 1;
            $y = self::CANVAS_MARGIN * $margin;
            $dest_y = round( $height - ( $margin * 10 ) );
            //センターバー
            if( $char == 2 )
            {
                $dest_y = $height - (self::CANVAS_MARGIN * $margin);
            }
            //バー描画
            imagefilledrectangle( $image, $x, $y, $dest_x, $dest_y, $bar_color );
            $x = $dest_x + 1;
        }

        return $image;
    }


    /**
     * 数字描画
     */
    protected function write_number_image( $image_src, $ean_code )
    {

        $parity    = $this->parity;
        $height    = $this->height;
        $margin    = $this->size;
        $font_file = $this->font_file;

        $head  = substr( $ean_code, 0, 1 );
        $left  = substr( $ean_code, 1, 6 );
        $right = substr( $ean_code, 7 );
        $text_color = imagecolorallocate( $image_src, 0, 0, 0 );

        $font = 7 * $margin;
        $x = self::CANVAS_MARGIN * $margin + $font / 3;
        $y = $height - (self::CANVAS_MARGIN * $margin);
        imagettftext( $image_src, $font, 0, $x, $y, $text_color, $font_file, $head );
        //左ガイドバースペース
        $x += $margin * 7 + $font / 2.5;
        for( $i=0;$i<strlen($left);$i++ )
        {
            $value = substr( $left, $i, 1 );
            imagettftext( $image_src, $font, 0, $x, $y, $text_color, $font_file, $value );
            $x += $margin * 7;

        }
        //センターガイドバースペース
        $x += $font / 2;
        for( $i=0;$i<strlen($right);$i++ )
        {
            $value = substr( $right, $i, 1 );
            imagettftext( $image_src, $font, 0, $x, $y, $text_color, $font_file, $value );
            $x += $margin * 7;

        }
    }
}

$object = new MakeBarcode();
$object->set_size(2);
$object->set_font("/usr/share/fonts/japanese/TrueType/sazanami-mincho.ttf");
$object->image_png( 4902471063378 );
$object->image_jpeg( 4902471063378 );
