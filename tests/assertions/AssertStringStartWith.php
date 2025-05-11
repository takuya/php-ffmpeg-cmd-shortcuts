<?php

namespace Takuya\Tests\assertions;

trait AssertStringStartWith {
  
  public static function assertStringStartWith( string $heystack, $needle ):void {
    static::assertTrue(str_starts_with($heystack, $needle));
  }
}
