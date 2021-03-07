<?php
/**
 * Plugin Name: WPDA XS - Extended Search
 * Description: An alternative search algorithm for WP Data Access, see README.md
 * Version:     1.9
 * Plugin URI:  https://github.com/CharlesGodwin/wpda-cwg-extensions
 * Author:      Charles Godwin
 * Author URI:  https://github.com/CharlesGodwin/wpda-cwg-extensions
 * License URI: https://unlicense.org
 *
 * Copyright (c) 2020-2021 Charles Godwin <charles@godwin.ca> All Rights Reserved.
 *
 * This is free and unencumbered software released into the public domain.
 *
 * Anyone is free to copy, modify, publish, use, compile, sell, or
 * distribute this software, either in source code form or as a compiled
 * binary, for any purpose, commercial or non-commercial, and by any
 * means.
 *
 * In jurisdictions that recognize copyright laws, the author or authors
 * of this software dedicate any and all copyright interest in the
 * software to the public domain. We make this dedication for the benefit
 * of the public at large and to the detriment of our heirs and
 * successors. We intend this dedication to be an overt act of
 * relinquishment in perpetuity of all present and future rights to this
 * software under copyright law.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
 * OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 * ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * For more information, please refer to <https://unlicense.org>
 *
 * @package plugin
 * @author  Charles Godwin
 * @since   1.0.0
 *
 */

class WPDA_XS
{

    public function __construct($premium = false)
    {
        /*
         * the filter must run at priority 9 to ensure it runs before the default filter which runs at 10
         */
        if ($premium) {
            add_filter('wpda_construct_where_clause', array($this, 'construct_where_clause_premium'), 9, 5);
        } else {
            add_filter('wpda_construct_where_clause', array($this, 'construct_where_clause_free'), 9, 5);
        }
        $this->skiptables = null;
    }

