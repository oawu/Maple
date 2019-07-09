/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2015 - 2019, Maple ApiDoc
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

const Display = require('./Display');
const Xterm   = require('./Xterm');

module.exports = function(title, closure) {
  Display.title(title);

  Display.line('檢查參數是否正確');

  const argvs = process.argv.slice(2);

  let bucket = null;
  let access = null;
  let secret = null;
  let domain = null;
  let folder = '';

  for(let i = 0; i < argvs.length; i++) {
    if (['-b', '--bucket'].indexOf(argvs[i].toLowerCase()) !== -1)
      if (typeof argvs[i + 1] !== 'undefined' && argvs[i + 1][0] != '-')
        bucket = argvs[i + 1];

    if (['-a', '--access'].indexOf(argvs[i].toLowerCase()) !== -1)
      if (typeof argvs[i + 1] !== 'undefined' && argvs[i + 1][0] != '-')
        access = argvs[i + 1];

    if (['-s', '--secret'].indexOf(argvs[i].toLowerCase()) !== -1)
      if (typeof argvs[i + 1] !== 'undefined' && argvs[i + 1][0] != '-')
        secret = argvs[i + 1];

    if (['-f', '--folder'].indexOf(argvs[i].toLowerCase()) !== -1)
      if (typeof argvs[i + 1] !== 'undefined' && argvs[i + 1][0] != '-')
        folder = argvs[i + 1];

    if (['-d', '--domain'].indexOf(argvs[i].toLowerCase()) !== -1)
      if (typeof argvs[i + 1] !== 'undefined' && argvs[i + 1][0] != '-')
        domain = argvs[i + 1];
  }

  if (bucket === null)
    Display.line(false, ['參數有誤，請檢查 ' + Xterm.color.gray('--bucket', true) + ' 參數的是否正確！']);
  
  if (access === null)
    Display.line(false, ['參數有誤，請檢查 ' + Xterm.color.gray('--access', true) + ' 參數的是否正確！']);
  
  if (secret === null)
    Display.line(false, ['參數有誤，請檢查 ' + Xterm.color.gray('--secret', true) + ' 參數的是否正確！']);

  Display.line(true, '正確');

  Display.line('測試 S3 是否可以正常連線');

  const S3 = require('aws-sdk/clients/s3');

  const s3 = new S3({
    accessKeyId: access,
    secretAccessKey: secret
  });

  if (!s3)
    return Display.line(false, '初始 S3 物件失敗！');

  return s3.listBuckets(function(error, data) {
    if (error)
      return Display.line(false, [error.message]);
    
    if (data.Buckets.map(function(t) { return t.Name; }).indexOf(bucket) == -1)
      return Display.line(false, ['您這組 ' + Xterm.color.gray('access') + '、' + Xterm.color.gray('secret') + ' 無法操作 ' + Xterm.color.gray(bucket, true) + ' 此 Bucket！']);
  
    return Display.line(true) && closure(s3, { bucket: bucket, access: access, secret: secret, folder: folder, domain: domain });
  });
};