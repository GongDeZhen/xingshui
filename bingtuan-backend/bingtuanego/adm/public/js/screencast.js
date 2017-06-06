var frames = [
    {
        animation: function () {
            $('#screencast img').css('top', 0).css('left', 0).hide();
            $('#cast-1').show();
        },

        dur: 1
    },

    {
        animation: function () {
            $('#cast-2').show();
        },

        dur: 2
    },

    {
        animation: function () {
            $('#cast-3').css('top', '568px').show();
            $('#cast-3').animate({
                top: "-=568",
                easing: 'swing',
                duration: 300
            }, function () {
                $('#cast-2').hide();
            });
        },

        dur: 4
    },
    
    {
        animation: function () {
            $('#cast-4').css('left', '320px').show();
            $('#cast-3').animate({
                left: "-=320",
                easing: 'swing',
                duration: 300,
            });
            $('#cast-4').animate({
                left: "-=320",
                easing: 'swing',
                duration: 300
            });
        },

        dur: 4
    },

    {
        animation: function () {
            $('#cast-4').hide();
            $('#cast-5').show();
        },

        dur: 1
    },

    {
        animation: function () {
            $('#cast-5').hide();
            $('#cast-6').show();
        },

        dur: 1
    },

    {
        animation: function () {
            $('#cast-6').hide();
            $('#cast-7').show();
        },

        dur: 1
    },

    {
        animation: function () {
            $('#cast-7').hide();
            $('#cast-8').show();
        },

        dur: 2
    },

    {
        animation: function () {
            $('#cast-9').css('left', '-320px').show();
            $('#cast-9').animate({
                left: "+=320",
                easing: 'swing',
                duration: 300,
            });
            $('#cast-8').animate({
                left: "+=320",
                easing: 'swing',
                duration: 300
            });
        },

        dur: 2
    },

    {
        animation: function () {
            $('#cast-8').hide();
            $('#cast-9').animate({
                top: "+=568",
                easing: 'swing',
                duration: 300
            }, function () {
                $('#cast-9').hide();
            });
        },

        dur: 2
    },  

];

var frameIdx = 0;
var castTimer;
function castShow()
{
    frameIdx = frameIdx >= frames.length ? 0 : frameIdx;
    console.log(frameIdx);
    var frame = frames[frameIdx];
    frame.animation();
    castTimer = setTimeout(function() {
        frameIdx ++;
        castShow();
    }, frame.dur * 1000);
}

function castStop()
{
    window.clearTimeout(castTimer);
    var frame = frames[0];
    frame.animation();

    frameIdx = 0;
}

