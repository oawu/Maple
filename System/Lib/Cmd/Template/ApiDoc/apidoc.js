/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2015 - 2019, Maple ApiDoc
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

const Path  = require('path');
Path.root   = Path.resolve(__dirname, '..' + Path.sep + '..' + Path.sep) + Path.sep;
Path.apiDoc = __dirname + Path.sep;
Path.ctrl   = Path.root + 'App' + Path.sep + 'Controller' + Path.sep;
Path.phps   = Path.ctrl + '**' + Path.sep + '*.php';

const Display     = require('./Lib/Display');
const EnvCheck    = require('./Lib/EnvCheck');
const OpenServer  = require('./Lib/OpenServer');
const WatchPHP    = require('./Lib/WatchPHP');

process.stdout.write('\x1b[2J');
process.stdout.write('\x1b[0f');

Display.mainTitle('API 文件產生器');

EnvCheck('檢查環境', function() {
  OpenServer('開啟伺服器', function(socketIO) {
    let sockets = [];

    socketIO.sockets.on('connection', function(socket) {
      sockets.push(socket);

      socket.on('disconnect', function() {
        const index = sockets.indexOf(socket);
        if (index !== -1)
          sockets.splice(sockets.indexOf(socket), 1);
      });
    });

    WatchPHP('Watch PHP 檔案', function() {
      sockets.forEach(function(t) {
        t.emit('action', 'reload');
      });
    });
  });
});