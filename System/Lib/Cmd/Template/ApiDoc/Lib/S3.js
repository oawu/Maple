/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2015 - 2019, Maple ApiDoc
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */
const Path = require('path');
const FileSystem = require('fs');

const Display = require('./Display');
const exts = { jpg: ['image/jpeg', 'image/pjpeg'], gif: ['image/gif'], png: ['image/png', 'image/x-png'], pdf: ['application/pdf', 'application/x-download'], gz: ['application/x-gzip'], zip: ['application/x-zip', 'application/zip', 'application/x-zip-compressed'], swf: ['application/x-shockwave-flash'], tar: ['application/x-tar'], bz: ['application/x-bzip'], bz2: ['application/x-bzip2'], txt: ['text/plain'], html: ['text/html'], htm: ['text/html'], ico: ['image/x-icon'], css: ['text/css'], js: ['application/x-javascript'], xml: ['text/xml'], ogg: ['application/ogg'], wav: ['audio/x-wav', 'audio/wave', 'audio/wav'], avi: ['video/x-msvideo'], mpg: ['video/mpeg'], mov: ['video/quicktime'], mp3: ['audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'], mpeg: ['video/mpeg'], flv: ['video/x-flv'], php: ['application/x-httpd-php'], bin: ['application/macbinary'], psd: ['application/x-photoshop'], ai: ['application/postscript'], ppt: ['application/powerpoint', 'application/vnd.ms-powerpoint'], wbxml: ['application/wbxml'], tgz: ['application/x-tar', 'application/x-gzip-compressed'], jpeg: ['image/jpeg', 'image/pjpeg'], jpe: ['image/jpeg', 'image/pjpeg'], bmp: ['image/bmp', 'image/x-windows-bmp'], shtml: ['text/html'], text: ['text/plain'], doc: ['application/msword'], docx: ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'], xlsx: ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'], word: ['application/msword', 'application/octet-stream'], json: ['application/json', 'text/json'], svg: ['image/svg+xml'], mp2: ['audio/mpeg'], exe: ['application/octet-stream', 'application/x-msdownload'], tif: ['image/tiff'], tiff: ['image/tiff'], asc: ['text/plain'], xsl: ['text/xml'], hqx: ['application/mac-binhex40'], cpt: ['application/mac-compactpro'], csv: ['text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'], dms: ['application/octet-stream'], lha: ['application/octet-stream'], lzh: ['application/octet-stream'], class: ['application/octet-stream'], so: ['application/octet-stream'], sea: ['application/octet-stream'], dll: ['application/octet-stream'], oda: ['application/oda'], eps: ['application/postscript'], ps: ['application/postscript'], smi: ['application/smil'], smil: ['application/smil'], mif: ['application/vnd.mif'], xls: ['application/excel', 'application/vnd.ms-excel', 'application/msexcel'], wmlc: ['application/wmlc'], dcr: ['application/x-director'], dir: ['application/x-director'], dxr: ['application/x-director'], dvi: ['application/x-dvi'], gtar: ['application/x-gtar'], php4: ['application/x-httpd-php'], php3: ['application/x-httpd-php'], phtml: ['application/x-httpd-php'], phps: ['application/x-httpd-php-source'], sit: ['application/x-stuffit'], xhtml: ['application/xhtml+xml'], xht: ['application/xhtml+xml'], mid: ['audio/midi'], midi: ['audio/midi'], mpga: ['audio/mpeg'], aif: ['audio/x-aiff'], aiff: ['audio/x-aiff'], aifc: ['audio/x-aiff'], ram: ['audio/x-pn-realaudio'], rm: ['audio/x-pn-realaudio'], rpm: ['audio/x-pn-realaudio-plugin'], ra: ['audio/x-realaudio'], rv: ['video/vnd.rn-realvideo'], log: ['text/plain', 'text/x-log'], rtx: ['text/richtext'], rtf: ['text/rtf'], mpe: ['video/mpeg'], qt: ['video/quicktime'], movie: ['video/x-sgi-movie'], xl: ['application/excel'], eml: ['message/rfc822']};

const mapDir = function(dir, options, filelist) {
  const files = FileSystem.readdirSync(dir);

  filelist = filelist || [];

  files.forEach(function(file) {
    const path = dir + file;

    if (!FileSystem.statSync(path).isDirectory())
      if ((options.hidden || file[0] !== '.') && (!options.formats.length  || options.formats.indexOf('.' + file.split('.').pop().toLowerCase()) !== -1))
        if ((stats = FileSystem.statSync(path)) && (stats.size > 0))
          return filelist.push(path);

    if (FileSystem.statSync(path).isDirectory() && options.recursive)
      filelist = mapDir(path + Path.sep, options, filelist);
  });

  return filelist;
};

