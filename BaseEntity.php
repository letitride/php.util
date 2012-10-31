<?php
//require 'psql.php';
class psql{

    protected $_db;
    protected $_resource;

    function __construct()
    {
        $arg_list = func_get_args();
        $dsn = join( "\x20", $arg_list );
        $this->_db = pg_connect( $dsn );
    }


    public function get_record( $sql, &$row_count, &$rows, $cond = array() )
    {

        $ret = $this->query( $sql, $cond );
        if( $ret == false )
        {
            return false;
        }

        $row_count = pg_num_rows( $this->_resource );
        $rows = array();
        if( $row_count == 0 )
        {
            return true;
        }
        while( $record = $this->fetch() )
        {
            $rows[] = $record;
        }
        return true;
    }

    protected function query( $sql, $cond = array() )
    {
        $this->_resource = pg_query_params( $this->_db, $sql, $cond );
        if( $this->_resource == false )
        {
            //$error = pg_last_error( $this->_db );
            return false;
        }
        return true;
    }

    public function fetch()
    {
        return pg_fetch_object( $this->_resource );
    }

}

class BaseEntity{

    protected $_db;
    protected $_table;
    protected $_table_alias;

    protected $_select = array();
    protected $_where  = array();
    protected $_cond   = array();
    protected $_order;

    protected $_row_count = 0;
    protected $_rows = array();

    const DELETE_COLUMN = "flg_delete";

    function __construct()
    {
        /** sample connect */
        $this->_db = new psql( "host=localhost", "port=5432", "dbname=im5", "user=im0", "password=gps123" );
        $tbl = get_class( $this );
        $this->_table = $tbl;
        $this->_table_alias = $tbl;
        self::set_disable_record();
    }

    public function get( $column, $idx = 0 )
    {
        if( isset($this->_rows[$idx]->{$column}) == false ){
            return false;
        }
        return $this->_rows[$idx]->{$column};    
    }

    public function get_record( $idx )
    {
        if( isset($this->_rows[$idx]) == false ){
            return false;
        }
        return $this->_rows[$idx];
    }

    public function set_join( $join_table, $option = "inner" )
    {
        //todo
    }

    public function set_grouping()
    {
        //todo
    }

    public function set_order()
    {
        $arg_list = func_get_args();
        if( count($arg_list) < 1 ){return;}
        $this->_order = sprintf( "order by %s", join(", ", $arg_list) );
    }

    public function set_option( $option  )
    {
        $this->_option[] = $option;
    }


    public function set_select( $column, $tbl_alias = null, $column_alias = null )
    {
        if( $tbl_alias != null )
        {
            $column = $tbl_alias . $column;
        }
        if( $column_alias != null )
        {
            $column .= " as $column_alias ";
        }
        $this->_select[] = $column;
    }

    public function add_where( $column, $right_operator = "=", $value = 0, $left_operator = "and" )
    {
        $this->_where[] = array( $left_operator, $column, $right_operator );
        $this->_cond[] = $value;
    }

    public function to_entity()
    {
        $sql = self::make_select_query();
        print $sql."\n";
        return $this->_db->get_record( $sql, $this->_row_count, $this->_rows, $this->_cond );
    }


    protected function set_disable_record()
    {
        self::add_where( self::DELETE_COLUMN );
    }


    protected function make_select_query()
    {
        $select = "*";
        if( count( $this->_select ) )
        {
            $select = join( ",", $this->_select );
        }
        $where = "";
        foreach( $this->_where as $idx => $expression )
        {
            
            $place_holder = sprintf("$%d", $idx+1);
            $where .= sprintf( " %s %s %s %s ", $expression[0], $expression[1], $expression[2], $place_holder );
        }
        return sprintf( " select %s from %s where 1=1 %s %s %s ", $select, $this->_table, $where, $this->_order, join( "\x20", $this->_option) );

    }

}

/** usage */
class Account extends BaseEntity{

}

$account = new Account();
$account->set_select( "account_id" );
$account->set_select( "account_name" );
$account->add_where( "account_id", "=", 1 );
$account->set_order( "account_id desc", "created" );
$account->set_option( "limit 100" );
$account->set_option( "offset 0" );
$account->to_entity();
var_dump( $account->get( "account_name", 1 ) );
var_dump( $account->get_record( 0 ) );
/**/
