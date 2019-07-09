/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2015 - 2019, Maple ApiDoc
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

const Display = require('./Display');
const Xterm   = require('./Xterm');

function testRequire() {
  try { Object.keys(JSON.parse(require('fs').readFileSync(require('path').apiDoc + 'package.json', 'utf8')).devDependencies).map(require); return true; }
  catch(e) { return false; }
  return true;
}

function installAll() {
  try { let output = require('child_process').execSync('npm install .', {stdio: 'pipe'}).toString(); return true; }
  catch(err) { return false; }
  return true;
}

function sudoInstallAll() {
  try { let output = require('child_process').execSync('sudo npm install .', {stdio: 'pipe'}).toString(); return true; }
  catch(err) { return false; }
  return true;
}

module.exports = function(title, closure) {
  Display.title(title);

  Display.line('檢查是否已經初始', Xterm.color.gray('檢查動作', true).dim() + Display.markSemicolon() + Xterm.color.gray('try package.json devDependencies', true).dim().italic());
  if (testRequire()) return Display.line(true) && closure();
  Display.line(false);

  Display.line('自動初始化', Xterm.color.gray('執行指令', true).dim() + Display.markSemicolon() + Xterm.color.gray('npm install .', true).dim().italic());
  if (installAll()) return Display.line(true) && closure();
  Display.line(false);

  Display.line('改用最高權限初始', Xterm.color.gray('執行指令', true).dim() + Display.markSemicolon() + Xterm.color.gray('sudo npm install .', true).dim().italic());
  if (sudoInstallAll()) return Display.line(true) && closure();
  Display.line(false, ['請在終端機手動輸入指令 ' + Xterm.color.gray('npm install .', true).blod() + ' 吧！']);
};