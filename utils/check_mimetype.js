var mime = require('mime-magic');

mime(process.argv[2], function(err, type){
    if (err){
        console.log("binary/octet-stream");
    }else{
        console.log(type);
    }
});