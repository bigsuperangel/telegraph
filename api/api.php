<?php

// 判断是否有文件上传
if (!isset($_FILES["file"])) {
  $result = array(
    "code" => 201,
    "msg" => "没有上传文件！"
  );
  outputResult($result);
  exit;
}

// 获取上传的文件名和扩展名
$file = $_FILES["file"]["name"];
$extension = pathinfo($file, PATHINFO_EXTENSION);

// 判断上传的文件类型是否允许
$fileType = $_FILES["file"]["type"];
$allowedTypes = ["image/gif", "image/jpeg", "image/jpg", "image/pjpeg", "image/x-png", "image/png"];
if (!in_array($fileType, $allowedTypes)) {
  $result = array(
    "code" => 201,
    "msg" => "只允许上传gif、jpeg、jpg、png格式的图片文件！"
  );
  outputResult($result);
  exit;
}

// 定义最大允许上传的文件大小为5MB
$maxSize = 5 * 1024 * 1024;
$fileSize = $_FILES["file"]["size"];

// 如果上传的文件大小超过最大允许大小，则进行压缩
if ($fileSize > $maxSize) {
  $compressedImage = compress_image($_FILES["file"], $maxSize);
  if (!$compressedImage) {
    $result = array(
      "code" => 201,
      "msg" => "图片压缩失败！"
    );
    outputResult($result);
    exit;
  }
  $fileType = $compressedImage['type'];
  $fileSize = $compressedImage['size'];
  $filepath = $compressedImage['tmp_name'];
} else {
  $filepath = $_FILES["file"]["tmp_name"];
}

// 调用upload_image函数上传图片
$imgpath = upload_image($filepath, $fileType, $file);
if ($imgpath) {
  $image_host = 'https://i'.rand(0, 3).'.wp.com/修改成你的反代域名';
  $result = array(
    "code" => 200,
    "msg" => "上传成功",
    "url" => $image_host . $imgpath
  );
} else {
  $result = array(
    "code" => 201,
    "msg" => "图片上传失败！请检查接口可用性！"
  );
}

// 输出结果
outputResult($result);

// 压缩图片函数
function compress_image($image, $maxSize) {
  if ($image['size'] <= $maxSize) {
    return $image;
  }

  $temp_file = tempnam(sys_get_temp_dir(), 'image');
  if (!$temp_file) {
    return false;
  }
  imagejpeg(imagecreatefromstring(file_get_contents($image['tmp_name'])), $temp_file, 80);
  $compressed_size = filesize($temp_file);

  if ($compressed_size <= $maxSize) {
    return array(
      'name' => $image['name'],
      'type' => 'image/jpeg',
      'tmp_name' => $temp_file,
      'error' => 0,
      'size' => $compressed_size
    );
  } else {
    unlink($temp_file);
    return false;
  }
}

// 输出结果函数
function outputResult($result) {
  header("Content-type: application/json");
  echo json_encode($result, true);
}

// 上传图片函数
function upload_image($filepath, $fileType, $fileName) {
  $data = array(
    'file' => curl_file_create($filepath, $fileType, $fileName)
  );
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://telegra.ph/upload');
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $response = curl_exec($ch);
  curl_close($ch);

  $json = json_decode($response, true);
  if ($json && isset($json[0]['src'])) {
    return $json[0]['src'];
  } else {
    return false;
  }
}