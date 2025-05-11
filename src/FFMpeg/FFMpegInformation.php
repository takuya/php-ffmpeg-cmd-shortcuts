<?php

namespace Takuya\FFMpeg;

use UnexpectedValueException;
use Takuya\ProcessExec\ExecArgStruct;
use Takuya\ProcessExec\ProcessExecutor;
use function Takuya\Helpers\String\str_cut;
use function Takuya\Helpers\String\str_grep;
use function Takuya\Helpers\String\str_head;
use function Takuya\Helpers\Array\array_to_assoc;

class FFMpegInformation {

  protected string $cmd = 'ffprobe';

  public function __construct () { }

  public function encoder ( $codec ) {
    $p = new ProcessExecutor( new ExecArgStruct( ...[$this->cmd, '-h', "encoder=$codec"] ) );
    $p->start();
    $out = $p->getOutput();
    if ( str_contains( $out, 'not recognized' ) ) {
      throw new UnexpectedValueException( $out );
    }
    preg_match_all( '/Encoder\s(.+) \[(.+)]:\n/', $out, $m );
    $header_line = $m[0];
    $names = $m[1];
    $desc = $m[2];
    $options = array_values( array_filter( preg_split( '/Encoder.+:\n/', $out ), fn( $e ) => !empty( $e ) ) );
    $matched_encoder_list = [];
    foreach ( range( 0, sizeof( $names ) - 1 ) as $idx ) {
      $matched_encoder_list[$idx] = [
        'name'    => $names[$idx],
        'desc'    => $desc[$idx],
        'options' => $header_line[$idx].$options[$idx],
      ];
    }

    return $matched_encoder_list;
  }

  public function videoEncoders () {
    $p = new ProcessExecutor( new ExecArgStruct( ...[$this->cmd, '-encoders'] ) );
    $p->start();
    $out = $p->getOutput();
    $ret = array_map( 'trim', preg_grep( "/^\s+V.+\(\s*codec\s/", explode( "\n", $out ) ) );
    $ret = array_values(
      array_map( function( $str ) {
        preg_match( '/^(\S{6})\s(\S+)\s+(.+)\((codec\s(\S+))\)/', $str, $m );

        return ['encoder' => $m[2], 'codec' => $m[5], 'name' => $m[3]];
      }, $ret ) );

    return $ret;
  }

  public function Formats ( $search = null ) {
    $p = new ProcessExecutor( new ExecArgStruct( ...[$this->cmd, '-formats'] ) );
    $p->start();
    $out = $p->getOutput();
    $en = array_map( fn( $e ) => str_cut( $e, 3 ), str_grep( $out, '/E\s+/' ) );
    $de = array_map( fn( $e ) => str_cut( $e, 3 ), str_grep( $out, '/\s+D/' ) );
    $names = array_unique( array_merge( $en, $de ) );
    unset( $names['='] );
    sort( $names );
    $f = [];
    foreach ( $names as $n ) {
      in_array( $n, $de ) && $f[$n][] = 'Decode';
      in_array( $n, $en ) && $f[$n][] = 'Encode';
    }
    if ( $search ) {
      return array_filter( $f, fn( $e ) => preg_match( "/$search/", $e ), ARRAY_FILTER_USE_KEY );
    } else {
      return $f;
    }
  }

  public function defaultMuxer ( $search_muxer = 'mp4' ) {
    if ( !in_array( $search_muxer, $this->Muxers() ) ) {
      throw new UnexpectedValueException( "'$search_muxer' is not found." );
    }
    $p = new ProcessExecutor( new ExecArgStruct( ...[$this->cmd, '-h', "muxer=$search_muxer"] ) );
    $p->start();
    $h = array_map(
      fn( $e ) => array_map( fn( $a ) => trim( $a, ",. \n\r\t\v\x00" ), explode( ':', $e ) ),
      str_grep( str_head( $p->getOutput(), 5 ), '/^\s/' ) );

    return array_to_assoc( $h );
  }

  public function Muxers () {
    $p = new ProcessExecutor( new ExecArgStruct( ...[$this->cmd, '-muxers'] ) );
    $p->start();

    return array_values( array_map( fn( $e ) => str_cut( $e, 3 ), str_grep( $p->getOutput(), '/^\s+E\s+/' ) ) );
  }
}
