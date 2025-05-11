<?php

namespace Takuya\FFMpeg;

use Takuya\Progress\PvPraser;
use Takuya\ProcessExec\ExecArgStruct;
use Takuya\ProcessExec\ProcessExecutor;
use Takuya\ProcessExec\ProcessObserver;
use Takuya\Progress\FFMpegProgressParse;
use Takuya\ProcessExec\ProcessEvents\Events\ProcessRunning;
use Takuya\ProcessExec\ProcessEvents\Events\ProcessStopped;
use Takuya\ProcessExec\ProcessEvents\Events\ProcessResumed;
use LogicException;
use Closure;
use SplFileObject;
use function Takuya\Helpers\Shell\find_path;
use function Takuya\Helpers\Shell\shell_args_cleanup;
use function Takuya\Helpers\Array\array_equal_values;
use function Takuya\Helpers\Array\array_replace_entry;

class FFMpegEncode {
  
  public mixed    $last_cmd;
  protected array $exitCode;
  /** @var callable */
  protected $on_pv;
  /** @var callable */
  protected $on_ffmpeg;
  /* @var callable */
  protected $pv_opts;
  /* @var callable */
  protected                 $on_start_callback;
  protected ProcessObserver $ffmpeg_observer;
  /**
   * @var false|resource
   */
  protected       $stderr_buff;
  protected array $suspend_resume;
  
  public function __construct(protected $src,
                              protected $dst,
                              protected $decode_opt = '',
                              protected $encode_opt = '-f mp4 -s 1280x720 -vsync 1 -movflags faststart') {
    $this->on_pv = function($pg) { return $pg; };
    $this->on_ffmpeg = function($pg) { return $pg; };
    $this->stderr_buff = new SplFileObject('php://memory', 'w+');
  }
  
  public function setOnStartCallback($callable) {
    $this->on_start_callback = $callable;
  }
  
  public function setPvOpts($opts) {
    $this->pv_opts = $opts;
  }
  
  public function setDecodeOpt(string $decode_opt): void {
    $this->decode_opt = $decode_opt;
  }
  
  public function setEncodeOpt(string $encode_opt): void {
    $this->encode_opt = $encode_opt;
  }
  
  public function setOnPvProgress(callable $callback) {
    $this->on_pv = $callback;
  }
  
  public function setOnFFMpegProgress(callable $callback) {
    $this->on_ffmpeg = $callback;
  }
  
  public function setOnFFMpegResumed(callable $callback): void {
    $fn = fn(ProcessResumed $ev) => call_user_func($callback, $ev->getExecutor());
    $this->getObserver()->addEventListener(ProcessResumed::class, $fn);
  }
  
  protected function getObserver(): ProcessObserver {
    empty($this->ffmpeg_observer) && $this->ffmpeg_observer = new ProcessObserver();
    
    return $this->ffmpeg_observer;
  }
  
  public function suspend_if(Closure $callback): void {
    $this->getObserver();//initialize observer
    $this->suspend_resume = $this->suspend_resume ?? [];
    //
    $fn = fn(ProcessExecutor $executor) => true === $callback() && $executor->suspend();
    $this->suspend_resume['suspend_if'] = $fn;
  }
  
  public function resume_if(Closure $callback): void {
    $this->suspend_resume = $this->suspend_resume ?? [];
    $this->getObserver();//initialize observer
    //
    $fn = fn(ProcessExecutor $executor) => true === $callback() && $executor->resume();
    $this->suspend_resume['resume_if'] = $fn;
  }
  
  public function getStderr(): string {
    return join('', iterator_to_array($this->stderr_buff));
  }
  
  public function start() {
    [$p1, $p2] = $this->preparePipeProcess();
    !empty($this->on_start_callback) && call_user_func($this->on_start_callback, $this, $p1, $p2);
    $p2->start();
    $this->exitCode['pv'] = $p1->getExitCode();
    $this->exitCode['ffmpeg'] = $p2->getExitCode();
  }
  
  protected function preparePipeProcess() {
    $cmds = $this->buildCommand($this->src, $this->dst, $this->decode_opt, $this->encode_opt);
    $pv_cmd = new ExecArgStruct($cmds['pv']);
    $ffmpeg_cmd = new ExecArgStruct($cmds['ffmpeg']);
    $p1 = new ProcessExecutor($pv_cmd);
    $p2 = new ProcessExecutor($ffmpeg_cmd);
    $p1->onStderr(
      fn($line) => strrpos($line, '%') > 0 && call_user_func($this->on_pv, $this->parsePvProgress($line)),
      "\r");
    $p2->onStderr(
      fn($line) => $this->stderr_buff->fwrite($line)
        && strrpos($line, 'time=') > 0
        && call_user_func($this->on_ffmpeg, $this->parseFfmpegProgress($line)),
      "\r");
    !empty($this->ffmpeg_observer) && $this->addObserverInto($p2);
    $p1->pipe($p2);
    //
    return [$p1, $p2];
  }
  
  /*
   * @return [ProcessExecutor $p1,ProcessExecutor $p2]
   */
  
  protected function buildCommand($src, $dst, $decode_opt, $encode_opt): array {
    //
    $pv = [find_path('pv'), '-f', $this->pv_opts];
    $pv = shell_args_cleanup($pv);
    $pv[] = $src;
    //
    $ffmpeg = [find_path('ffmpeg'), '-y -i pipe:0', $decode_opt, $encode_opt, 'OUTPUT',];
    $ffmpeg = array_replace_entry($ffmpeg, 'OUTPUT', $dst);
    $ffmpeg = shell_args_cleanup($ffmpeg);
    //
    $this->last_cmd = join(' | ', [join(" ", $pv), join(" ", $ffmpeg)]);
    
    return ['pv' => $pv, 'ffmpeg' => $ffmpeg];
  }
  
  protected function parsePvProgress($str): array {
    return PvPraser::parsePvProgress($str);
  }
  
  protected function parseFfmpegProgress($str) {
    return FFMpegProgressParse::parseFfmpegProgress($str);
  }
  
  protected function addObserverInto(ProcessExecutor $p2) {
    if( isset($this->suspend_resume) ) {
      if( false === array_equal_values(['suspend_if', 'resume_if'], array_keys($this->suspend_resume)) ) {
        throw new LogicException('suspend_if と resume_ifはペアにすること');
      }
      $this->setOnFFMegRunning($this->suspend_resume['suspend_if']);
      $this->setOnFFMegStopped($this->suspend_resume['resume_if']);
    }
    $p2->addObserver($this->getObserver());
  }
  
  public function setOnFFMegRunning(callable|array $callbacks): void {
    $callbacks = is_array($callbacks) ? $callbacks : [$callbacks];
    foreach ( $callbacks as $callback ) {
      $fn = fn(ProcessRunning $ev) => call_user_func($callback, $ev->getExecutor());
      $this->getObserver()->addEventListener(ProcessRunning::class, $fn);
    }
  }
  
  public function setOnFFMegStopped(callable|array $callbacks): void {
    $callbacks = is_array($callbacks) ? $callbacks : [$callbacks];
    foreach ( $callbacks as $callback ) {
      $fn = fn(ProcessStopped $ev) => call_user_func($callback, $ev->getExecutor());
      $this->getObserver()->addEventListener(ProcessStopped::class, $fn);
    }
  }
  
  public function isSuccessful(): bool {
    return $this->exitCode['pv'] === 0 && $this->exitCode['ffmpeg'] === 0;
  }
}
