<?php

use Arakne\MapParser\WorldMap\WorldMapTileRenderer;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dofus Map</title>
    <meta charset="utf-8"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css"
          integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ=="
          crossorigin=""/>
</head>

<body style="margin: 0; padding: 0;">

<header>
    <ul style="padding: 0; margin: 0; list-style: none; display: flex; gap: 10px; background-color: #333; color: white; height: 30px; align-items: center;">
        <li><a href="/incarnam">Incarnam</a></li>
        <li><a href="/amakna">Amakna</a></li>
    </ul>
</header>

<style lang="css">
    .tp-marker {
        background-color: rgba(255, 255, 255, 0.1);
        width: 20px;
        height: 20px;
        box-sizing: border-box;
        border-radius: 50%;
        border: 1px solid black;
        overflow: hidden;
    }

    .tp-marker a {
        display: block;
        width: 100%;
        height: 100%;
    }

    .tp-marker:hover {
        background-color: rgba(255, 255, 255, 0.5);
    }
</style>

<div id="mapid"
     style="height: calc(100vh - 30px); width: 100vw; background-color: <?= WorldMapTileRenderer::BACKGROUND_COLOR ?>;"></div>
<script src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js"
        integrity="sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og=="
        crossorigin=""></script>
<script lang="js">
    var mymap = L.map('mapid', {
        zoomSnap: 1,
        zoomDelta: 1,
        wheelPxPerZoomLevel: 120,
    }).setView([70, -40], 4);

    L.tileLayer('/tiles/<?= $this->name ?>?x={x}&y={y}&z={z}', {
        maxZoom: <?= $this->tileRenderer->maxZoom + 1 ?>,
    }).addTo(mymap);

    const tpLayer = L.layerGroup().addTo(mymap);

    function getBboxString() {
        const b = mymap.getBounds();
        return [b.getWest(), b.getSouth(), b.getEast(), b.getNorth()].join(',');
    }

    async function fetchTeleports() {
        const z = mymap.getZoom();
        const minZoomToShow = 7; // ne rien demander en dessous si inutile
        if (z < minZoomToShow) {
            tpLayer.clearLayers();
            return;
        }

        const bbox = getBboxString();
        try {
            const res = await fetch(`/markers/<?= $this->name ?>?bbox=${bbox}&zoom=${z}`);
            if (!res.ok) throw new Error('API error');
            const geojson = await res.json();
            renderGeoJSON(geojson);
        } catch (err) {
            console.error('fetch teleports failed', err);
        }
    }

    function renderGeoJSON(geojson) {
        tpLayer.clearLayers();
        L.geoJSON(geojson, {
            pointToLayer: (feat, latlng) => {
                // n'affiche que si zoom dans les propriétés
                const p = feat.properties || {};
                const z = mymap.getZoom();
                const html = `<div class="tp-marker"><a
                        href="/showmap?id=${p.targetMapId}"
                        class="tp-go"
                        title="${p.label}"
                        data-id="${p.id}"
                        data-target="${p.targetMapId}"></a>
                </div>`;
                return L.marker(latlng, {
                    icon: L.divIcon({
                        html,
                        className: '',
                        iconSize: [20, 20],
                        iconAnchor: [10, 10]
                    })
                });
            }
        }).addTo(tpLayer);
    }

    // debounce helper
    function debounce(fn, wait) {
        let t = null;
        return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), wait);
        };
    }

    const debouncedFetch = debounce(fetchTeleports, 250);
    mymap.on('moveend zoomend', debouncedFetch);

    // initial
    fetchTeleports();
</script>
</body>
</html>
