<!DOCTYPE html>
<html>
    <head>
        <title>Dofus Map</title>
        <meta charset="utf-8" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css"
              integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ=="
              crossorigin=""/>
    </head>

    <body>
    <div id="mapid" style="height: 432px; width: 742px;"></div>
    <script src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js"
            integrity="sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og=="
            crossorigin=""></script>
    <script lang="js">
        var mymap = L.map('mapid').setView([0, 0], 1);
        //
        // L.TileLayer.Custom = L.TileLayer.extend({
        //     getTileUrl: function(coords) {
        //         var groupData = this._map.options.data;
        //         var groupId   = groupData.id;
        //         var lastChanged = groupData.lastChanged;
        //         if (!groupData.hasWorldMap || coords.z > groupData.worldMap.maxZoom) {
        //             var tileExists = doesTileExist(coords, groupData.availableMaps);
        //         } else {
        //             var factor = Math.pow(2, groupData.worldMap.maxZoom - coords.z);
        //             var minX = Math.floor(groupData.worldMap.tileMinX / factor);
        //             var maxX = Math.floor(groupData.worldMap.tileMaxX / factor);
        //             var minY = Math.floor(groupData.worldMap.tileMinY / factor);
        //             var maxY = Math.floor(groupData.worldMap.tileMaxY / factor);
        //             var tileExists = (coords.x >= minX && coords.x <= maxX && coords.y >= minY && coords.y <= maxY) ? true : false ;
        //         }
        //         var url = (tileExists) ? dns+'tiles/' + groupId + '/' + coords.z + '/' + coords.x + '/' + coords.y + '.jpg?' + lastChanged : dns+'images/blankPixel.png' ;
        //         return url;
        //     }
        // });

        L.tileLayer('http://127.0.0.1/php-map-parser/amakna.php?x={x}&y={y}&z={z}', {
            maxZoom: 9, // 5 for incarnam
        }).addTo(mymap);
    </script>
    </body>
</html>
