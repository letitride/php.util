<?php

class ProcessHandler
{

    //処理回数
    protected $_break_process;
    //最大同時起動プロセス数
    protected $_max_process;
    //プロセスタイムアウト時間
    protected $_timeout;
    //ログモード
    protected $_log_mode;
    const LOG_NO_OUTPUT = -1;   //ログ出力しない
    const LOG_STDOUT    = 0;    //標準出力
    const LOG_FILE      = 3;    //ファイル出力

    //実行method or function
    protected $_executes = array();
    //引数
    protected $_args     = array();
    //実行pid
    protected $_fork_process = array();
    //処理開始時間
    protected $_start_time;

    /**
     * __construct
     * カウンタ初期化
     * 最大起動プロセス数, 最大処理回数, プロセスタイムアウト時間
     */
    function __construct( $max = 0, $break = 0, $timeout = 60 )
    {
        $this->_break_process = $break;
        $this->_max_process   = $max;
        $this->_timeout       = $timeout;
        $this->_log_mode = self::LOG_NO_OUTPUT;
        $this->_log_file = sprintf( "%s/fork.log", dirname( __FILE__ ) );
    }

    /**
     * ログモードセット
     */
    public function set_log_mode( $mode, $logfile = null )
    {
        $this->_log_mode = $mode;
        if( $logfile != null )
        {
            $this->_log_file = $logfile;
        }
    }
    /**
     * 処理回数セット
     */
    public function set_break_process( $value )
    {
        $this->_break_process = $break;
    }
    /**
     * 最大同時実行数セット
     */
    public function set_max_process( $value )
    {
        $this->_max_process = $max;
    }
    /**
     * タイムアウトセット
     */
    public function set_timeout( $value )
    {
        $this->_timeout = $timeout;
    }
    /**
     * 実行メソッドセット
     */
    public function set_instance_process( $object, $method, $args = array() )
    {
        $this->_executes[] = array( $object, $method );
        if( ! is_array( $args ) )
        {
            $args = array( $args );
        }
        $this->_args[] = $args; 
    }
    /*
     * 実行関数セット
     */
    public function set_function_process( $function, $args = array() )
    {

        $this->_executes[] = $function;
        if( ! is_array( $args ) )
        {
            $args = array( $args );
        }
        $this->_args[] = $args; 
    }

    /**
     * プロセス実行
     */
    public function run()
    {

        if( $this->_executes == "" )
        {
            return false;
        }

        if( $this->_break_process == 0 )
        {
            $this->_break_process = count( $this->_executes );
        }
        if( $this->_max_process == 0 )
        {
            $this->_max_process = count( $this->_executes );
        }

        $this->_init_log();

        foreach( $this->_executes as $idx => $func )
        {

            if( $this->_break_process != 0 && $idx >= $this->_break_process )
            {
                break;
            }

            $args = $this->_args[$idx];
            $pid = pcntl_fork();
            //fork失敗
            if( $pid == -1 ){ return false; }

            //子プロセスハンドラ
            if( $pid == 0 )
            {
                //timeout設定
                pcntl_alarm( $this->_timeout );
                $mypid = getmypid();
                //処理実施
                $ret = call_user_func_array( $func, $args );
                $time = microtime(true) - $this->_start_time;
                $this->_log( "child {$mypid}: ". $time );
                exit();
            }

            //親ハンドラ

            //pid フック
            $this->_fork_process[$pid] = true;
            $pids[ $idx ] = $pid;

            //最大プロセス起動時はjob完了するまで待機
            if( $this->_max_process <= count($this->_fork_process) )
            {
                unset( $this->_fork_process[ pcntl_waitpid( -1, $status, WUNTRACED ) ] );
            }
        }


        while( 0 < count($this->_fork_process) )
        {
            unset( $this->_fork_process[ pcntl_waitpid( -1, $status, WUNTRACED ) ] );
        }

        $time = microtime(true) - $this->_start_time;
        $this->_log( "parent: ". $time );
        return $pids;

    }

    //ログ記録
    protected function _log( $msg )
    {
        if( $this->_log_mode == self::LOG_NO_OUTPUT )
        {
            return;
        }
        error_log( sprintf("%s \n", $msg ), $this->_log_mode, $this->_log_file );
    }

    /**
     * 処理起動開始ログ
     */
    protected function _init_log()
    {
        $this->_start_time = microtime(true);
        $this->_log( "start: {$this->_start_time} " );
        $this->_log( "_break_process: {$this->_break_process} " );
        $this->_log( "_max_process: {$this->_max_process} " );
        $this->_log( "execute: ". var_export( $this->_executes, 1 ) );
        $this->_log( "arg: ".var_export($this->_args, 1) );
    }

}

/**
 * usage
*/

class Hoge{

    public $data;

    function print_data( $v ){
        sleep(1);
        $pid = getmypid();
        print "$pid : $v \n";
    }
}

function print_arg( $v ){
    sleep(1);
    print "$v \n";
}

//single thread
$hoge = new Hoge();
$hoge->print_data( 1 );
$hoge->print_data( 2 );
$hoge->print_data( 3 );
print_arg( 1 );
print_arg( 2 );
print_arg( 3 );
// 6seconds

//multi thread
$tasks = array();
$ph = new ProcessHandler();
$ph->set_instance_process( $hoge, "print_data", array( "fork1" ) );
$ph->set_instance_process( $hoge, "print_data", array( "fork2" ) );
$ph->set_instance_process( $hoge, "print_data", array( "fork3" ) );
$ph->set_function_process( "print_arg", array( "fork1" ) );
$ph->set_function_process( "print_arg", array( "fork2" ) );
$ph->set_function_process( "print_arg", array( "fork3" ) );
$ph->set_log_mode( ProcessHandler::LOG_NO_OUTPUT );
$pids = $ph->run();

print "end of process";