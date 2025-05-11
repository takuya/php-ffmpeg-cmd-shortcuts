<?php

namespace Takuya\Tests\assertions;

trait GetCountOfAssertions {

  public static function getCountOfAssertions():int {
    return static::getCount();
  }
}
