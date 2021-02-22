<?php
/*
Plugin Name: WPDA Extension by CWG
Description: An alternative search algorithm for WP Data Access, see README.md
Version: 1.2.0
Plugin URI: https://github.com/CharlesGodwin/wpda-cwg-extensions
Author: Charles Godwin
Copyright (c) 2020 Charles Godwin <charles@godwin.ca> All Rights Reserved.

This is free and unencumbered software released into the public domain.

Anyone is free to copy, modify, publish, use, compile, sell, or
distribute this software, either in source code form or as a compiled
binary, for any purpose, commercial or non-commercial, and by any
means.

In jurisdictions that recognize copyright laws, the author or authors
of this software dedicate any and all copyright interest in the
software to the public domain. We make this dedication for the benefit
of the public at large and to the detriment of our heirs and
successors. We intend this dedication to be an overt act of
relinquishment in perpetuity of all present and future rights to this
software under copyright law.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.

For more information, please refer to <https://unlicense.org>
 */
class WPDA_cwg_extensions
{
    public function __construct()
    {

        /*
         * the filter must run at priority 9 to ensure it runs before the default filter which runs at 10
         */
        add_filter('wpda_construct_where_clause', array($this, 'construct_where_clause'), 9, 5);
        $this->trace = false; /* change this to TRUE if you want to debug */
    }

    public function construct_where_clause($where_clause, $schema_name, $table_name, $columns, $search_value)
    {
        if ($where_clause !== '') {
            if ($this->trace) {
                error_log(__FUNCTION__ . " where clause is already valued: " . $where_clause);
            }
            return $where_clause;
        }

        if (function_exists('wpda_fremius') && wpda_fremius()->is__premium_only()) {
            return $this->construct_where_clause_premium($where_clause, $schema_name, $table_name, $columns, $search_value);
        } else {
            if ($search_value == null || trim($search_value) === '') {
                if ($this->trace) {
                    error_log(__FUNCTION__ . " empty search string");
                }
                return $where_clause;
            }
            return $this->construct_where_clause_free($where_clause, $schema_name, $table_name, $columns, $search_value);
        }
    }

    private function construct_where_clause_free($where_clause, $schema_name, $table_name, $columns, $search_value)
    {
        if ($this->trace) {
            error_log(__FUNCTION__ . " entered: " . $search_value);
        }
        $likes = [];
        foreach ($columns as $column) {
            switch ($column['data_type']) {
                // Use the premium version to search these types of fields
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                case 'int':
                case 'bigint':
                case 'float':
                case 'double':
                case 'decimal':
                case 'time':
                case 'enum':
                case 'set':
                case 'year':
                case 'date':
                case 'datetime':
                case 'timestamp':
                    break;
                default:
                    $likes[] = "`{$column['column_name']}` like '%%%s%%'";
            }
        }
        if (count($likes) == 0) {
            return $where_clause;
        }
        /*
        parse search request as if it was a space delimited CSV row
         */
        $tokens = str_getcsv(trim($search_value), ' ');
        $tokens = array_filter($tokens, function ($token) {
            return !($token == null || (strlen(trim($token)) === 0));
        });

        if (count($tokens) === 0) {
            return $where_clause;
        }
        global $wpdb;
        $queries = array();
        foreach ($tokens as $token) {
            /*
             * 2020/02/19 CWG Revised to unescape the single quote like O'Brian
             */
            $token  = str_replace("&#039;", "\'", esc_attr($token));
            $wheres = array();
            foreach ($likes as $like) {
                $wheres[] = $wpdb->prepare($like, $token);
            }
            $queries[] = "(" . join(" OR ", $wheres) . ")";
        }
        $where = '(' . join(" AND ", $queries) . ')';
        if ($this->trace) {
            error_log(__FUNCTION__ . " Where=>" . $where);
        }
        return $where;
    }

