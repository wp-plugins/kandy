<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
require_once (KANDY_PLUGIN_DIR . "/api/kandy-api-class.php");
class AssignTableList extends WP_List_Table
{

    var $data = array();
    var $limit = 10;
    var $offset = 0;
    function __construct()
    {

        parent::__construct(
            array(
                'singular' => __('book', 'mylisttable'),
                //singular name of the listed records
                'plural'   => __('books', 'mylisttable'),
                //plural name of the listed records
                'ajax'     => false
                //does this table support ajax?
            )
        );
    }

    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'ID':
            case 'username':
            case 'name':
            case 'kandy_user_id':
            case 'action':
                return $item[$column_name];
            default:
                return print_r(
                    $item,
                    true
                ); //Show the whole array for troubleshooting purposes
        }
    }

    function get_columns()
    {
        $columns = array(
            'ID'            => __("ID", 'kandy'),
            'username'      => __('Username', 'kandy'),
            'name'      => __('Name', 'kandy'),
            'kandy_user_id' => __('Kandy User', 'kandy'),
            'action'        => __('Action', 'kandy')
        );
        return $columns;
    }

    function get_data($limit = 10, $offset = 0)
    {
        $result = KandyApi::getUserData($limit, $offset);

        return $result;
    }

    function prepare_items()
    {
        $data = $this->get_data();
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        //pagination
        $per_page = 5;
        $current_page = $this->get_pagenum();
        $total_items = count($data);

        //search
        $searchItem = array_slice(
            $data,
            (($current_page - 1) * $per_page),
            $per_page
        );
        //sorting
        usort($searchItem, array(&$this, 'usort_reorder'));
        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                //WE have to calculate the total number of items
                'per_page'    => $per_page
                //WE have to determine how many items to show on a page
            )
        );
        $this->items = $searchItem;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'booktitle' => array('booktitle', false),
            'author'    => array('author', false),
            'isbn'      => array('isbn', false)
        );
        return $sortable_columns;
    }

    function usort_reorder($a, $b)
    {
        // If no sort, default to title
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'booktitle';
        // If no order, default to asc
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';
        // Determine sort order
        $result = strcmp($a[$orderby], $b[$orderby]);
        // Send final sort direction to usort
        return ($order === 'asc') ? $result : -$result;
    }
}
