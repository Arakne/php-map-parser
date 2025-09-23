<!DOCTYPE html>
<html>
<head>
    <title>Dofus Map</title>
    <meta charset="utf-8" />
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

    #map {
        background-color: #333333;
        width: fit-content;
        position: relative;
    }

    #map img {
        margin: 0;
        padding: 0;
        display: block;
    }
</style>

<div id="map">
    <img
        src="/render?id=<?= $map->id ?>"
        width="<?= $width ?>"
        height="<?= $height ?>"
    >

    <?php foreach ($triggers as $trigger): ?>
        <div
            class="tp-marker"
            style="
                position: absolute;
                left: <?= $trigger['x'] - 10 ?>px;
                top: <?= $trigger['y'] - 10 ?>px;
            "
            title="Map <?= $trigger['target'] ?>"
        >
            <a href="/showmap?id=<?= $trigger['target'] ?>"></a>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
