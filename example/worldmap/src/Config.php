<?php

namespace Example\Worldmap;

final readonly class Config
{
    public function __construct(
        public string $dofusPath,
        public string $mapsPath,
        public string $cachePath,
        public string $dbDsn,
        public ?string $dbUser = null,
        public ?string $dbPassword = null,
    ) {}

    public static function fromEnv(): self
    {
        return new self(
            dofusPath: getenv('DOFUS_PATH'),
            mapsPath: getenv('MAPS_PATH'),
            cachePath: getenv('CACHE_PATH') ?: __DIR__ . '/../cache',
            dbDsn: getenv('DB_DSN'),
            dbUser: getenv('DB_USER') ?: null,
            dbPassword: getenv('DB_PASSWORD') ?: null,
        );
    }
}
