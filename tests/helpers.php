<?php
if( ! function_exists('test_path') ) {
  function test_path():string {
    return __DIR__;
  }
}
if( ! function_exists('test_data_path') ) {
  function test_data_path():string {
    return test_path().'/test-data';
  }
}
if( ! function_exists('test_data') ) {
  function test_data( $name ):string {
    return realpath(test_data_path().'/'.$name);
  }
}
if( ! function_exists('get_call_stack') ) {
  function get_call_stack() {
    return join(
      "\n",
      array_map(
        fn( $e ) => "{$e['file']}:{$e['line']} {$e['function']}",
        debug_backtrace(0)));
  }
}

