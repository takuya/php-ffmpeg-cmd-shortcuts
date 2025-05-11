<?php

namespace Takuya\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Takuya\Tests\assertions\AssertStringStartWith;
use Takuya\Tests\assertions\GetCountOfAssertions;

abstract class TestCase extends BaseTestCase {


  // Custom Assertions
  use AssertStringStartWith;
  use GetCountOfAssertions;
}