    private function construct_where_clause_premium($where_clause, $schema_name, $table_name, $columns, $search_value)
    {
        if ($this->trace) {
            error_log(__FUNCTION__ . ' entered: ' . $where_clause . ', ' . $schema_name . ', ' . $table_name . ', ' . print_r($columns, true) . ', ' . $search_value);
        }

        $premium        = false;
        $table_settings = \WPDataAccess\Plugin_Table_Models\WPDA_Table_Settings_Model::query($table_name, $schema_name);
        if ($this->trace) {
            error_log(__FUNCTION__ . " " . print_r($table_settings, true));
        }
        if (0 < sizeof($table_settings) && isset($table_settings[0]['wpda_table_settings'])) {
            $table_settings = json_decode($table_settings[0]['wpda_table_settings']);
            if ($table_settings->search_settings->search_type !== "normal") {
                return $where_clause; // Only process normal filter
            }
            if (isset($table_settings->search_settings)) {
                if (isset($table_settings->search_settings->search_columns)) {
                    $premium        = true;
                    $search_columns = $table_settings->search_settings->search_columns;
                }
            }
        }

        if (!$premium) {
            if ($this->trace) {
                error_log(__FUNCTION__ . ' Not premium');
            }
            return null;
        }
        if (isset($table_settings->search_settings->no_search_no_rows)) {
            $no_search_no_rows = $table_settings->search_settings->no_search_no_rows;
            if ($this->trace) {
                error_log(__FUNCTION__ . ' no_search_no_rows: ' . $no_search_no_rows);
            }
        } else {
            $no_search_no_rows = false;
        }

        if (isset($table_settings->search_settings->column_specific_search)) {
            $column_specific_search = $table_settings->search_settings->column_specific_search;
            if ($this->trace) {
                error_log(__FUNCTION__ . ' column_specific_search: ' . $column_specific_search);
            }

        } else {
            $column_specific_search = false;
        }
        if ($column_specific_search) {
            $where_search_args = WPDA_cwg_extensions::add_wpda_search_args($columns);
            if ($this->trace) {
                error_log(__FUNCTION__ . ' where_search_args: ' . $where_search_args);
            }
        } else {
            $where_search_args = '';
        }

        if (
            $no_search_no_rows &&
            (('' === trim($search_value) || null === $search_value) && $where_search_args === '')
        ) {
            // No search criteria entered + no search no rows
            if ($this->trace) {
                error_log(__FUNCTION__ . ' empty search');
            }
            return '(1=2)'; // empty
        }

        $likes    = [];
        $numerics = [];
        foreach ($columns as $column) {
            $column_name = $column['column_name'];
            if (in_array($column_name, $search_columns)) {
                switch ($column['data_type']) {
                    // All columns that get here will be searched
                    // Comment out the case statement to include the column type in the search
                    case 'tinyint':
                    case 'smallint':
                    case 'mediumint':
                    case 'int':
                    case 'bigint':
                    case 'float':
                    case 'double':
                    case 'decimal':
                    case 'year':
                        $numerics[] = "`{$column['column_name']}` = %f";
                        break;
                    case 'time':
                    case 'enum':
                    case 'date':
                    case 'datetime':
                    case 'timestamp':
                        $likes[] = "`{$column['column_name']}` like %s";
                        break;
                    default:
                        $likes[] = "`{$column['column_name']}` like %s";
                }
            }
        }
        if (count($likes) !== 0 || count($numerics) !== 0) {
            /*
            parse search request as if it was a space delimited CSV row
             */
            $tokens = str_getcsv(trim($search_value), ' ');
            $tokens = array_filter($tokens, function ($token) {
                return !($token == null || (strlen($token) === 0));
            });

            if (count($tokens) !== 0) {
                global $wpdb;
                $queries = array();
                foreach ($tokens as $token) {
                    /*
                     * 2020/02/19 CWG Revised to unescape the single quote like O'Brian
                     */
                    $token  = esc_attr(str_replace("&#039;", "\'", $token));
                    $wheres = array();
                    $string = '%' . $token . '%';
                    foreach ($likes as $like) {
                        $wheres[] = $wpdb->prepare($like, $string);
                    }
                    if (is_numeric($token)) {
                        $float = floatval($token);
                        foreach ($numerics as $numeric) {
                            $wheres[] = $wpdb->prepare($numeric, $float);
                        }
                    }
                    $queries[] = "(" . join(" OR ", $wheres) . ")";
                }
                if (count($queries) !== 0) {
                    $where_clause = '(' . join(" AND ", $queries) . ')';
                }
            }
        }

        if (null !== $where_clause) {
            if ('' !== $where_search_args) {
                if ('' === $where_clause) {
                    $where_clause = $where_search_args;
                } else {
                    $where_clause = "{$where_clause} and {$where_search_args}";
                }
            }
        }

        if ($this->trace) {
            error_log(__FUNCTION__ . " Where=>" . $where_clause);
        }
        return $where_clause;
    }

    public static function add_wpda_search_args($columns)
    {
        $where_columns = [];
        if (is_array($columns)) {
            global $wpdb;
            foreach ($columns as $column) {
                $column_name = $column['column_name'];
                if (isset($_REQUEST["wpda_search_{$column_name}"])) {
                    $column_date_type = $column['data_type'];
                    $column_value     = sanitize_text_field(wp_unslash($_REQUEST["wpda_search_{$column_name}"])); // input var okay.
                    if ('' !== $column_value) {
                        switch ($column['data_type']) {
                            // All columns that get here will be searched
                            // Comment out the case statement to include the column type in the search
                            case 'tinyint':
                            case 'smallint':
                            case 'mediumint':
                            case 'int':
                            case 'bigint':
                            case 'float':
                            case 'double':
                            case 'decimal':
                            case 'year':
                                $where_columns[] = $wpdb->prepare("`{$column_name}` = %f", esc_attr($column_value)); // WPCS: unprepared SQL OK.
                                break;
                            case 'time':
                            case 'enum':
                            case 'date':
                            case 'datetime':
                            case 'timestamp':
                                $where_columns[] = $wpdb->prepare("`{$column_name}` like %s", '%' . esc_attr($column_value) . '%'); // WPCS: unprepared SQL OK.
                                break;
                            default:
                                $where_columns[] = $wpdb->prepare("`{$column_name}` like %s", '%' . esc_attr($column_value) . '%'); // WPCS: unprepared SQL OK.
                        }
                    }
                }
            }
        }

        if (0 === count($where_columns)) {
            return '';
        } else {
            return ' (' . implode(' and ', $where_columns) . ') ';
        }
    }
}
$wpda_cwg_extensions = new WPDA_cwg_extensions();
