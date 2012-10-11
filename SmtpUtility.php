<?php
require_once('Net/SMTP.php');


class SmtpUtility extends Super
{

    /** 接続先サーバーホスト */
    protected $_smtp_host;
    /** SMTPポート */
    protected $_smtp_port = 25;
    /** 接続元ドメイン */
    protected $_helo_host;
    /** 送信アドレス */
    protected $_from_address;

    /** 接続インスタンス */
    protected $_smtp;

    const NG = 0;
    const OK = 1;

    /** RCPT TO返却値 */
    //コマンドの発行順序が間違っている。
    const WRN_BAD_SEQUENCE_OF_COMMANDS = 503;
    //メールボックスが利用できないため、要求された処理は実行不能。
    const WRN_MAILBOX_UNAVAILABLE = 550;
    //受信者が存在しない。[FORWARD-PATH]に送信せよ。
    const WRN_USER_NOT_LOCAL = 551;
    //ディスク不足のため、要求された処理は実行不能。
    const WRN_REQUESTED_MAIL_ACTION_ABORTED = 552;
    //メールボックスの名前が不適切なため、要求された処理は実行不能。
    const WRN_REQUESTED_ACTION_NOT_TAKEN = 553;
    //処理失敗。
    const WRN_TRANSACTION_FAILED = 554;

    /** 使用不能なメールアドレス */
    const WRN_INVALID_MAIL_ADDRESS = 10001;

    public function __set($nm, $value){
        $this->{"_$nm"} = $value;
    }

    function connect()
    {

        /** SMTP接続 */
        $conn = "";
        $ret = $this->smtp_connect();
        if( $ret == self::NG )
        {
            return self::NG;
        }

        /** HELO */
        $ret = $this->helo();
        if( $ret == self::NG )
        {
            return self::NG;
        }

        /* MAIL FROM */
        $ret = $this->mail_from();
        if( $ret == self::NG )
        {
            return self::NG;
        }

        return self::OK;

    }

    function add_rcpt( $to )
    {

        /* RCPT TO */
        $ret = $this->rcpt_to( $to );
        if( $ret == self::NG )
        {
            return self::NG;
        }
        if( $ret == self::WRN_INVALID_MAIL_ADDRESS )
        {
            return self::WRN_INVALID_MAIL_ADDRESS;
        }

        return self::OK;

    }

    function disconnect()
    {

        $this->_smtp->disconnect();
        $answer = $this->_smtp->getResponse();
        $this->_log_debug( " QUIT" );
        $this->_log_debug( $answer[0]." ".$answer[1] );

    }


    /**
     * SMTP接続
     */
    private function smtp_connect()
    {
        $smtp = new Net_SMTP( $this->_smtp_host, $this->_smtp_port, $this->_helo_host );
        $result = $smtp->connect();
        if ( PEAR::isError( $result ) )
        {
            $this->_log_debug('Connect : ' . $result->toString() );
            return self::NG;
        }

        $conn_answer = $smtp->getResponse();
        $this->_log_debug( " Connected to ".$this->_smtp_host."  ".$conn_answer[0]." ".$conn_answer[1] );

        $this->_smtp = $smtp;

        return self::OK;
    }

    /**
     * HELO (SMTPトランザクションの開始)
     */
    private function helo()
    {
        $smtp = $this->_smtp;
        $result = $smtp->helo( $this->_helo_host );
        if ( PEAR::isError( $result ) )
        {
            $this->_log_debug( 'HELO : ' . $result->toString() );
            $smtp->disconnect();
            return self::NG;
        }

        /** HELO 応答 */
        $helo_answer = $smtp->getResponse();
        $this->_log_debug( " HELO ".$this->_helo_host );
        $this->_log_debug( $helo_answer[0]." ".$helo_answer[1] );
        if( $helo_answer[0] != 250 )
        {
            $this->_log_debug('HELO(RES) : ' . $helo_answer[1]);
            $smtp->disconnect();
            return self::NG;
        }

        return self::OK;

    }


    /**
     * MAIL FROM
     */
    private function mail_from()
    {
        $mta = $this->_smtp;
        $result = $mta->mailFrom( $this->_from_address );
        if ( PEAR::isError( $result ) )
        {
            $this->_log_debug( 'MAIL FROM : ' . $result->toString() );
            $mta->disconnect();
            return self::NG;
        }

        $response = $mta->getResponse();
        $this->_log_debug( " MAIL FROM: ".$this->_from_address );
        $this->_log_debug( $response[0]." ".$response[1] );
        if( $response[0] != 250 )
        {
            // エラー！
            $this->_log_debug('MAIL FROM(RES) : ' . $response[1]);
            $mta->disconnect();
            return self::NG;
        }
        return self::OK;
    }


    /**
     * RCPT TO
     */
    private function rcpt_to( $to )
    {

        $mta = $this->_smtp;
        $result = $mta->rcptTo( $to );

        //コマンド送信結果
        $response = $mta->getResponse();
        $this->_log_debug( " RCPT TO: ".$to );
        $this->_log_debug( $response[0]." ".$response[1] );
        $status = $response[0];

        switch( $status )
        {
            case self::WRN_BAD_SEQUENCE_OF_COMMANDS:
                return self::NG;
            case self::WRN_MAILBOX_UNAVAILABLE:
            case self::WRN_USER_NOT_LOCAL:
            case self::WRN_REQUESTED_MAIL_ACTION_ABORTED:
            case self::WRN_REQUESTED_ACTION_NOT_TAKEN:
                return self::WRN_INVALID_MAIL_ADDRESS;
            case self::WRN_TRANSACTION_FAILED:
                return self::NG;
            default :
                return self::OK;
        }

        return self::OK;

    }


    /**
     * DATA
     */
    public function data_command( $data )
    {

        $mta = $this->_smtp;

        $result = $mta->data($data);
        $response = $mta->getResponse();
            $this->_log_debug('DATA(RES) : ' . $response[0].' '.$response[1]);
        if($response[0] != 250) {
            // エラー！
            //$this->_log_debug('DATA(RES) : ' . $response[1]);
            $mta->disconnect();
            return self::OK;
        }


        return self::OK;

    }

}