    public function construct_where_clause_free($where_clause, $schema_name, $table_name, $columns, $search_value)
    {
        if ($where_clause !== '') {
            return $where_clause;
        }
        if (isset($this->skiptables[$schema_name]) && in_array($table_name, $this->skiptables[$schema_name])) {
            return $where_clause;
        }
        $extended_column_searches = '';
        // $extended_column_searches = $this->add_column_search($columns);
        if ('' === $search || null === $search || !is_array($columns)) {
            return $extended_column_searches ? $extended_column_searches : $where_clause;
        }
        /*
        parse search request as if it was a space delimited CSV row
         */
        $tokens = str_getcsv(trim($search_value), ' ');
        $tokens = array_filter($tokens, function ($token) { //strip empty tokens and ineligible form array
            return !($token == null || (strlen($token) === 0 || $token === "EMPTY" || $token === "NOTEMPTY"));
        });

        if (count($tokens) === 0) {
            return $where_column_filters ? $where_column_filters : $where_clause;
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

        if (count($likes) === 0) {
            return $extended_column_searches ? $extended_column_searches : $where_clause;
        }

        global $wpdb;
        $queries = array();
        foreach ($tokens as $token) {
            $token         = esc_sql($wpdb->esc_like($token));
            $where_clauses = array();
            foreach ($likes as $like) {
                $where_clauses[] = sprintf($like, $token);
            }
            $where_clauses = array_filter($where_clauses, function ($where_clause) { //strip empty tokens and ineligible form array
                return !($where_clause == null || (strlen($where_clause) === 0));
            });

            $queries[] = "(" . join(" OR ", $where_clauses) . ")";
        }
        $where = '(' . join(" AND ", $queries) . ')';
        if ($where_column_filters) {
            $where = "$where AND $extended_column_searches";
        }
        return $where;
    }

    public function construct_where_clause_premium($where_clause, $schema_name, $table_name, $columns, $search_value)
    {
        if ($where_clause !== '') {
            return $where_clause;
        }
        if ($this->check_table($schema_name, $table_name)) {
            return $where_clause;
        }

        $table_settings = \WPDataAccess\Plugin_Table_Models\WPDA_Table_Settings_Model::query($table_name, $schema_name);
        if (0 < sizeof($table_settings) && isset($table_settings[0]['wpda_table_settings'])) {
            $table_settings = json_decode($table_settings[0]['wpda_table_settings']);
            if ($table_settings->search_settings->search_type !== "normal") {
                return $where_clause; // Only process normal filter
            }
            if (isset($table_settings->search_settings)) {
                if (isset($table_settings->search_settings->search_columns)) {
                    $search_columns = $table_settings->search_settings->search_columns;
                }
            }
        }
        if (isset($table_settings->search_settings->no_search_no_rows)) {
            $no_search_no_rows = $table_settings->search_settings->no_search_no_rows;
        } else {
            $no_search_no_rows = false;
        }

        if (isset($table_settings->search_settings->column_specific_search)) {
            $column_specific_search = $table_settings->search_settings->column_specific_search;
        } else {
            $column_specific_search = false;
        }
        if ($column_specific_search) {
            if (isset($settings_object->search_settings->listbox_columns)) {
                $no_like_decoration = (array) $settings_object->search_settings->listbox_columns;
            } else {
                $no_like_decoration = [];
            }

            $where_column_filters = $this->add_column_filters($columns, $no_like_decoration);
        } else {
            $where_column_filters = '';
        }
        $extended_column_searches = '';
        // $extended_column_searches = $this->add_column_search($columns);
        if ($no_search_no_rows && (('' === trim($search_value) || null === $search_value) && $where_column_filters === '')) 
        {
            // No search criteria entered + no search no rows
            return '(1=2)'; // empty
        }
        $tokens = str_getcsv(trim($search_value), ' ');
        $tokens = array_filter($tokens, function ($token) { //strip empty tokens and ineligible Keywords form array
            return !($token == null || (strlen($token) === 0 || $token === "EMPTY" || $token === "NOTEMPTY"));
        });

        if (count($tokens) !== 0) {
            $queries = array();
            foreach ($tokens as $token) {
                $where_clauses = array();
                foreach ($columns as $column) {
                    $column_name = $column['column_name'];
                    if (in_array($column_name, $search_columns)) {
                        $where_clauses[] = $this->build_where($column, $token, array());
                    }
                }
                $where_clauses = array_filter($where_clauses, function ($where_clause) { //strip empty tokens and ineligible form array
                    return !($where_clause == null || (strlen($where_clause) === 0));
                });
                $queries[] = "(" . join(" OR ", $where_clauses) . ")";
            }
            if (count($queries) !== 0) {
                $where_clause = '(' . join(" AND ", $queries) . ')';
            }
        }

        if (null !== $where_clause) {
            if ('' !== $where_column_filters) {
                if ('' === $where_clause) {
                    $where_clause = $where_column_filters;
                } else {
                    $where_clause = "{$where_clause} and {$where_column_filters}";
                }
            }
            if ('' === $where_clause) {
                if ($extended_column_searches) {
                    $where_clause = $extended_column_searches;
                } else {
                    if ($extended_column_searches) {
                        $where_clause = "${where_clause} and {$extended_column_searches}";
                    }
                }
            }
        }
        return $where_clause;
    }

    private function add_column_filters($columns, $no_like_decoration = array())
    {
        $where_columns = [];
        if (is_array($columns)) {
            global $wpdb;
            foreach ($columns as $column) {
                $column_name = $column['column_name'];
                if (isset($_REQUEST["wpda_search_{$column_name}"])) {
                    $where_columns[] = $this->build_where($column, $_REQUEST["wpda_search_column_{$column_name}"], $no_like_decoration);
                }
            }
            $where_columns = array_filter($where_columns, function ($string) {
                return !($string == null || (strlen($string) === 0));
            });
        }

        if (0 === count($where_columns)) {
            return '';
        } else {
            return ' (' . implode(' and ', $where_columns) . ') ';
        }
    }

    private function check_table($schema_name, $table_name)
    {
        if ($this->skiptables === null) {
            $this->skiptables = array();
            $skiptable_list   = get_option('wpda_xs_skiptables');
            if ($skiptable_list) {
                $list = explode(",", $skiptable_list);
                foreach ($list as $table) {
                    $item = explode(".", $table);
                    if (count($item) == 2) {
                        if (!isset($item[0])) {
                            $this->skiptables[$item[0]] = array();
                        }
                        $this->skiptables[$item[0]][] = $item[1];
                    }
                }
            }
        }
        if (count($this->skiptables) > 0 && isset($this->skiptables[$schema_name]) && in_array($table_name, $this->skiptables[$schema_name])) {
            return true;
        } else {
            return false;
        }
    }

/**
 * This builds a single where clause.
 *
 * The actual clause depends on type of data. If the column is a numeric type, $value must be compatible or no where clause is generated.
 *
 * There are two custom keywords, EMPTY and NOTEMPTY.
 *
 * @param $column A single WPDA column object
 * @param $value The value to be inserted into the clause
 * @param $no_like_decoration (optional) A list of columns that should be considered for simple like processing ( like '$value' )
 *
 * @return a string. It may be empty if nothing can bee generated or a simple where clause. It is not parenthesised.
 * @since 2.0
 */
    private function build_where($column, $value, $no_like_decoration = array())
    {
        global $wpdb;
        $column_date_type = $column['data_type'];
        $column_value     = sanitize_text_field(wp_unslash($value));
        if ('' !== $column_value) {
            $column_name = $column['column_name'];
            switch ($column['data_type']) {
                // All columns that get here will be searched
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                case 'int':
                case 'bigint':
                case 'year':
                    if ($column_value == 'EMPTY') {
                        return "`{$column_name}` is NULL";
                    } elseif ($column_value == 'NOTEMPTY') {
                        return "`{$column_name}` is NOT NULL";
                    } else {
                        if (is_numeric($column_value)) {
                            return sprintf("`{$column_name}` = %d", intval($column_value));
                        } else {
                            return '';
                        }
                    }
                case 'float':
                case 'double':
                case 'decimal':
                    if ($column_value == 'EMPTY') {
                        return "`{$column_name}` is NULL";
                    } elseif ($column_value == 'NOTEMPTY') {
                        return "`{$column_name}` is NOT NULL";
                    } else {
                        if (is_numeric($column_value)) {
                            return sprintf("`{$column_name}` = %f", floatval($column_value));
                        } else {
                            return '';
                        }
                    }
                case 'time':
                case 'date':
                case 'datetime':
                case 'timestamp':
                    if ($column_value == 'EMPTY') {
                        return "`{$column_name}` is NULL";
                    } elseif ($column_value == 'NOTEMPTY') {
                        return "`{$column_name}` is NOT NULL";
                    } elseif (in_array($column_name, $no_like_decoration)) {
                        return sprintf("`{$column_name}` like '%s'", esc_sql($column_value));
                    } else {
                        return sprintf("`{$column_name}` like '%s%%'", esc_sql($wpdb->esc_like($column_value)));
                    }
                case 'enum':
                default:
                    if ($column_value == 'EMPTY') {
                        return "(`{$column_name}` is NULL or ${column_name} = '')";
                    } elseif ($column_value == 'NOTEMPTY') {
                        return "`({$column_name}` is NOT NULL and ${column_name} != '')";
                    } elseif (in_array($column_name, $no_like_decoration)) {
                        return sprintf("`{$column_name}` like '%s'", esc_sql($column_value));
                    } else {
                        return sprintf("`{$column_name}` like '%%%s%%'", esc_sql($wpdb->esc_like($column_value)));
                    }
            }
        }
        return '';
    }
    /**
     * Builds where clauses for wpda_search_column_xs_{$column_name} defined in page $_REQUEST
     * @param list of eligible columns
     *
     * Returns a where clause set for all field=value pairs in $_REQUEST. If more than one, it is enclosed in parenthesis.
     *
     * The search value can have like syntax.  (% _)
     *
     * @return ''|string Where clause
     * @since   2.0.0 - suspended
     *
     */
//     private function add_column_search($columns)
//     {
//         $where_columns = [];
//         if (is_array($columns)) {
//             global $wpdb;
//             foreach ($columns as $column) {
//                 $column_name = $column['column_name'];
//                 if (isset($_REQUEST["wpda_search_column_xs_{$column_name}"])) {
//                     $values = explode(',', $_REQUEST["wpda_search_column_xs_{$column_name}"]);
//                     foreach ($values as $value) {
//                         $where_columns[] = $this->build_where($column, $value, array($column_name));
//                     }
//                 }
//             }
//             $where_columns = array_filter($where_columns, function ($string) {
//                 return !($string == null || (strlen($string) === 0));
//             });
//         }

//         if (count($where_columns) === 0) {
//             return '';
//         } elseif (count($where_columns) === 1) {
//             return $where_columns[0];
//         } else {
//             return ' (' . implode(' or ', $where_columns) . ') ';
//         }
//     }
// }
if (is_plugin_active("wp-data-access-premium/wp-data-access.php")) {
    $wpda_xs = new WPDA_XS(true);
} elseif (is_plugin_active("wp-data-access/wp-data-access.php")) {
    $wpda_xs = new WPDA_XS(false);
} else {
    $wpda_xs = null;
}
if ($wpda_xs && is_admin()) {
    require_once "includes/wpda-xs-admin.php";
    $wpda_xs_admin = new WPDA_XS_Admin();
}
