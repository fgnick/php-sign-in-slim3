if (!Array.isArray) {
    Array.isArray = function(arg) {
      return Object.prototype.toString.call(arg) === '[object Array]';
    };
}

var App = new ( function () {
    // for CVS report export
    this.exportCSV = function ()
    {
        this.export = function ( fileName, data ) {
            fileName = fileName + ".csv";
            var blob = new Blob( [ data ], {
                type : "application/octet-stream"
            });
            var href = URL.createObjectURL( blob );
            var link = document.createElement( "a" );
            document.body.appendChild( link );
            link.href = href;
            link.download = fileName;
            link.click();
        }
    };

    this.getParameterByName = function(name, url) 
    {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    this.f_trim = function (x) 
    {
        return x.replace(/^\s+|\s+$/gm,'');
    }

    this.getNowUTCDate = function()
    {
        var now = new Date();
        return new Date( now.getTime() + ( now.getTimezoneOffset() * 60000 ) );
    }

    this.dateToUNIXFormat = function (date, sep)
    {
        if( sep !== '/' && sep !== '-' ) { sep = '/'; }
        return moment(date).format("YYYY"+sep+"MM"+sep+"DD HH:mm:ss");
    }

    this.getRandomColor = function () {
        var letters = '0123456789ABCDEF';
        var color = '#';
        for (var i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }

    this.response_decoder = undefined;

    this.init = async function () {
        try {
            const response = await fetch('/asset/global/response-decoder.json');
            const data = await response.json();
            response_decoder = data;
            //console.log('response_decoder loaded:', response_decoder);
        } catch (error) {
            console.error('response decode json loading error:', error);
            response_decoder = {};
        }
    }



} );