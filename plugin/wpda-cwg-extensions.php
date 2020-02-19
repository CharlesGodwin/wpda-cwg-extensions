<?php
/*
Plugin Name: WPDA Extension by CWG
Description: An alternative search algorythm for WP Data Access
Version: 1.0.0
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

function wpda_cwg_construct_where_clause($where_clause,
                                            $schema,
                                            $table,
                                            $columns,
                                            $search_value) {
    // error_log("cwg-search entered:" . $search_value);
    if ($where_clause !== '') {
        return $where_clause;
    }
    if ($search_value == null || trim($search_value) === '') {
        return $where_clause;
    }
    $likes = [];
    $field = '!!token!!';
    global $wpdb;
    foreach ($columns as $column) {
        switch ($column['data_type']) {
            // Comment out the case statement to include the column type in the search
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
            // next 4 are argueably searchable
            case 'year':
            case 'date':
            case 'datetime':
            case 'timestamp':
                break;
            default:
                $likes[] ="`{$column['column_name']}` like '%$field%'";
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
    $queries = array();
    foreach ($tokens as $token) {
        /*
         * 2020/02/19 CWG Revised to unescape the single quote like O'Brian
         */
        $token = str_replace("&#039;","\'",esc_attr($token));
        $wheres = array();
        foreach ($likes as $like) {
            $wheres[] = str_replace($field, $token, $like);
        }
        $queries[] = "(" . join(" OR ", $wheres) . ")";
    }
    $where = '(' . join(" AND ", $queries) . ')';
    return $where;
}

add_filter('wpda_construct_where_clause', 'wpda_cwg_construct_where_clause', 10, 5);
