<?php

namespace Example\Worldmap;

use Arakne\MapParser\Loader\MapCoordinates;
use Arakne\MapParser\Loader\MapKey;
use Arakne\MapParser\Loader\MapStructure;
use Arakne\MapParser\Tile\Coordinate\Bounds;
use Arakne\Swf\SwfFile;
use PDO;
use PDOException;
use PDOStatement;

use function str_contains;
use function strtolower;

final class MapRepository
{
    private ?PDO $pdo = null;

    public function __construct(
        private readonly Config $config,
    ) {}

    public function findByMapByCoordinates(MapCoordinates $coordinates, int $superAreaId): ?MapStructure
    {
        $query = <<<'SQL'
            SELECT * FROM maps 
            WHERE MAP_X = ? AND MAP_Y = ?
            AND INDOOR = 0
            AND SUBAREA_ID IN (SELECT SUBAREA_ID FROM SUBAREA WHERE AREA_ID IN (SELECT AREA_ID FROM AREA WHERE SUPERAREA_ID = ?))
            SQL
        ;

        $stmt = $this->prepare($query);
        $stmt->bindValue(1, $coordinates->x, PDO::PARAM_INT);
        $stmt->bindValue(2, $coordinates->y, PDO::PARAM_INT);
        $stmt->bindValue(3, $superAreaId, PDO::PARAM_INT);
        $stmt->execute();

        $map = $stmt->fetch();

        if (!$map) {
            return null;
        }

        $mapFile = $this->config->mapsPath . '/' . $map['id'] . '_' . $map['date'] . ($map['key'] ? 'X' : '') . '.swf';

        if (!is_file($mapFile)) {
            return null;
        }

        // Directly return the map structure to avoid loading attachments again
        // And to ensure that the correct map file is used (considering the date and key)
        return MapStructure::fromSwfFile(new SwfFile($mapFile), new MapKey($map['key']), new MapCoordinates($map['MAP_X'], $map['MAP_Y'], $map['SUBAREA_ID']));
    }

    public function loadMapAttachments(MapStructure $map): array
    {
        // Attachments are already loaded
        if ($map->attachments) {
            return [];
        }

        $query = 'SELECT * FROM maps WHERE id = ?';

        $stmt = $this->prepare($query);
        $stmt->bindValue(1, $map->id, PDO::PARAM_INT);
        $stmt->execute();

        $map = $stmt->fetch();

        if (!$map) {
            return [];
        }

        return [new MapKey($map['key']), new MapCoordinates($map['MAP_X'], $map['MAP_Y'], $map['SUBAREA_ID'])];
    }

    /**
     * @param Bounds $bounds
     * @return array<int, MapStructure>
     */
    function findMapsInBounds(Bounds $bounds, int $superAreaId): array
    {
        $query = <<<'SQL'
            SELECT * FROM maps 
            WHERE MAP_X BETWEEN ? AND ?
            AND MAP_Y BETWEEN ? AND ?
            AND INDOOR = 0
            AND SUBAREA_ID IN (SELECT SUBAREA_ID FROM SUBAREA WHERE AREA_ID IN (SELECT AREA_ID FROM AREA WHERE SUPERAREA_ID = ?))
            SQL
        ;

        $stmt = $this->prepare($query);
        $stmt->bindValue(1, $bounds->xMin, PDO::PARAM_INT);
        $stmt->bindValue(2, $bounds->xMax, PDO::PARAM_INT);
        $stmt->bindValue(3, $bounds->yMin, PDO::PARAM_INT);
        $stmt->bindValue(4, $bounds->yMax, PDO::PARAM_INT);
        $stmt->bindValue(5, $superAreaId, PDO::PARAM_INT);
        $stmt->execute();

        $maps = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $loaded = [];

        foreach ($maps as $map) {
            $mapFile = $this->config->mapsPath . '/' . $map['id'] . '_' . $map['date'] . ($map['key'] ? 'X' : '') . '.swf';

            if (is_file($mapFile)) {
                $loaded[] = MapStructure::fromSwfFile(
                    new SwfFile($mapFile),
                    new MapCoordinates($map['MAP_X'], $map['MAP_Y']),
                    new MapKey($map['key']),
                );
            }
        }

        return $loaded;
    }

    /**
     * @param list<int> $mapIds
     * @return array<int, array<int, array{
     *     TRIGGER_ID: string|int,
     *     CELL_ID: string|int,
     *     ARGUMENTS: string
     * }>
     */
    public function loadTriggers(array $mapIds): array
    {
        $stmt = $this->prepare('SELECT * FROM MAP_TRIGGER WHERE MAP_ID = ?');
        $triggers = [];

        foreach ($mapIds as $mapId) {
            $stmt->execute([$mapId]);
            $triggers[$mapId] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $triggers;
    }

    private function prepare(string $query, bool $new = false): PDOStatement
    {
        if ($new) {
            $this->pdo = null;
        }

        $this->pdo ??= new PDO($this->config->dbDsn, $this->config->dbUser, $this->config->dbPassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        try {
            return $stmt = $this->pdo->prepare($query);
        } catch (PDOException $e) {
            if (!$new && str_contains(strtolower($e->getMessage()), 'has gone away')) {
                return $this->prepare($query, true);
            } else {
                throw $e;
            }
        }
    }
}
