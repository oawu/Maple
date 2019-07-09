/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2015 - 2019, Maple ApiDoc
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Xterm {
  constructor(str = '') {
    this.codes = [];
    this.str = str;
  }

  blod() {        this.codes.push('\x1b[1m'); return this; }
  dim() {         this.codes.push('\x1b[2m'); return this; }
  italic() {      this.codes.push('\x1b[3m'); return this; }
  underline() {   this.codes.push('\x1b[4m'); return this; }
  blink() {       this.codes.push('\x1b[5m'); return this; }
  inverted() {    this.codes.push('\x1b[7m'); return this; }
  hidden() {      this.codes.push('\x1b[8m'); return this; }
  
  color(code) {   this.addCode('\x1b[38;5;' + code + 'm'); return this; }
  bgColor(code) { this.addCode('\x1b[48;5;' + code + 'm'); return this; }
  
  addCode(code) { this.codes.push(code); return this; }

  toString() { let str = this.str; if (str === '') return str; for (var i = 0; i < this.codes.length; i++) str = this.codes[i] + str + "\x1b[0m"; return str; }

  static new(str) { return new Xterm(str); }

  static black(str, light) {    return Xterm.new(str).addCode(light ? '\x1b[38;5;8m'  : '\x1b[38;5;0m'); }
  static red(str, light) {      return Xterm.new(str).addCode(light ? '\x1b[38;5;9m'  : '\x1b[38;5;1m'); }
  static green(str, light) {    return Xterm.new(str).addCode(light ? '\x1b[38;5;10m' : '\x1b[38;5;2m'); }
  static yellow(str, light) {   return Xterm.new(str).addCode(light ? '\x1b[38;5;11m' : '\x1b[38;5;3m'); }
  static blue(str, light) {     return Xterm.new(str).addCode(light ? '\x1b[38;5;12m' : '\x1b[38;5;4m'); }
  static purple(str, light) {   return Xterm.new(str).addCode(light ? '\x1b[38;5;13m' : '\x1b[38;5;5m'); }
  static cyan(str, light) {     return Xterm.new(str).addCode(light ? '\x1b[38;5;14m' : '\x1b[38;5;6m'); }
  static gray(str, light) {     return Xterm.new(str).addCode(light ? '\x1b[38;5;15m' : '\x1b[38;5;7m'); }

  static bgBlack(str, light) {  return Xterm.new(str).addCode(light ? '\x1b[48;5;8m'  : '\x1b[48;5;0m'); }
  static bgRed(str, light) {    return Xterm.new(str).addCode(light ? '\x1b[48;5;9m'  : '\x1b[48;5;1m'); }
  static bgGreen(str, light) {  return Xterm.new(str).addCode(light ? '\x1b[48;5;10m' : '\x1b[48;5;2m'); }
  static bgYellow(str, light) { return Xterm.new(str).addCode(light ? '\x1b[48;5;11m' : '\x1b[48;5;3m'); }
  static bgBlue(str, light) {   return Xterm.new(str).addCode(light ? '\x1b[48;5;12m' : '\x1b[48;5;4m'); }
  static bgPurple(str, light) { return Xterm.new(str).addCode(light ? '\x1b[48;5;13m' : '\x1b[48;5;5m'); }
  static bgCyan(str, light) {   return Xterm.new(str).addCode(light ? '\x1b[48;5;14m' : '\x1b[48;5;6m'); }
  static bgGray(str, light) {   return Xterm.new(str).addCode(light ? '\x1b[48;5;15m' : '\x1b[48;5;7m'); }
}

Xterm.color = {
  black:  function(str, light) { return Xterm.black(str, light); },
  red:    function(str, light) { return Xterm.red(str, light); },
  green:  function(str, light) { return Xterm.green(str, light); },
  yellow: function(str, light) { return Xterm.yellow(str, light); },
  blue:   function(str, light) { return Xterm.blue(str, light); },
  purple: function(str, light) { return Xterm.purple(str, light); },
  cyan:   function(str, light) { return Xterm.cyan(str, light); },
  gray:   function(str, light) { return Xterm.gray(str, light); },
};

Xterm.bg = {
  black:  function(str, light) { return Xterm.bgBlack(str, light); },
  red:    function(str, light) { return Xterm.bgRed(str, light); },
  green:  function(str, light) { return Xterm.bgGreen(str, light); },
  yellow: function(str, light) { return Xterm.bgYellow(str, light); },
  blue:   function(str, light) { return Xterm.bgBlue(str, light); },
  purple: function(str, light) { return Xterm.bgPurple(str, light); },
  cyan:   function(str, light) { return Xterm.bgCyan(str, light); },
  gray:   function(str, light) { return Xterm.bgGray(str, light); },
};

module.exports = Xterm;