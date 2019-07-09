/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2015 - 2019, Maple ApiDoc
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

const Maple   = require('./Maple');
const Display = require('./Display');
const Xterm   = require('./Xterm');

function openServer(port, closure) {
  const Http       = require('http');
  const FileSystem = require('fs');
  const Mime       = require('mime');
  const Path       = require('path');

  let server = Http.createServer(function(request, response) {
    let path = require('url').parse(request.url).pathname;

    if (path === '/') path = '/index.html';
    
    path = Path.apiDoc + 'Output' + path;

    if (!FileSystem.existsSync(path)) {
      response.writeHead(404, {'Content-Type': 'text/html; charset=UTF-8'});
      response.write('404 Not Found');
      response.end();
      return;
    }

    FileSystem.readFile(path, 'utf8', function(error, data) {
      if (error) {
        response.writeHead(404, {'Content-Type': 'text/html; charset=UTF-8'});
        response.write('404 Not Found');
      } else {
        response.writeHead(200, {'Content-Type': Mime.getType(path) + '; charset=UTF-8'});
        
        if (Mime.getExtension(Mime.getType(path)) != 'html')
          response.write(data);
        else {
          const $ = require('cheerio').load(data);
          $('head').append($('<script />').attr('src', '/socket.io/socket.io.js')).append($('<script />').attr('type', 'text/javascript').html('var socket = io.connect();socket.on("action", function(data) { if (data === "reload") location.reload(true); });'));
          response.write($.html());
        }
      }
      response.end();
    });

  }).listen(port).on('error', function(e) {
    return Display.line(false, ['請檢查是否有開啟其他的 API 文件產生器！', e.message]);
  });

  const socketIO = require('socket.io').listen(server);

  Display.line(true, '完成');
  Maple.print(' '.repeat(3) + Display.markList() + ' ' + '網址' + Display.markSemicolon() + Xterm.color.blue('http://127.0.0.1:' + port + '/', true).italic().underline() + Display.LN);
  return closure(socketIO);
}

module.exports = function(title, closure) {
  Display.title(title);
  Display.line('開啟 Node Server', Xterm.color.gray('主要語法', true).dim() + Display.markSemicolon() + Xterm.color.gray('http.createServer', true).dim().italic());
  return openServer(8888, closure);
};