<?php

/*
 * This file is part of PHP Map parser.
 *
 * PHP Map parser is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * PHP Map parser is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with PHP Map parser.
 * If not, see <https://www.gnu.org/licenses/>.
 *
 * Copyright (C) 2019-2025 Vincent Quatrevieux (quatrevieux.vincent@gmail.com)
 */

namespace Arakne\MapParser\Tile\Cache;

use Arakne\MapParser\Tile\Coordinate\Bounds;
use Arakne\MapParser\Tile\TileMapCoordinates;
use Closure;
use GdImage;
use Override;
use PDO;

use function dirname;
use function imagecreatefromstring;
use function is_dir;
use function mkdir;

/**
 * A tile cache using a SQLite database for storage.
 */
final readonly class SqliteTileCache implements TileCacheInterface
{
    private PDO $pdo;

    public function __construct(
        /**
         * The path to the SQLite database file
         */
        private string $path,

        /**
         * The namespace to use for this cache instance
         */
        private string $namespace = '',
    ) {
        if (!is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0755, true);
        }

        $this->pdo = new PDO('sqlite:' . $path);
        $this->pdo->exec('PRAGMA journal_mode = WAL;');

        $this->initSchema();
    }

    #[Override]
    public function bounds(Closure $compute): Bounds
    {
        $stmt = $this->pdo->prepare('SELECT * FROM bounds WHERE namespace = ?');
        $stmt->execute([$this->namespace]);

        if (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            return new Bounds((int) $row['xMin'], (int) $row['xMax'], (int) $row['yMin'], (int) $row['yMax']);
        }

        $bounds = $compute();

        $stmt = $this->pdo->prepare('INSERT INTO bounds (namespace, xMin, xMax, yMin, yMax) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $this->namespace,
            $bounds->xMin,
            $bounds->xMax,
            $bounds->yMin,
            $bounds->yMax,
        ]);

        return $bounds;
    }

    #[Override]
    public function map(TileMapCoordinates $coordinates, Closure $compute): ?GdImage
    {
        $stmt = $this->pdo->prepare('SELECT data FROM maps WHERE namespace = ? AND x = ? AND y = ?');
        $stmt->execute([$this->namespace, $coordinates->x, $coordinates->y]);

        if (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            if ($row['data'] === null) {
                return null;
            }

            $gd = imagecreatefromstring($row['data']);

            if ($gd !== false) {
                return $gd;
            }
        }

        $gd = $compute($coordinates);

        if ($gd === null) {
            $data = null;
        } else {
            ob_start();
            imagepng($gd);
            $data = ob_get_clean();
        }

        $stmt = $this->pdo->prepare('REPLACE INTO maps (namespace, x, y, data) VALUES (?, ?, ?, ?)');
        $stmt->bindValue(1, $this->namespace);
        $stmt->bindValue(2, $coordinates->x, PDO::PARAM_INT);
        $stmt->bindValue(3, $coordinates->y, PDO::PARAM_INT);
        $stmt->bindValue(4, $data, $data === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
        $stmt->execute();

        return $gd;
    }

    #[Override]
    public function fullSizeTile(int $x, int $y, Closure $compute): GdImage
    {
        $stmt = $this->pdo->prepare('SELECT data FROM full_size WHERE namespace = ? AND x = ? AND y = ?');
        $stmt->execute([$this->namespace, $x, $y]);

        if (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $gd = imagecreatefromstring($row['data']);

            if ($gd !== false) {
                return $gd;
            }
        }

        $gd = $compute($x, $y);

        ob_start();
        imagepng($gd);
        $data = ob_get_clean();

        $stmt = $this->pdo->prepare('REPLACE INTO full_size (namespace, x, y, data) VALUES (?, ?, ?, ?)');
        $stmt->bindValue(1, $this->namespace);
        $stmt->bindValue(2, $x, PDO::PARAM_INT);
        $stmt->bindValue(3, $y, PDO::PARAM_INT);
        $stmt->bindValue(4, $data, PDO::PARAM_LOB);
        $stmt->execute();

        return $gd;
    }

    #[Override]
    public function tile(int $x, int $y, int $zoom, Closure $compute): GdImage
    {
        $stmt = $this->pdo->prepare('SELECT data FROM tiles WHERE namespace = ? AND x = ? AND y = ? AND zoom = ?');
        $stmt->execute([$this->namespace, $x, $y, $zoom]);

        if (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $gd = imagecreatefromstring($row['data']);

            if ($gd !== false) {
                return $gd;
            }
        }

        $gd = $compute($x, $y, $zoom);

        ob_start();
        imagepng($gd);
        $data = ob_get_clean();

        $stmt = $this->pdo->prepare('REPLACE INTO tiles (namespace, x, y, zoom, data) VALUES (?, ?, ?, ?, ?)');
        $stmt->bindValue(1, $this->namespace);
        $stmt->bindValue(2, $x, PDO::PARAM_INT);
        $stmt->bindValue(3, $y, PDO::PARAM_INT);
        $stmt->bindValue(4, $zoom, PDO::PARAM_INT);
        $stmt->bindValue(5, $data, PDO::PARAM_LOB);
        $stmt->execute();

        return $gd;
    }

    #[Override]
    public function withNamespace(string $namespace): static
    {
        return new self(
            $this->path,
            $this->namespace . $namespace,
        );
    }

    private function initSchema(): void
    {
        $this->pdo->exec(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS tiles (
                namespace TEXT NOT NULL,
                x INTEGER NOT NULL,
                y INTEGER NOT NULL,
                zoom INTEGER NOT NULL,
                data BLOB NOT NULL,
                PRIMARY KEY (namespace, x, y, zoom)
            );
            SQL,
        );

        $this->pdo->exec(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS full_size (
                namespace TEXT NOT NULL,
                x INTEGER NOT NULL,
                y INTEGER NOT NULL,
                data BLOB NOT NULL,
                PRIMARY KEY (namespace, x, y)
            );
            SQL,
        );

        $this->pdo->exec(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS maps (
                namespace TEXT NOT NULL,
                x INTEGER NOT NULL,
                y INTEGER NOT NULL,
                data BLOB,
                PRIMARY KEY (namespace, x, y)
            );
            SQL,
        );

        $this->pdo->exec(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS bounds (
                namespace TEXT NOT NULL,
                xMin INTEGER NOT NULL,
                xMax INTEGER NOT NULL,
                yMin INTEGER NOT NULL,
                yMax INTEGER NOT NULL
            );
            SQL,
        );
    }
}
