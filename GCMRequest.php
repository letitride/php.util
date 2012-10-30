<?php
class GCMRequest 
{ 

    //api key
    protected $api_key;
    //送信モード(multi or single)
    protected $send_mode;
    //intent.extract
    protected $extracts = array();

    //TODO option param定義

    const GCM_URI = "https://android.googleapis.com/gcm/send";

    //送信モード定義
    const MULTI_CAST = 1;
    const PAIN_TEXT  = 2;


    public function set_api_key( $key )
    {
        $this->api_key = $key;
    }

    //intentで渡される電文をセット
    public function set_extract( $key, $value )
    {
        $this->extracts[ $key ] = $value;
    }


    /*
     * メッセージ送信
     * params mixed registration_id 送信アドレス(複数配信時は配列で渡す)
     */
    public function send_message( $registration_id )
    {

        //マルチキャスト
        if( is_array( $registration_id ) && count( $registration_id ) > 1 )
        {
            self::set_send_mode( self::MULTI_CAST );
            $payload = self::make_json_payload( $registration_id );
        //PLAIN
        }else{
            self::set_send_mode( self::PAIN_TEXT );
            $payload = self::make_plain_text_payload( $registration_id );
        }

        //電文送信
        $curl = self::make_curl();
        curl_setopt( $curl, CURLOPT_POSTFIELDS, $payload );
        $ret = curl_exec($curl);

    }

    //送信モードセット
    protected function set_send_mode( $mode )
    {
        $this->send_mode = $mode;
    }

    //ネットワークハンドル設定
    protected function make_curl()
    {

        switch( $this->send_mode )
        {
            //json format
            case self::MULTI_CAST : 
                $content_type = "application/json;charset=utf-8";
                break;

            //post query
            case self::PAIN_TEXT : 
                $content_type = "application/x-www-form-urlencoded;charset=utf-8";
                break;

            default:
                return false;
        }

        $header = array(
            "Content-Type: "     .$content_type,
            "Authorization: key=".$this->api_key,
        );

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, self::GCM_URI );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
        curl_setopt( $ch, CURLOPT_POST, TRUE );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );

        return $ch;

    }

    //jsonペイロード作成
    protected function make_json_payload( $registration_ids )
    {

        $payload = array();
        $payload["data"] = $this->extracts;
        $payload["registration_ids"] = array();
        foreach( $registration_ids as $id )
        {
            array_push( $payload["registration_ids"], $id );
        }

        return json_encode( $payload );
    }

    //postペイロード作成
    protected function make_plain_text_payload( $registration_id ){

        $payload = array();
        $payload["registration_id"] = $registration_id;
        foreach( $this->extracts as $key => $message )
        {
            $extract = "data.{$key}";
            $payload[ $extract ] = $message;
        }

        return http_build_query( $payload, "&");

    }

}
/* usage
$address = array( "user1", "user2" );
$o = new GCMUtility();
$o->set_api_key( "key" );
$o->set_extract( "message", "hello GCM world" );
$o->send_message( $address );
*/