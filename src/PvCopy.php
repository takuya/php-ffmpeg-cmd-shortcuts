<?php

namespace Takuya;

use Takuya\Progress\PvPraser;
use InvalidArgumentException;
use Takuya\ProcessExec\ExecArgStruct;
use Takuya\ProcessExec\ProcessExecutor;

use function Takuya\Helpers\Shell\find_path;
use function Takuya\Helpers\Shell\shell_args_cleanup;
use function Takuya\Helpers\Array\array_flatten;
use function Takuya\Helpers\String\empty_str;

class PvCopy {

  protected string $last_cmd;
  /**
   * @var callable
   */
  protected $on_pv;
  protected mixed $pv_opts = '';

  public function __construct ( protected string $src,
                                protected string $dst ) {
    if ( false === is_readable( $src ) ) {
      throw new InvalidArgumentException( 'src('.$src.') is not readable' );
    }
    if ( false === ( is_writable( $dst ) || is_writable( dirname( $dst ) ) ) ) {
      throw new InvalidArgumentException( 'dst('.$dst.') is not writable' );
    }
  }

  public function on_pv_progress ( callable $callback ) {
    $this->on_pv = fn( $line ) => strrpos( $line, '%' ) > 0 && call_user_func( $callback, $this->parsePvProgress( $line ) );
  }

  protected function parsePvProgress ( $str ): array {
    return PvPraser::parsePvProgress( $str );
  }

  public function start () {
    $this->run();
  }

  protected function run () {
    $cmd = $this->buildCommand( $this->src, $this->dst );
    $this->last_cmd = $cmd['pv'];
    $p = new ProcessExecutor( $cmd['args'] );
    $p->onStderr( $this->on_pv, "\r" );
    $p->start();
  }

  protected function buildCommand ( $src, $dst ): array {
    $pv = [find_path( 'pv' ), '-f', $this->pv_opts];
    $pv = shell_args_cleanup( $pv );
    $pv = array_flatten( array_map( fn( $e ) => preg_split( '/\s+/', trim( $e ) ), array_flatten( $pv ) ) );
    $pv = array_filter( $pv, fn( $e ) => !empty_str( $e ) );
    $pv[] = $src;
    $args = new ExecArgStruct( ...$pv );
    $args->setStdout( fopen( $dst, 'w' ) );

    return ['pv' => join( " ", $pv ).' > "'.$dst.'" ', 'args' => $args];
  }

  public function setPvOpts ( $opts ) {
    $this->pv_opts = $opts;
  }
}
