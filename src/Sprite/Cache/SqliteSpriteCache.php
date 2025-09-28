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

namespace Arakne\MapParser\Sprite\Cache;

use Arakne\MapParser\Sprite\Sprite;
use Arakne\MapParser\Sprite\SpriteState;
use Arakne\MapParser\Util\ClockCache;
use Closure;
use Override;
use PDO;

use function dirname;
use function is_dir;
use function mkdir;

/**
 * A sprite cache implementation using a SQLite database for storage.
 */
final readonly class SqliteSpriteCache implements SpriteCacheInterface
{
    private PDO $pdo;

    /**
     * @var ClockCache<int, Sprite>
     */
    private ClockCache $cache;

    /**
     * @param positive-int $inMemoryCapacity The maximum number of sprites to keep in memory, to limit database access.
     */
    public function __construct(
        /**
         * The path to the SQLite database file
         */
        private string $path,

        /**
         * The namespace to use for this cache instance
         */
        private string $namespace = '',

        /**
         * The maximum number of sprites to keep in memory, to limit database access.
         *
         * @param positive-int $inMemoryCapacity
         */
        int $inMemoryCapacity = 100,
    ) {
        $this->cache = new ClockCache($inMemoryCapacity);

        if (!is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0755, true);
        }

        $this->pdo = new PDO('sqlite:' . $path);
        $this->pdo->exec('PRAGMA journal_mode = WAL;');

        $this->initSchema();
    }

    #[Override]
    public function exports(Closure $compute): array
    {
        $stmt = $this->pdo->prepare('SELECT id, file FROM exports WHERE namespace = ?');
        $stmt->bindValue(1, $this->namespace);
        $stmt->execute();

        $exports = [];

        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $exports[(int) $row['id']] = $row['file'];
        }

        if ($exports === []) {
            $exports = $compute();

            $stmt = $this->pdo->prepare('INSERT OR IGNORE INTO exports (namespace, id, file) VALUES (?, ?, ?)');
            foreach ($exports as $id => $file) {
                $stmt->bindValue(1, $this->namespace);
                $stmt->bindValue(2, $id, PDO::PARAM_INT);
                $stmt->bindValue(3, $file);
                $stmt->execute();
            }
        }

        return $exports;
    }

    #[Override]
    public function sprite(int $id, Closure $compute): Sprite
    {
        $cached = $this->cache[$id];

        if ($cached !== null) {
            return $cached;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM sprites WHERE namespace = ? AND id = ?');
        $stmt->bindValue(1, $this->namespace);
        $stmt->bindValue(2, $id, PDO::PARAM_INT);
        $stmt->execute();

        if (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            return new Sprite(
                (int) $row['id'],
                (string) $row['png'],
                (float) $row['width'],
                (float) $row['height'],
                (float) $row['offsetX'],
                (float) $row['offsetY'],
                SpriteState::{$row['state']},
            );
        }

        $sprite = $compute($id);
        $this->cache->offsetSet($id, $sprite);

        $stmt = $this->pdo->prepare('INSERT OR IGNORE INTO sprites (namespace, id, png, width, height, offsetX, offsetY, state) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bindValue(1, $this->namespace);
        $stmt->bindValue(2, $sprite->id, PDO::PARAM_INT);
        $stmt->bindValue(3, $sprite->pngData, PDO::PARAM_LOB);
        $stmt->bindValue(4, $sprite->width);
        $stmt->bindValue(5, $sprite->height);
        $stmt->bindValue(6, $sprite->offsetX);
        $stmt->bindValue(7, $sprite->offsetY);
        $stmt->bindValue(8, $sprite->state->name);
        $stmt->execute();

        return $sprite;
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
            CREATE TABLE IF NOT EXISTS sprites (
                namespace TEXT NOT NULL,
                id INTEGER NOT NULL,
                png BLOB NOT NULL,
                width REAL NOT NULL,
                height REAL NOT NULL,
                offsetX REAL NOT NULL,
                offsetY REAL NOT NULL,
                state TEXT NOT NULL,
                PRIMARY KEY (namespace, id)
            );
            SQL,
        );

        $this->pdo->exec(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS exports (
                namespace TEXT NOT NULL,
                id INTEGER NOT NULL,
                file TEXT NOT NULL,
                PRIMARY KEY (namespace, id)
            );
            SQL,
        );
    }
}
