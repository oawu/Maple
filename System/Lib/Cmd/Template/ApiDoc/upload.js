/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2015 - 2019, Maple ApiDoc
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

const Path  = require('path');
Path.root   = Path.resolve(__dirname, '..' + Path.sep + '..' + Path.sep) + Path.sep;
Path.apiDoc = __dirname + Path.sep;
Path.apiDocOutput = Path.apiDoc + 'Output' + Path.sep;

const Maple    = require('./Lib/Maple');
const Display  = require('./Lib/Display');
const Xterm    = require('./Lib/Xterm');
const Argv     = require('./Lib/Argv');
const EnvCheck = require('./Lib/EnvCheck');
const S3       = require('./Lib/S3');

process.stdout.write('\x1b[2J');
process.stdout.write('\x1b[0f');

Display.mainTitle('API 文件上傳器');

EnvCheck('檢查環境', function() {
  Argv('檢查參數', function(s3, options) {
    S3('開始上傳', s3, options, function() {
      Display.title('完成')
      Display.markListLine('上傳 API 文件上傳成功！');
      if (options.domain && options.domain !== '')
        Display.markListLine('API 文件的線上位置' + Display.markSemicolon() + Xterm.color.blue('http://' + options.domain + '/' + options.folder, true).underline());
      Maple.print(Display.LN);
    });
  });
});