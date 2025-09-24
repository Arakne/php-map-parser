<?php

namespace Arakne\MapParser\Sprite;

use GdImage;

use Imagick;

use function assert;
use function ceil;
use function floor;
use function getenv;
use function imagecreatefromstring;
use function imageflip;
use function imagerotate;
use function imagesavealpha;
use function imagescale;

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
         * This value must be non-negative.
         */
        public readonly float $width,

        /**
         * The height of the sprite in pixels.
         * Note: SWF are in twips (1/20th of a pixel), but here we use pixels, so this value is 1/20th of the SWF value.
         *
         * This value must be non-negative.
         */
        public readonly float $height,

        /**
         * The X offset of the sprite in pixels.
         * This value can be negative.
         */
        public readonly float $offsetX,

        /**
         * The Y offset of the sprite in pixels.
         * This value can be negative.
         */
        public readonly float $offsetY,

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
        if ($this->gd) {
            return $this->gd;
        }

        $gd = imagecreatefromstring($this->pngData);
        assert($gd !== false);
        imagesavealpha($gd, true);

        return $gd;
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
        assert($gd !== false);
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

    /**
     * Create a new sprite with the rotation applied.
     *
     * @param int<1, 3> $rotation Rotation in counts of quarter turns (90 degrees), clockwise.
     *
     * @return self
     */
    public function rotate(int $rotation): self
    {
        $gd = imagecreatefromstring($this->pngData);
        assert($gd !== false);

        $gd = $this->doRotate($gd, $rotation * 90);

        // 180deg rotation: only change offsets, no need to rescale
        if ($rotation === 2) {
            $sprite = new self(
                id: $this->id,
                pngData: self::EMPTY_PNG,
                width: $this->width,
                height: $this->height,
                offsetX: - $this->offsetX - $this->width,
                offsetY: - $this->offsetY - $this->height,
                state: $this->state,
            );

            imagesavealpha($gd, true);
            $sprite->gd = $gd;

            return $sprite;
        }

        $width = ceil($this->height * 1.9286);
        $height = ceil($this->width * 0.5185);

        if ($rotation === 1) {
            // 90deg rotation
            $offsetX = ceil($this->offsetY * -1.9286 - $width);
            $offsetY = floor($this->offsetX * 0.5185);
        } else {
            // 270deg rotation
            $offsetX = floor($this->offsetY * 1.9286);
            $offsetY = ceil($this->offsetX * -0.5185 - $height);
        }

        // IMG_BESSEL shows the best results
        $gd = imagescale($gd, (int) $width, (int) $height, IMG_BESSEL);
        assert($gd !== false);

        $sprite = new self(
            id: $this->id,
            pngData: self::EMPTY_PNG,
            width: $width,
            height: $height,
            offsetX: $offsetX,
            offsetY: $offsetY,
            state: $this->state,
        );

        imagesavealpha($gd, true);
        $sprite->gd = $gd;

        return $sprite;
    }

    /**
     * Rotate the image by the given angle, clockwise.
     *
     * In some environments GD rotation is broken, so we can fallback to Imagick if needed.
     * Set the FIX_ROTATE_IMAGICK=1 environment variable to force using Imagick.
     *
     * @param GdImage $img The image to rotate
     * @param int $angle Angle in degrees, clockwise
     *
     * @return GdImage
     */
    private function doRotate(GdImage $img, int $angle): GdImage
    {
        static $useImagick = getenv('FIX_ROTATE_IMAGICK') == '1';

        if (!$useImagick) {
            // GD rotates counter-clockwise, so negate the rotation
            $gd = imagerotate($img, 360 - $angle, 0);
            assert($gd !== false);

            return $gd;
        }

        // Fallback to Imagick if GD rotation is broken
        ob_start();
        imagesavealpha($img, true);
        imagepng($img);
        $data = ob_get_clean();

        $im = new \Imagick();
        $im->readImageBlob($data);
        $im->setImageFormat('png');
        $im->rotateImage('transparent', $angle);
        $im->setImagePage(0, 0, 0, 0);
        $im->setImageFormat('png');

        $gd = imagecreatefromstring($im->getImageBlob());
        imagesavealpha($img, true);
        assert($gd !== false);

        return $gd;
    }
}
