/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2015 - 2019, Maple ApiDoc
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

const Maple    = require('./Maple');
const Display  = require('./Display');
const Xterm    = require('./Xterm');
const Path     = require('path');

const later = 250;
const readyLater = 2000;
let   ready = false;
let   timer = null;

function BuildApiDoc(title, file, closure) {
  const option = {
    path: Path.ctrl,
    output: Path.apiDoc + 'Output' + Path.sep,
    template: Path.apiDoc + 'Template' + Path.sep,
    formats: ['.php']
  };

  let cmd = 'npx apidoc';
  if (typeof option.path !== undefined) cmd += ' --input ' + option.path;
  if (typeof option.output !== undefined) cmd += ' --output ' + option.output;
  if (typeof option.template !== undefined) cmd += ' --template ' + option.template;
  if (typeof option.formats !== undefined) cmd += option.formats.map(function(format) { return ' --file-filters ".*\\' + format + '$"'; }).join('');
  
  if (ready) Display.line(Xterm.color.gray(title, true) + ' API 文件', Xterm.color.gray('變更檔案', true).dim() + Display.markSemicolon() + Xterm.color.gray(file.replace(Path.root, ''), true).dim().italic(), Xterm.color.gray('執行指令', true).dim() + Display.markSemicolon() + Xterm.color.gray('npx apidoc', true).dim().italic());
  else Display.line(Xterm.color.gray('首次', true) + '編譯 API 文件', Xterm.color.gray('執行指令', true).dim() + Display.markSemicolon() + Xterm.color.gray('npx apidoc', true).dim().italic());

  try {
    require('child_process').execSync(cmd, {stdio: 'pipe'}).toString();
    Display.line(true);
    closure();
  } catch(err) {
    Display.line(false);
  }

  ready || Display.title('開始 Watch 以及編譯');
  setTimeout(function() { ready = true; }, readyLater);
  clearTimeout(timer);
  timer = null;
}

module.exports = function(title, closure) {
  const Chokidar = require('chokidar');

  Display.title(title);
  Display.line('鎖定 Controller', Xterm.color.gray('執行動作', true).dim() + Display.markSemicolon() + Xterm.color.gray('watch ' + Path.sep + Path.phps.replace(Path.root, ''), true).dim().italic());

  Chokidar.watch(Path.phps.replace(/\\/g, '/'))
          .on('change', function(file) { if(timer !== null) return; timer = setTimeout(BuildApiDoc.bind(null, '編譯', file, closure), later); })
          .on('add',    function(file) { if(timer !== null) return; timer = setTimeout(BuildApiDoc.bind(null, '新增', file, closure), later); })
          .on('unlink', function(file) { if(timer !== null) return; timer = setTimeout(BuildApiDoc.bind(null, '刪除', file, closure), later); })
          .on('error',  function(error) { return Maple.notifier('[監控 PHP 檔案] 警告！', '監控 PHP 檔案發生錯誤', '請至終端機確認錯誤原因！') && Display.line(false, ['發生錯誤，請至終端機確認錯誤原因！']); })
          .on('ready', function() { return Display.line(true) && BuildApiDoc(); });
};