<?php
require 'psql.php';
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

    function __construct()
    {
        /** sample */
        $this->_db = new pgsql( "db dsn"  );
        $this->_db->connect();
        $tbl = get_class( $this );
        $this->_table = $tbl;
        $this->_table_alias = $tbl;
        self::set_disable_record();
    }

    public function get( $column, $idx = 0 ){
        return $this->_rows[$idx][$column];    
    }

    public function set_join( $join_table, $option = "inner" ){
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
        if( tbl_alias != null )
        {
            $column = $tbl_alias . $column;
        }
        if( $column_alias != null )
        {
            $column .= " as $column_alias ";
        }
        $this->_select[] = $column;
    }

    public function add_where( $column, $value = 0, $right_operator = "=", $left_operator = "and" )
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
        self::add_where( "flg_delete" );
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
class Bookmark extends BaseEntity{

}

$obj = new Bookmark();
$obj->add_where( "id", 1, ">="  );
$obj->set_order( "id desc", "created" );
$obj->set_option( "limit 100" );
$obj->set_option( "offset 0" );
$obj->to_entity();
echo " url:".$obj->get("url");
echo "\n comment:".$obj->get("comment");
echo "\n";
/**/
