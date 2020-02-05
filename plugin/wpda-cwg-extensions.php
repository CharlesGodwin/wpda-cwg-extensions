<?php
/*
Plugin Name: CWG Search Extension for WPDA
Description: An alternative search algorythm for WP Data Access
Version: 0.9.3
Author: Charles Godwin
Copyright (c) 2020 Charles Godwin <charles@godwin.ca> All Rights Reserved.

his is free and unencumbered software released into the public domain.

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

function cwg_construct_where_clause($where_clause,
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
                break;
            // next 4 are argueable
            case 'year':
            case 'date':
            case 'datetime':
            case 'timestamp':
            default:
                $column_name = $column['column_name'];
                $likes[] ="`$column_name` like '%$field%'";
        }
    }
    if (count($likes) == 0) {
        return $where_clause;
    }

    /*
    parse search request as if it was a space delimited CSV row
     */
    $tokenarray = str_getcsv(trim($search_value), ' ');
    $tokenarray = array_filter($tokenarray);
    $tokens = array();
    foreach ($tokenarray as $token) {
        if (strlen(trim($token)) === 0) {
            continue; // skip empty string
        }
        $tokens[] = esc_attr($token);
    }
    if (count($tokens) == 0) {
        return $where_clause;
    }
    $queries = array();
    foreach ($tokens as $token) {
        $wheres = array();
        foreach ($likes as $like) {
            $wheres[] = str_replace($field, $token, $like);
        }
        $queries[] = "(" . join(" OR ", $wheres) . ")";
    }
    $where = '(' . join(" AND ", $queries) . ')'; 
    // error_log(print_r($where, true));
    return $where;
}

add_filter('wpda_construct_where_clause', 'cwg_construct_where_clause', 10, 5);

