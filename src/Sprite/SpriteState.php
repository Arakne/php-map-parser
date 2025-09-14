<?php

namespace Arakne\MapParser\Sprite;

enum SpriteState
{
    case Valid;
    case Empty;
    case Missing;
    case Invalid;
}
