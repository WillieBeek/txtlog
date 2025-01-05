<?php
namespace Txtlog\Includes;

enum Priv:int {
  case Admin = 1;

  case Insert = 2;

  case View = 3;
}
