var Drawing = {

    MODE_DRAWING: 'drawing',
    MODE_IDLE: 'idle',
    MODE_VIEW: 'view',

    UPDATE_TYPE_PENCIL: 'pencil',

    $canvas: null,
    canvas: null,
    sprite: null,

    canvasWidth: 0,
    canvasHeight: 0,

    mode: null,

    pointsCache: [],
    imageCache: [],

    newPointMinDist: 4,

    init: function ($canvas) {
        Drawing.mode = Drawing.MODE_IDLE;
        Drawing.canvas = $canvas[0];
        Drawing.$canvas = $canvas;
        Drawing.sprite = Drawing.canvas.getContext("2d");
        Drawing.sprite.fillStyle = 'black';
        Drawing.sprite.strokeStyle = 'black';
        Drawing.sprite.lineWidth = 5;
        Drawing.sprite.lineCap = 'round';
        Drawing.sprite.lineJoin = 'round';
        Drawing.sprite.imageSmoothingEnabled = true;
        Drawing.canvas.imageSmoothingEnabled = true;
        Drawing.canvasWidth = Drawing.canvas.width;
        Drawing.canvasHeight = Drawing.canvas.height;
        // Callbacks
        document.onmouseup = Drawing.onMouseUp;
        Drawing.canvas.onmouseout = Drawing.onMouseOut;
        Drawing.canvas.onmouseover = Drawing.onMouseOver;
        Drawing.canvas.onmousedown = Drawing.onMouseDown;
        Drawing.canvas.onmousemove = Drawing.onMouseMove;
    },

    /** CallBacks *****************************************************************************************************/

    onMouseUp: function (e) {
        switch (Drawing.mode) {
            case Drawing.MODE_DRAWING:
                Drawing.mode = Drawing.MODE_IDLE;
                if (e.target == Drawing.canvas && Drawing.pointsCache.length == 1) {
                    Drawing.drawPoint(Drawing.lastPointInCache());
                }
                Drawing.flushPointsCache();
        }
    },

    onMouseOut: function (e) {
        switch (Drawing.mode) {
            case Drawing.MODE_DRAWING:
                Drawing.endLine(e);
                Drawing.flushPointsCache();
        }
    },

    onMouseOver: function (e) {
        switch (Drawing.mode) {
            case Drawing.MODE_DRAWING:
                Drawing.startLine(e);
        }
    },

    onMouseDown: function (e) {
        switch (Drawing.mode) {
            case Drawing.MODE_IDLE:
                Drawing.mode = Drawing.MODE_DRAWING;
                Drawing.startLine(e);
        }
    },

    onMouseMove: function (e) {
        switch (Drawing.mode) {
            case Drawing.MODE_DRAWING:
                Drawing.endLine(e);
        }
    },

    /** Drawing methods ***********************************************************************************************/

    flushPointsCache: function () {
        Drawing.imageCache.push(Drawing.pointsCache);
        console.log(JSON.stringify(Drawing.imageCache));
        Drawing.$canvas.trigger('update', [Drawing, {
            type: Drawing.UPDATE_TYPE_PENCIL,
            settings: {},
            data: Drawing.pointsCache
        }]);
        Drawing.pointsCache = [];
    },

    startLine: function (e) {
        var point = Drawing.pointFromEvent(e);
        Drawing.pointsCache.push(point);
        Drawing.startLineAtPoint(point);
    },

    startLineAtPoint: function (point) {
        Drawing.sprite.beginPath();
        Drawing.sprite.moveTo(point.x, point.y);
    },

    endLine: function (e) {
        var point = Drawing.pointFromEvent(e);
        if (!Drawing.useNewPoint(point)) return;
        Drawing.pointsCache.push(point);
        Drawing.endLineAtPoint(point);
    },

    endLineAtPoint: function (point) {
        Drawing.sprite.lineTo(point.x, point.y);
        Drawing.sprite.stroke();
        Drawing.sprite.beginPath();
        Drawing.sprite.moveTo(point.x, point.y);
    },

    drawPoint: function (point) {
        //Drawing.sprite.clearRect(0, 0, Drawing.canvasWidth, Drawing.canvasHeight);
        Drawing.sprite.lineTo(point.x+0.1, point.y+0.1);
        Drawing.sprite.stroke();
    },

    useNewPoint: function (newPoint) {
        var prevPoint = Drawing.lastPointInCache();
        return (Math.abs(prevPoint.x - newPoint.x) > Drawing.newPointMinDist || Math.abs(prevPoint.y - newPoint.y) > Drawing.newPointMinDist);
    },

    lastPointInCache: function () {
        return Drawing.pointsCache[Drawing.pointsCache.length-1];
    },

    pointFromEvent: function (e) {
        return new Point(e.layerX, e.layerY);
    },

    clear: function () {
        Drawing.sprite.fillStyle = "white";
        Drawing.sprite.rect(0, 0, Drawing.canvasWidth, Drawing.canvasHeight);
        Drawing.sprite.fill();
        Drawing.pointsCache = [];
        Drawing.imageCache = [];
    },

    draw: function(imageCache) {
        Drawing.imageCache = imageCache;
        for (var i in imageCache) {
            var startPoint = imageCache[i].shift();
            Drawing.startLineAtPoint(startPoint);
            if (imageCache[i].length) {
                for (var p in imageCache[i]) {
                    Drawing.endLineAtPoint(imageCache[i][p]);
                }
            } else {
                Drawing.drawPoint(startPoint);
            }
        }
    }

};

function Point(x, y) {
    this.x = x;
    this.y = y;
}