const localFiles = function(title, options, closure) {
  const md5File = require('md5-file');

  Display.line(title);

  const dirs = [{
    path: Path.apiDocOutput,
    formats: ['.html', '.css', '.js', '.json'],
    recursive: false,
    hidden: false
  }];

  let localFiles = dirs.map(function(dir) { return mapDir(dir.path, dir); }).reduce(function(a, b) { return a.concat(b); });

  Display.line(localFiles.length);

  localFiles = localFiles.map(function(dir) {
    return Display.line() && {
      name: (options.folder.length ? options.folder + '/' : '') + dir.replace(Path.apiDocOutput, '').replace(/\\/g, '/'),
      hash: md5File.sync(dir),
      path: dir,
    };
  });

  Display.line(true);

  return closure(localFiles);
};

var listObjects = function(s3, closure, options, items) {
  s3.listObjectsV2(options, function(error, data) {
    if (error)
      return Display.line(false, [error.message]);
    
    items = items.concat(data.Contents.map(function(t) {
      return {
        name: t.Key,
        hash: t.ETag.replace(/^('|")(.*)\1/g, '$2'),
      };
    }));

    if (!data.IsTruncated)
      return Display.line(items.length) && Display.line(true) && closure(s3, items);

    options.ContinuationToken = data.NextContinuationToken;
    return listObjects(s3, closure, options, items);
  });
};

const s3Files = function(title, s3, options, closure) {
  const S3 = require('aws-sdk/clients/s3');

  Display.line(title);

  return listObjects(s3, closure, {
    Bucket: options.bucket,
    Prefix: options.folder,
  }, []);
};

const filterLocalFiles = function(title, localFiles, s3Files, closure) {
  Display.line(title);
  Display.line(localFiles.length);

  const uploadFiles = localFiles.filter(function(localFile) {
    Display.line();
  
    for (let i = 0; i < s3Files.length; i++)
      if (s3Files[i].name == localFile.name && s3Files[i].hash == localFile.hash)
        return false;
  
    return true;
  });

  return Display.line(true) && closure(uploadFiles);
};

const extensions = function(name) {
  return typeof exts[name.split('.').pop().toLowerCase()] === 'undefined' ? 'text/plain' : exts[name.split('.').pop().toLowerCase()][0];
};

const uploadFiles = function(title, s3, options, uploads, closure) {
  Display.line(title);
  Display.line(uploads.length);
  
  Promise.all(uploads.map(function(file) {
    return new Promise(function(resolve, reject) {
      s3.putObject({
        Bucket: options.bucket,
        Key: file.name,
        Body: FileSystem.readFileSync(file.path),
        ACL: 'public-read',
        ContentType: extensions(file.path),
        // ContentMD5: Buffer.from(file.hash).toString('base64'),
        // CacheControl: 'max-age=5'
      }, function(error, data) { if (error) reject(error); else Display.line() && resolve(data); });
    });
  })).then(function() {
    return Display.line(true) && closure();
  }).catch(function(error) {
    return Display.line(false, [error.message]);
  });
};

const filterS3Files = function(title, localFiles, s3Files, closure) {
  Display.line(title);
  Display.line(s3Files.length);

  const deleteFiles = s3Files.filter(function(s3File) {
    Display.line();
  
    for (let i = 0; i < localFiles.length; i++)
      if (localFiles[i].name == s3File.name)
        return false;

    return true;
  });

  return Display.line(true) && closure(deleteFiles);
};

const deleteFiles = function(title, s3, options, deletes, closure) {
  Display.line(title);
  Display.line(deletes.length);

  Promise.all(deletes.map(function(file) {
    return new Promise(function(resolve, reject) {
      s3.deleteObject({
        Bucket: options.bucket,
        Key: file.name,
      }, function(error, data) { if (error) reject(error); else Display.line() && resolve(data); });
    });
  })).then(function() {
    return Display.line(true) && closure();
  }).catch(function(error) {
    return Display.line(false, [error.message]);
  });
};

module.exports = function(title, s3, options, closure) {
  Display.title(title);

  localFiles('整理本機內檔案', options, function(localFiles) {
    s3Files('取得 S3 上檔案', s3, options, function(s3, s3Files) {
      filterLocalFiles('過濾上傳的檔案', localFiles, s3Files, function(uploads) {
        uploadFiles('上傳檔案至 S3 ', s3, options, uploads, function() {
          filterS3Files('過濾刪除的檔案', localFiles, s3Files, function(deletes) {
            deleteFiles('刪除 S3 的檔案', s3, options, deletes, closure);
          });
        });
      });
    });
  });
};