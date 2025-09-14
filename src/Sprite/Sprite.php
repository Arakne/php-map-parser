<?php

namespace Arakne\MapParser\Sprite;

use GdImage;

use function assert;
use function imagecreatefromstring;
use function imageflip;

/**
 * Represents a single sprite extracted from gfx SWF files.
 */
final class Sprite
{
    public const string EMPTY_PNG = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a\x00\x00\x00\x0d\x49\x48\x44\x52\x00\x00\x00\x01\x00\x00\x00\x01\x01\x03\x00\x00\x00\x25\xdb\x56\xca\x00\x00\x00\x03\x50\x4c\x54\x45\x00\x00\x00\xa7\x7a\x3d\xda\x00\x00\x00\x01\x74\x52\x4e\x53\x00\x40\xe6\xd8\x66\x00\x00\x00\x0a\x49\x44\x41\x54\x08\xd7\x63\x60\x00\x00\x00\x02\x00\x01\xe2\x21\xbc\x33\x00\x00\x00\x00\x49\x45\x4e\x44\xae\x42\x60\x82";

    /**
     * True if the sprite is valid and can be rendered.
     */
    public bool $valid {
        get => $this->state === SpriteState::Valid;
    }

    private ?GdImage $gd = null;

    public function __construct(
        /**
         * The sprite ID.
         * Corresponds to the exported asset ID in the SWF.
         *
         * Note: IDs are not shared between ground and object SWF files, so the same ID can exist in both types of files.
         */
        public readonly int $id,

        /**
         * The PNG data as raw binary string.
         */
        public readonly string $pngData,

        /**
         * The width of the sprite in pixels.
         * Note: SWF are in twips (1/20th of a pixel), but here we use pixels, so this value is 1/20th of the SWF value.
         *
         * @var positive-int
         */
        public readonly int $width,

        /**
         * The height of the sprite in pixels.
         * Note: SWF are in twips (1/20th of a pixel), but here we use pixels, so this value is 1/20th of the SWF value.
         *
         * @var positive-int
         */
        public readonly int $height,

        /**
         * The X offset of the sprite in pixels.
         * This value can be negative.
         */
        public readonly int $offsetX,

        /**
         * The Y offset of the sprite in pixels.
         * This value can be negative.
         */
        public readonly int $offsetY,

        /**
         * State of the sprite.
         * Only {@see SpriteState::Valid} sprites can be rendered.
         */
        public readonly SpriteState $state,
    ) {}

    /**
     * Get the GD image resource for this sprite.
     *
     * This method will create the GD image on first call and cache it for subsequent calls.
     * So do not apply any modifications to the returned image.
     */
    public function gd(): GdImage
    {
        return $this->gd ??= imagecreatefromstring($this->pngData);
    }

    /**
     * Create a new sprite with the horizontal flip applied.
     *
     * Use {@see Sprite::gd()} to get the flipped image.
     * OffsetX is also adjusted to match the flip.
     *
     * @return self
     */
    public function flip(): self
    {
        $gd = imagecreatefromstring($this->pngData);
        imageflip($gd, IMG_FLIP_HORIZONTAL);

        $self = new self(
            id: $this->id,
            pngData: self::EMPTY_PNG,
            width: $this->width,
            height: $this->height,
            offsetX: -$this->offsetX - $this->width,
            offsetY: $this->offsetY,
            state: $this->state,
        );
        $self->gd = $gd;

        return $self;
    }

    /**
     * Create a new invalid sprite with the given state.
     *
     * @param int $id Sprite ID
     * @param SpriteState $state State of the sprite, must not be {@see SpriteState::Valid}
     *
     * @return self
     */
    public static function invalid(int $id, SpriteState $state): self
    {
        assert($state !== SpriteState::Valid);

        return new self(
            id: $id,
            pngData: self::EMPTY_PNG,
            width: 0,
            height: 0,
            offsetX: 0,
            offsetY: 0,
            state: $state,
        );
    }
}
