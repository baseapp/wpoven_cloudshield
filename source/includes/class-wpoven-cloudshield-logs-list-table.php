<?php

//namespace WPOVEN\LIST\TABLE;

//use WP_List_Table;

class WPOven_CloudShield_Logs_List_Table extends WP_List_Table
{
    private $table_data;

    public function __construct() {
        parent::__construct([
            'singular' => 'log',
            'plural'   => 'logs',
            'ajax'     => false
        ]);
    }

    // Define table columns
    function get_columns()
    {
        $columns = array(
            'cb'                 => '<input type="checkbox" />',
            'url'                => __('URL', 'WPOven CloudShield'),
            'status_code'                => __('Status', 'WPOven CloudShield'),
            'ip_address'         => __('IP Address', 'WPOven CloudShield'),
            'timestamp'          => __('Timestamp', 'WPOven CloudShield'),
            'action'             => ''
        );
        return $columns;
    }

    // Output the content of the "Actions" column
    protected function column_action($item)
    {
        $delete = sprintf(
            '<button type="submit" title="Delete Log" class="button" value="%s" id="delete" name="delete" style="color:red;">
            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 4.66683H3.33333V13.3335C3.33333 13.6871 3.47381 14.0263 3.72386 14.2763C3.97391 14.5264 4.31304 14.6668 4.66667 14.6668H11.3333C11.687 14.6668 12.0261 14.5264 12.2761 14.2763C12.5262 14.0263 12.6667 13.6871 12.6667 13.3335V4.66683H4ZM6.66667 12.6668H5.33333V6.66683H6.66667V12.6668ZM10.6667 12.6668H9.33333V6.66683H10.6667V12.6668ZM11.0787 2.66683L10 1.3335H6L4.92133 2.66683H2V4.00016H14V2.66683H11.0787Z" fill="currentColor"/>
            </svg>
        </button>',
            $item['id']
        );
        //    $modal = $this->data_modal($item);
        $actions = array(
            '<div class="alignright">' . $delete . '</div>'
        );

        return $this->row_actions($actions, true);
    }

    // Bind table with columns, data and all
    function prepare_items()
    {
        global $wpdb;
        $table_name = esc_sql($wpdb->prefix . 'cloudshield_logs');

        $this->table_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_name}"), ARRAY_A);

        if (isset($_POST['s']) && !empty($_POST['s'])) {
            $search_term = sanitize_text_field($_POST['s']);
            $search_columns = array('url', 'status_code', 'ip_address', 'timestamp');

            // Escape the search term and create wildcards for the LIKE condition
            $search_wildcards = '%' . $wpdb->esc_like($search_term) . '%';

            $conditions = [];
            $args = []; // Prepare arguments for the placeholders

            foreach ($search_columns as $column) {
                // Prepare each condition with the appropriate placeholder
                $conditions[] = "$column LIKE %s";
                $args[] = $search_wildcards; // Append the escaped search term for each column
            }
            // Combine conditions with "OR"
            $where_clause = implode(' OR ', $conditions);
            $this->table_data = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_name} WHERE $where_clause", ...$args), ARRAY_A);
        }

        if (isset($_POST['action']) == 'delete_all' || isset($_POST['delete'])) {
            if (isset($_POST['element']) && $_POST['action'] == 'delete_all') {
                $selectedLogIds = array_map('absint', $_POST['element']);
                foreach ($selectedLogIds as $logId) {
                    $wpdb->delete($table_name, array('id' => $logId), array('%d'));
                }
                echo '<div class="updated notice"><p>' . count($selectedLogIds) . '&nbsp;Rows deleted successfully!</p></div>';
            }
            if (isset($_POST['delete'])) {
                $id =  $_POST['delete'];
                $wpdb->delete($table_name, array('id' => $id), array('%d'));
                echo '<div class="updated notice"><p>1&nbsp;Rows deleted successfully!</p></div>';
            }
        }

        $columns = $this->get_columns();
        $subsubsub = $this->views();
        $hidden = (is_array(get_user_meta(get_current_user_id(), 'aaa', true))) ? get_user_meta(get_current_user_id(), 'dff', true) : array();
        $sortable = $this->get_sortable_columns();
        $primary  = 'id';
        $this->_column_headers = array($columns, $hidden, $sortable, $primary);

        usort($this->table_data, array(&$this, 'usort_reorder'));

        /* pagination */
        $per_page = $this->get_items_per_page('elements_per_page', 15);
        $current_page = $this->get_pagenum();
        $total_items = count($this->table_data);

        $this->table_data = array_slice($this->table_data, (($current_page - 1) * $per_page), $per_page);

        $this->set_pagination_args(array(
            'total_items' => $total_items, // total number of items
            'per_page'    => $per_page, // items to show on a page
            'total_pages' => ceil($total_items / $per_page) // use ceil to round up
        ));

        $this->items = $this->table_data;
    }

    //Get column default
    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
                return $item['id'];
            case 'url':
                return '<a href="' . $item["url"] . '" target="_blank">' . $item["url"] . '</a>';
            case 'status_code':
                return $item['status_code'];
            case 'ip_address':
                return '<a href="https://ipinfo.io/' . $item["ip_address"] . '" target="_blank">' . $item["ip_address"] . '</a>';
            case 'timestamp':
                return $item['timestamp'];
            default:
                return $item[$column_name];
        }
    }
    // Get checkbox
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="element[]" value="%s" />',
            $item['id']
        );
    }

    // Sorting columns
    function get_sortable_columns()
    {
        $sortable_columns = array(
            'url'  => array('url', true),
            'status_code' => array('status_code', true),
            'ip_address' => array('ip_address', true),
            'timestamp'   => array('timestamp', true)
        );
        return $sortable_columns;
    }

    function get_bulk_actions()
    {
        $actions = array(
            'delete_all'    => __('Delete', 'WPOven CloudShield'),
        );
        return $actions;
    }

    // Sorting function
    function usort_reorder($a, $b)
    {
        $time = (!empty($_GET['timestamp'])) ? $_GET['timestamp'] : 'timestamp';
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'desc';
        $result = strcmp($a[$time], $b[$time]);


        return ($order === 'asc') ? $result : -$result;
    }

    function views()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cloudshield_logs';

        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $row_count = 0;
        } else {
            $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        }

        echo '<ul class="subsubsub">';
        echo sprintf(
            '<a type="button" style="color:#0073aa;" href="?page=view-cloudshield-logs">All&nbsp;(%s)</a>',
            esc_html($row_count)
        );
        echo '</ul>';
    }
}
