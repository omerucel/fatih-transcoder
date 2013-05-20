var Metalib = require('fluent-ffmpeg').Metadata;

var metaObject = new Metalib(process.argv[2]);
metaObject.get(function(metadata, err) {
    if (err == null)
    {
        console.log('%j', metadata);
    }
});