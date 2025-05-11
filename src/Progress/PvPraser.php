<?php

namespace Takuya\Progress;

class PvPraser {

  public static function parsePvProgress ( $str ): array {
    preg_match(
      '/^\s*(?<trans>[0-9.]+?.iB) +(?<time>[0-9:]+?) +\[(?<rate>.+?)] '.
      '\[(?<bar>[=>\s]+?)] +(?<percent>\d+)%( ETA (?<eta>[\d:]+))?/',
      $str, $match );
    $match = array_filter( $match , function ( $i ) {return is_string($i);} ,ARRAY_FILTER_USE_KEY);
    //$match = collect( $match )->filter( fn( $e, $i ) => is_string( $i ) );
    $progress = [
      "transferred"               => $match['trans'] ?? '0',
      "elapsed_time"              => $match['time'] ?? '0:00:00',
      "bytes_rate"                => $match['rate'] ?? '0',
      "percent"                   => $match['percent'] ?? '0',
      "estimated_time_of_arrival" => $match['eta'] ?? '0:00:00',
    ];
    foreach ( $progress as $k => $v ) {
      $progress[$k] = trim( $v );
    }
    return $progress;
  }
}
