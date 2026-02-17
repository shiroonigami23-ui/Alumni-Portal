<!DOCTYPE html>
<html>
<head>
    <title>Live Stream - Alumni Portal</title>
    <link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />
</head>
<body>
    <div style="max-width: 800px; margin: 20px auto;">
        <h1>Live: Alumni Tech Talk</h1>
        <video id="my-video" class="video-js vjs-big-play-centered" controls preload="auto" width="800" height="450" data-setup="{}">
            <source src="http://localhost/live/live_38_test_key/index.m3u8" type="application/x-mpegURL">
        </video>
    </div>
    <script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>
</body>
</html>