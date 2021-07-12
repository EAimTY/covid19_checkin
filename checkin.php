<?php

require "vendor/autoload.php";
use GuzzleHttp\Client;
use thiagoalessio\TesseractOCR\TesseractOCR;

// 学号与密码
$id = "1234567890";
$password = "mypassword";

try {

  // 检查 PHP 版本
  if (version_compare(PHP_VERSION, '7.2.5') <= 0) {
    throw new Exception("PHP version does not meet the requirements. Please use at least version 7.2.5");
  }

  // 检查 mbstring 扩展
  if (!extension_loaded('mbstring')) {
    if (!dl('mbstring.so')) {
      throw new Exception("PHP mbstring extension not found");
    }
  }

  // 建立 Guzzle Client
  $guzzle = new Client([
    "base_uri" => base64_decode("aHR0cDovL3lxdGIuZ3podS5lZHUuY24v"),
    "timeout"  => 96.0,
    "cookies" => true
  ]);

  // 获取登录页面
  $login = $guzzle->request("GET", base64_decode("aHR0cHM6Ly9jYXMuZ3podS5lZHUuY24vY2FzX3NlcnZlci9sb2dpbg"))->getBody()->getContents();

  // 截取登录参数
  $lt = mb_substr($login, mb_strpos($login, 'name="lt"') + 17);
  $lt = mb_substr($lt, 0, mb_strpos($lt, ".example.org") + 12);

  // 获取验证码并使用 Tesseract OCR 处理
  $captcha = $guzzle->request("GET", base64_decode("aHR0cHM6Ly9jYXMuZ3podS5lZHUuY24vY2FzX3NlcnZlci9jYXB0Y2hhLmpzcA"));
  file_put_contents(dirname(__FILE__) . "/captcha.jpg", $captcha->getBody());
  $captcha = (new TesseractOCR(dirname(__FILE__) . "/captcha.jpg"))->digits()->run();
  if (strlen($captcha) != 4) {
    throw new Exception("Failed processing captcha");
  }

  // 发送登录数据
  $loginData = [
    "form_params" => [
      "username"  => $id,
      "password"  => $password,
      "captcha"   => $captcha,
      "warn"      => "true",
      "lt"        => $lt,
      "execution" => "e1s1",
      "_eventId"  => "submit",
      "submit"    => "登录"
    ]
  ];
  $login = $guzzle->post(base64_decode("aHR0cHM6Ly9jYXMuZ3podS5lZHUuY24vY2FzX3NlcnZlci9sb2dpbg"), $loginData)->getBody()->getContents();
  if ((mb_strpos($login, base64_decode("PHRpdGxlPuS4u+mhtSAtIOW5v+W3nuWkp+Wtpi0t6Zeo5oi3PC90aXRsZT4")) === false) && (mb_strpos($login, base64_decode("5b2T5YmN6YCJ6K++5pyf6Ze0")) === false)) {
    throw new Exception("Login Failed");
  }

  // 进入疫情打卡系统
  $start = $guzzle->request("GET", base64_decode("aHR0cDovL3lxdGIuZ3podS5lZHUuY24vaW5mb3BsdXMvZm9ybS9YTllRU0Ivc3RhcnQ"))->getBody()->getContents();

  // 截取 csrfToken
  $csrfToken = mb_substr($start, mb_strpos($start, 'itemscope="csrfToken"') + 31, 32);

  // 获取疫情打卡表单 ID
  $startData = [
    "form_params" => [
      "idc"       => "XNYQSB",
      "release"   => "",
      "csrfToken" => $csrfToken,
      "formData"  => '{"_VAR_URL":"' . base64_decode("aHR0cDovL3lxdGIuZ3podS5lZHUuY24vaW5mb3BsdXMvZm9ybS9YTllRU0Ivc3RhcnQ") . '","_VAR_URL_Attr":"{}"}'
    ]
  ];
  $start = $guzzle->post(base64_decode("aHR0cDovL3lxdGIuZ3podS5lZHUuY24vaW5mb3BsdXMvaW50ZXJmYWNlL3N0YXJ0"), $startData);
  $formID = str_replace(base64_decode("L3JlbmRlcg") . '"]}', "", str_replace('{"errno":0,"ecode":"SUCCEED","error":"Succeed.","entities":["' . base64_decode("aHR0cDovL3lxdGIuZ3podS5lZHUuY24vaW5mb3BsdXMvZm9ybS8"), "", $start->getBody()->getContents()));

  // 获取疫情打卡表单与 autofill 数据
  $render = $guzzle->request("GET", base64_decode("aHR0cDovL3lxdGIuZ3podS5lZHUuY24vaW5mb3BsdXMvZm9ybS8") . $formID . base64_decode("L3JlbmRlcg"));
  $renderData = [
    "headers" => [
      "Referer" => base64_decode("aHR0cDovL3lxdGIuZ3podS5lZHUuY24vaW5mb3BsdXMvZm9ybS8") . $formID . base64_decode("L3JlbmRlcg")
    ],
    "form_params" => [
      "stepId"     => $formID,
      "instanceId" => "",
      "admin"      => "false",
      "rand"       => rand(100, 1000) . "." . sprintf("%010d", rand(0, 10000000000000)),
      "width"      => "1024",
      "lang"       => "en",
      "csrfToken"  => $csrfToken
    ]
  ];
  $render = $guzzle->post(base64_decode("aHR0cDovL3lxdGIuZ3podS5lZHUuY24vaW5mb3BsdXMvaW50ZXJmYWNlL3JlbmRlcg"), $renderData)->getBody()->getContents();

  // 解析 autofill 数据为 json
  $data_json = mb_substr($render, mb_strpos($render, '"data":') + 7);
  $data_json = json_decode(mb_substr($data_json, 0, mb_strpos($data_json, ',"snapshots":')), true);

  // 建立疫情打卡所需的 formData
  $checkinData = [
    "_VAR_EXECUTE_INDEP_ORGANIZE_Name"=> $data_json["_VAR_EXECUTE_INDEP_ORGANIZE_Name"],
    "_VAR_ACTION_REALNAME"=> $data_json["_VAR_ACTION_REALNAME"],
    "_VAR_EXECUTE_ORGANIZES_Names"=> $data_json["_VAR_EXECUTE_ORGANIZES_Names"],
    "_VAR_RELEASE"=> "true",
    "_VAR_NOW_MONTH"=> strval($data_json["_VAR_NOW_MONTH"]),
    "_VAR_ACTION_USERCODES"=> $data_json["_VAR_ACTION_USERCODES"],
    "_VAR_ACTION_ACCOUNT"=> $data_json["_VAR_ACTION_ACCOUNT"],
    "_VAR_ACTION_ORGANIZES_Names"=> $data_json["_VAR_ACTION_ORGANIZES_Names"],
    "_VAR_EXECUTE_ORGANIZES_Codes"=> $data_json["_VAR_EXECUTE_ORGANIZES_Codes"],
    "_VAR_URL_Attr"=> $data_json["_VAR_URL_Attr"],
    "_VAR_EXECUTE_INDEP_ORGANIZES_Names"=> $data_json["_VAR_EXECUTE_INDEP_ORGANIZES_Names"],
    "_VAR_POSITIONS"=> $data_json["_VAR_POSITIONS"],
    "_VAR_EXECUTE_INDEP_ORGANIZES_Codes"=> $data_json["_VAR_EXECUTE_INDEP_ORGANIZES_Codes"],
    "_VAR_EXECUTE_POSITIONS"=> $data_json["_VAR_EXECUTE_POSITIONS"],
    "_VAR_ACTION_ORGANIZES_Codes"=> $data_json["_VAR_ACTION_ORGANIZES_Codes"],
    "_VAR_EXECUTE_INDEP_ORGANIZE"=> $data_json["_VAR_EXECUTE_INDEP_ORGANIZE"],
    "_VAR_NOW_YEAR"=> strval($data_json["_VAR_NOW_YEAR"]),
    "_VAR_ACTION_INDEP_ORGANIZES_Codes"=> $data_json["_VAR_ACTION_INDEP_ORGANIZES_Codes"],
    "_VAR_ACTION_ORGANIZE"=> $data_json["_VAR_ACTION_ORGANIZE"],
    "_VAR_EXECUTE_ORGANIZE"=> $data_json["_VAR_EXECUTE_ORGANIZE"],
    "_VAR_ACTION_INDEP_ORGANIZE"=> $data_json["_VAR_ACTION_INDEP_ORGANIZE"],
    "_VAR_ACTION_INDEP_ORGANIZE_Name"=> $data_json["_VAR_ACTION_INDEP_ORGANIZE_Name"],
    "_VAR_ACTION_ORGANIZE_Name"=> $data_json["_VAR_ACTION_ORGANIZE_Name"],
    "_VAR_OWNER_ORGANIZES_Codes"=> $data_json["_VAR_OWNER_ORGANIZES_Codes"],
    "_VAR_ADDR"=> $data_json["_VAR_ADDR"],
    "_VAR_OWNER_ORGANIZES_Names"=> $data_json["_VAR_OWNER_ORGANIZES_Names"],
    "_VAR_URL"=> base64_decode("aHR0cDovL3lxdGIuZ3podS5lZHUuY24vaW5mb3BsdXMvZm9ybS8") . $formID . base64_decode("L3JlbmRlcg"),
    "_VAR_EXECUTE_ORGANIZE_Name"=> $data_json["_VAR_EXECUTE_ORGANIZE_Name"],
    "_VAR_ACTION_INDEP_ORGANIZES_Names"=> $data_json["_VAR_ACTION_INDEP_ORGANIZES_Names"],
    "_VAR_OWNER_ACCOUNT"=> $data_json["_VAR_OWNER_ACCOUNT"],
    "_VAR_STEP_CODE"=> $data_json["_VAR_STEP_CODE"],
    "_VAR_OWNER_USERCODES"=> $data_json["_VAR_OWNER_USERCODES"],
    "_VAR_NOW_DAY"=> strval($data_json["_VAR_NOW_DAY"]),
    "_VAR_OWNER_REALNAME"=> $data_json["_VAR_OWNER_REALNAME"],
    "_VAR_ENTRY_TAGS"=> "疫情应用,移动端",
    "_VAR_NOW"=> time(),
    "_VAR_ENTRY_NUMBER"=> strval($data_json["_VAR_ENTRY_NUMBER"]),
    "_VAR_ENTRY_NAME"=> "学生健康状况申报_",
    "_VAR_STEP_NUMBER"=> strval($data_json["_VAR_STEP_NUMBER"]),
    "fieldFLid"=> $data_json["fieldFLid"],
    "fieldHQRQ"=> $data_json["fieldHQRQ"],
    "fieldDQSJ"=> $data_json["fieldDQSJ"],
    "fieldSQSJ"=> $data_json["fieldSQSJ"],
    "fieldJBXXxm"=> $data_json["fieldJBXXxm"],
    "fieldJBXXxm_Name"=> $data_json["fieldJBXXxm_Name"],
    "fieldJBXXgh"=> $data_json["fieldJBXXgh"],
    "fieldJBXXnj"=> $data_json["fieldJBXXnj"],
    "fieldJBXXbj"=> $data_json["fieldJBXXbj"],
    "fieldJBXXxb"=> $data_json["fieldJBXXxb"],
    "fieldJBXXxb_Name"=> $data_json["fieldJBXXxb_Name"],
    "fieldJBXXlxfs"=> $data_json["fieldJBXXlxfs"],
    "fieldJBXXcsny"=> "",
    "fieldJBXXdw"=> $data_json["fieldJBXXdw"],
    "fieldJBXXdw_Name"=> $data_json["fieldJBXXdw_Name"],
    "fieldJBXXbz"=> $data_json["fieldJBXXbz"],
    "fieldJBXXbz_Name"=> $data_json["fieldJBXXbz_Name"],
    "fieldJBXXbz_Attr"=> '{\"_parent\":\"' . $data_json["fieldJBXXbj"] . '\"}',
    "fieldJBXXfdy"=> $data_json["fieldJBXXfdy"],
    "fieldJBXXfdy_Name"=> $data_json["fieldJBXXfdy_Name"],
    "fieldJBXXfdy_Attr"=> '{\"_parent\":\"' . $data_json["fieldYCFDY"] . '\"}',
    "fieldjgs"=> $data_json["fieldjgs"],
    "fieldjgs_Name"=> $data_json["fieldjgs_Name"],
    "fieldjgshi"=> $data_json["fieldjgshi"],
    "fieldjgshi_Name"=> $data_json["fieldjgshi_Name"],
    "fieldjgshi_Attr"=> '{\"_parent\":\"' . $data_json["fieldjgs"] . '\"}',
    "fieldJBXXxnjzbgdz"=> $data_json["fieldJBXXxnjzbgdz"],
    "fieldJBXXJG"=> $data_json["fieldJBXXJG"],
    "fieldJBXXjgs"=> $data_json["fieldJBXXjgs"],
    "fieldJBXXjgs_Name"=> $data_json["fieldJBXXjgs_Name"],
    "fieldJBXXjgshi"=> $data_json["fieldJBXXjgshi"],
    "fieldJBXXjgshi_Name"=> $data_json["fieldJBXXjgshi_Name"],
    "fieldJBXXjgshi_Attr"=> '{\"_parent\":\"' . $data_json["fieldJBXXjgs"] . '\"}',
    "fieldJBXXjgq"=> $data_json["fieldJBXXjgq"],
    "fieldJBXXjgq_Name"=> $data_json["fieldJBXXjgq_Name"],
    "fieldJBXXjgq_Attr"=> '{\"_parent\":\"' . $data_json["fieldJBXXjgshi"] . '\"}',
    "fieldJBXXjgsjtdz"=> $data_json["fieldJBXXjgsjtdz"],
    "fieldJBXXdrsfwc"=> $data_json["fieldJBXXdrsfwc"],
    "fieldJBXXsheng"=> $data_json["fieldJBXXsheng"],
    "fieldJBXXsheng_Name"=> "",
    "fieldJBXXshi"=> $data_json["fieldJBXXshi"],
    "fieldJBXXshi_Name"=> "",
    "fieldJBXXshi_Attr"=> '{\"_parent\":\"\"}',
    "fieldJBXXqu"=> $data_json["fieldJBXXqu"],
    "fieldJBXXqu_Name"=> "",
    "fieldJBXXqu_Attr"=> '{\"_parent\":\"\"}',
    "fieldJBXXqjtxxqk"=> $data_json["fieldJBXXqjtxxqk"],
    "fieldSTQKbrstzk1"=> $data_json["fieldSTQKbrstzk1"],
    "fieldSTQKfs"=> $data_json["fieldSTQKfs"],
    "fieldSTQKks"=> $data_json["fieldSTQKks"],
    "fieldSTQKxm"=> $data_json["fieldSTQKxm"],
    "fieldSTQKfl"=> $data_json["fieldSTQKfl"],
    "fieldSTQKhxkn"=> $data_json["fieldSTQKhxkn"],
    "fieldSTQKfx"=> $data_json["fieldSTQKfx"],
    "fieldSTQKqt"=> $data_json["fieldSTQKqt"],
    "fieldSTQKqtms"=> $data_json["fieldSTQKqtms"],
    "fieldSTQKfrtw"=> $data_json["fieldSTQKfrtw"],
    "fieldSTQKfrsj"=> "",
    "fieldSTQKclfs"=> $data_json["fieldSTQKclfs"],
    "fieldSTQKzd"=> $data_json["fieldSTQKzd"],
    "fieldSTQKbrstzk"=> $data_json["fieldSTQKbrstzk"],
    "fieldSTQKglfs"=> $data_json["fieldSTQKglfs"],
    "fieldSTQKgldd"=> $data_json["fieldSTQKgldd"],
    "fieldSTQKglkssj"=> "",
    "fieldSTQKzdjgmc"=> $data_json["fieldSTQKzdjgmc"],
    "fieldSTQKzdmc"=> $data_json["fieldSTQKzdmc"],
    "fieldSTQKzdkssj"=> "",
    "fieldSTQKzljgmc"=> $data_json["fieldSTQKzljgmc"],
    "fieldSTQKzysj"=> "",
    "fieldSTQKzdjgmcc"=> $data_json["fieldSTQKzdjgmcc"],
    "fieldSTQKpcsj"=> "",
    "fieldSTQKjtcystzk1"=> $data_json["fieldSTQKjtcystzk1"],
    "fieldSTQKjtcyfs"=> $data_json["fieldSTQKjtcyfs"],
    "fieldSTQKjtcyks"=> $data_json["fieldSTQKjtcyks"],
    "fieldSTQKjtcyxm"=> $data_json["fieldSTQKjtcyxm"],
    "fieldSTQKjtcyfl"=> $data_json["fieldSTQKjtcyfl"],
    "fieldSTQKjtcyhxkn"=> $data_json["fieldSTQKjtcyhxkn"],
    "fieldSTQKjtcyfx"=> $data_json["fieldSTQKjtcyfx"],
    "fieldSTQKjtcyqt"=> $data_json["fieldSTQKjtcyqt"],
    "fieldSTQKjtcyqtms"=> $data_json["fieldSTQKjtcyqtms"],
    "fieldSTQKjtcyfrtw"=> $data_json["fieldSTQKjtcyfrtw"],
    "fieldSTQKjtcyfrsj"=> "",
    "fieldSTQKjtcyclfs"=> $data_json["fieldSTQKjtcyclfs"],
    "fieldSTQKjtcyzd"=> $data_json["fieldSTQKjtcyzd"],
    "fieldSTQKjtcystzk"=> $data_json["fieldSTQKjtcystzk"],
    "fieldSTQKjtcyglfs"=> $data_json["fieldSTQKjtcyglfs"],
    "fieldSTQKjtcygldd"=> $data_json["fieldSTQKjtcygldd"],
    "fieldSTQKjtcyglkssj"=> "",
    "fieldSTQKjtcyzdjgmc"=> $data_json["fieldSTQKjtcyzdjgmc"],
    "fieldSTQKjtcyzdmc"=> $data_json["fieldSTQKjtcyzdmc"],
    "fieldSTQKjtcyzdkssj"=> "",
    "fieldSTQKjtcyzljgmc"=> $data_json["fieldSTQKjtcyzljgmc"],
    "fieldSTQKjtcyzysj"=> "",
    "fieldSTQKjtcyzdjgmcc"=> $data_json["fieldSTQKjtcyzdjgmcc"],
    "fieldSTQKjtcypcsj"=> "",
    "fieldSTQKrytsqkqsm"=> $data_json["fieldSTQKrytsqkqsm"],
    "fieldCXXXszsqsfyyshqzbl"=> $data_json["fieldCXXXszsqsfyyshqzbl"],
    "fieldCXXXqjymsxgqk"=> $data_json["fieldCXXXqjymsxgqk"],
    "fieldCXXXsfjcgyshqzbl"=> $data_json["fieldCXXXsfjcgyshqzbl"],
    "fieldCXXXksjcsj"=> "",
    "fieldCXXXzhycjcsj"=> "",
    "fieldJCDDs"=> $data_json["fieldJCDDs"],
    "fieldJCDDs_Name"=> "",
    "fieldJCDDshi"=> $data_json["fieldJCDDshi"],
    "fieldJCDDshi_Name"=> "",
    "fieldJCDDshi_Attr"=> '{\"_parent\":\"\"}',
    "fieldJCDDq"=> $data_json["fieldJCDDq"],
    "fieldJCDDq_Name"=> "",
    "fieldJCDDq_Attr"=> '{\"_parent\":\"\"}',
    "fieldJCDDqmsjtdd"=> $data_json["fieldJCDDqmsjtdd"],
    "fieldCXXXjcdr"=> $data_json["fieldCXXXjcdr"],
    "fieldCXXXjcdqk"=> $data_json["fieldCXXXjcdqk"],
    "fieldYQJLsfjcqtbl"=> $data_json["fieldYQJLsfjcqtbl"],
    "fieldYQJLksjcsj"=> "",
    "fieldYQJLzhycjcsj"=> "",
    "fieldYQJLjcdry"=> $data_json["fieldYQJLjcdry"],
    "fieldYQJLjcdds"=> $data_json["fieldYQJLjcdds"],
    "fieldYQJLjcdds_Name"=> "",
    "fieldYQJLjcddshi"=> $data_json["fieldYQJLjcddshi"],
    "fieldYQJLjcddshi_Name"=> "",
    "fieldYQJLjcddshi_Attr"=> '{\"_parent\":\"\"}',
    "fieldYQJLjcddq"=> $data_json["fieldYQJLjcddq"],
    "fieldYQJLjcddq_Name"=> "",
    "fieldYQJLjcddq_Attr"=> '{\"_parent\":\"\"}',
    "fieldYQJLjcdryjkqk"=> $data_json["fieldYQJLjcdryjkqk"],
    "fieldqjymsjtqk"=> $data_json["fieldqjymsjtqk"],
    "fieldJKMsfwlm"=> "1",
    "fieldJKMjt"=> $data_json["fieldJKMjt"],
    "fieldCXXXsftjhb"=> $data_json["fieldCXXXsftjhb"],
    "fieldCXXXsftjhbjtdz"=> $data_json["fieldCXXXsftjhbjtdz"],
    "fieldCXXXsftjhbjtdz_Name"=> "",
    "fieldCXXXsftjhbs"=> $data_json["fieldCXXXsftjhbs"],
    "fieldCXXXsftjhbs_Name"=> "",
    "fieldCXXXsftjhbs_Attr"=> '{\"_parent\":\"\"}',
    "fieldCXXXsftjhbq"=> $data_json["fieldCXXXsftjhbq"],
    "fieldCXXXsftjhbq_Name"=> "",
    "fieldCXXXsftjhbq_Attr"=> '{\"_parent\":\"\"}',
    "fieldCXXXddsj"=> "",
    "fieldCXXXsfylk"=> $data_json["fieldCXXXsfylk"],
    "fieldCXXXlksj"=> "",
    "fieldYZNSFJCHS"=> "2",
    "fieldJCSJ"=> "",
    "fieldCNS"=> true,
    "fieldJKHDDzt"=> $data_json["fieldJKHDDzt"],
    "fieldJKHDDzt_Name"=> $data_json["fieldJKHDDzt_Name"],
    "fieldzgzjzdzq"=> $data_json["fieldzgzjzdzq"],
    "fieldzgzjzdzq_Name"=> "",
    "fieldzgzjzdzq_Attr"=> '{\"_parent\":\"\"}',
    "fieldzgzjzdzjtdz"=> $data_json["fieldzgzjzdzjtdz"],
    "fieldzgzjzdzshi"=> $data_json["fieldzgzjzdzshi"],
    "fieldzgzjzdzshi_Name"=> "",
    "fieldzgzjzdzshi_Attr"=> '{\"_parent\":\"\"}',
    "fieldzgzjzdzs"=> $data_json["fieldzgzjzdzs"],
    "fieldzgzjzdzs_Name"=> "",
    "fieldCXXXcxzt"=> $data_json["fieldCXXXcxzt"],
    "fieldCXXXjtgjbc"=> $data_json["fieldCXXXjtgjbc"],
    "fieldCXXXjtfsqtms"=> $data_json["fieldCXXXjtfsqtms"],
    "fieldCXXXjtfsqt"=> $data_json["fieldCXXXjtfsqt"],
    "fieldCXXXjtfslc"=> $data_json["fieldCXXXjtfslc"],
    "fieldCXXXjtfspc"=> $data_json["fieldCXXXjtfspc"],
    "fieldCXXXjtfsdb"=> $data_json["fieldCXXXjtfsdb"],
    "fieldCXXXjtfshc"=> $data_json["fieldCXXXjtfshc"],
    "fieldCXXXjtfsfj"=> $data_json["fieldCXXXjtfsfj"],
    "fieldCXXXfxcfsj"=> "",
    "fieldCXXXcqwdq"=> $data_json["fieldCXXXcqwdq"],
    "fieldCXXXdqszd"=> $data_json["fieldCXXXdqszd"],
    "fieldCXXXssh"=> $data_json["fieldCXXXssh"],
    "fieldCXXXfxxq"=> $data_json["fieldCXXXfxxq"],
    "fieldCXXXfxxq_Name"=> "",
    "fieldCXXXjtjtzz"=> $data_json["fieldCXXXjtjtzz"],
    "fieldCXXXjtzzq"=> $data_json["fieldCXXXjtzzq"],
    "fieldCXXXjtzzq_Name"=> $data_json["fieldCXXXjtzzq_Name"],
    "fieldCXXXjtzzs"=> $data_json["fieldCXXXjtzzs"],
    "fieldCXXXjtzzs_Name"=> $data_json["fieldCXXXjtzzs_Name"],
    "fieldCXXXjtzz"=> $data_json["fieldCXXXjtzz"],
    "fieldCXXXjtzz_Name"=> $data_json["fieldCXXXjtzz_Name"],
    "fieldSTQKqtqksm"=> $data_json["fieldSTQKqtqksm"],
    "fieldSHENGYC"=> $data_json["fieldSHENGYC"],
    "fieldYCFDY"=> $data_json["fieldYCFDY"],
    "fieldYCBZ"=> $data_json["fieldYCBZ"],
    "fieldYCBJ"=> $data_json["fieldYCBJ"],
    "fieldLYYZM"=> $data_json["fieldLYYZM"]
  ];
  $checkinData = str_replace("\\\\", "", json_encode($checkinData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

  // 执行疫情打卡操作
  $doActionData = [
    "headers" => [
      "User-Agent" => "Mozilla/5.0 (Linux; Android 12.0; Pixel 5; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/89.0.4389.90 Mobile Safari/537.36 wxwork/3.0.28 MicroMessenger/7.0.1 NetType/WIFI Language/en Lang/en"
    ],
    "form_params" => [
      "actionId"    => 1,
      "formData"    => $checkinData,
      "remark"      => "",
      "rand"        => rand(100, 1000) . "." . sprintf("%010d", rand(0, 10000000000000)),
      "nextUsers"   => "{}",
      "stepId"      => $formID,
      "timestamp"   => time(),
      "boundFields" => "fieldSTQKzdjgmc,fieldSTQKjtcyglkssj,fieldCXXXsftjhb,fieldzgzjzdzjtdz,fieldJCDDqmsjtdd,fieldSHENGYC,fieldYQJLksjcsj,fieldSTQKjtcyzd,fieldJBXXjgsjtdz,fieldSTQKbrstzk,fieldSTQKfrtw,fieldSTQKjtcyqt,fieldCXXXjtfslc,fieldJBXXlxfs,fieldSTQKpcsj,fieldJKMsfwlm,fieldJKHDDzt,fieldYQJLsfjcqtbl,fieldYQJLzhycjcsj,fieldJCSJ,fieldSTQKfl,fieldSTQKhxkn,fieldJBXXbz,fieldCXXXsfylk,fieldFLid,fieldjgs,fieldSTQKglfs,fieldCXXXsfjcgyshqzbl,fieldSTQKjtcyfx,fieldCXXXszsqsfyyshqzbl,fieldJCDDshi,fieldSTQKrytsqkqsm,fieldJCDDs,fieldSTQKjtcyfs,fieldSTQKjtcyzljgmc,fieldSQSJ,fieldzgzjzdzs,fieldzgzjzdzq,fieldJBXXnj,fieldSTQKjtcyzdkssj,fieldSTQKfx,fieldSTQKfs,fieldYQJLjcdry,fieldCXXXjtfsdb,fieldCXXXcxzt,fieldYQJLjcddshi,fieldCXXXjtjtzz,fieldCXXXsftjhbs,fieldHQRQ,fieldSTQKjtcyqtms,fieldCXXXksjcsj,fieldSTQKzdkssj,fieldSTQKjtcyzysj,fieldjgshi,fieldSTQKjtcyxm,fieldJBXXsheng,fieldJBXXdrsfwc,fieldqjymsjtqk,fieldJBXXdw,fieldCXXXjcdr,fieldCXXXsftjhbjtdz,fieldJCDDq,fieldSTQKjtcyclfs,fieldSTQKxm,fieldCXXXjtgjbc,fieldSTQKjtcygldd,fieldzgzjzdzshi,fieldSTQKjtcyzdjgmcc,fieldSTQKzd,fieldSTQKqt,fieldCXXXlksj,fieldSTQKjtcyfrsj,fieldCXXXjtfsqtms,fieldSTQKjtcyzdmc,fieldCXXXjtfsfj,fieldJBXXfdy,fieldJBXXxm,fieldJKMjt,fieldSTQKzljgmc,fieldCXXXzhycjcsj,fieldCXXXsftjhbq,fieldSTQKqtms,fieldYCFDY,fieldJBXXxb,fieldSTQKglkssj,fieldCXXXjtfspc,fieldSTQKbrstzk1,fieldYCBJ,fieldCXXXssh,fieldSTQKzysj,fieldLYYZM,fieldJBXXgh,fieldCNS,fieldCXXXfxxq,fieldSTQKclfs,fieldSTQKqtqksm,fieldCXXXqjymsxgqk,fieldYCBZ,fieldJBXXxnjzbgdz,fieldSTQKjtcyfl,fieldYZNSFJCHS,fieldSTQKjtcyzdjgmc,fieldCXXXddsj,fieldSTQKfrsj,fieldSTQKgldd,fieldCXXXfxcfsj,fieldJBXXbj,fieldSTQKks,fieldJBXXcsny,fieldCXXXjtzzq,fieldJBXXJG,fieldCXXXdqszd,fieldCXXXjtzzs,fieldJBXXshi,fieldSTQKjtcyfrtw,fieldSTQKjtcystzk1,fieldCXXXjcdqk,fieldSTQKzdmc,fieldSTQKjtcyks,fieldSTQKjtcystzk,fieldCXXXjtfshc,fieldCXXXcqwdq,fieldSTQKjtcypcsj,fieldJBXXqu,fieldJBXXjgshi,fieldYQJLjcddq,fieldYQJLjcdryjkqk,fieldYQJLjcdds,fieldSTQKjtcyhxkn,fieldCXXXjtzz,fieldJBXXjgq,fieldCXXXjtfsqt,fieldJBXXjgs,fieldSTQKzdjgmcc,fieldJBXXqjtxxqk,fieldDQSJ,fieldSTQKjtcyglfs",
      "csrfToken"   => $csrfToken,
      "lang"        => "en"
    ]
  ];
  $doAction = $guzzle->post(base64_decode("aHR0cDovL3lxdGIuZ3podS5lZHUuY24vaW5mb3BsdXMvaW50ZXJmYWNlL2RvQWN0aW9u"), $doActionData);
  if (mb_strpos($doAction->getBody()->getContents(), '"error":"打卡成功"') === false) {
    throw new Exception("Checkin Failed");
  }

  echo "SUCCESS: " . base64_decode("aHR0cDovL3lxdGIuZ3podS5lZHUuY24vaW5mb3BsdXMvZm9ybS8") . $formID . base64_decode("L3JlbmRlcg") . "\n";

} catch (Exception $err) {
  echo "ERROR: " . $err->getMessage() . "\n";
}
?>
