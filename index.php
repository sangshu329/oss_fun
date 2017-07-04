<?php
/**
 * Created by PhpStorm.
 * User: sien
 * Email: sangshu.sun@163.com
 * Date: 2017-06-21 0021
 * Time: 13:47
 */
require 'aliyun/autoload.php';
use OSS\OssClient;
use OSS\Core\OssException;

require 'config.php';

try {
    $ossclient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
    $rs = getFileList($ossclient, $bucket, 'public/feedback/20170704/');
    echo '<pre>';
    print_r($rs);
//    $res = getImageList($ossclient,$bucket,'',$extUrl,$rs);
//    print_r($res);
} catch (OssException $e) {
    echo $e->getMessage();
    die;
}

/* 获取文件及文件目录列表 */
function getFileList($obj, $bucket, $dir = '', $maxKey = 30, $delimiter = '/', $nextMarker = '')
{
    $fileList = [];
    $dirList = [];
    $storageList = [
        'file' => [],
        'dir' => [],
    ];
    while (true) {
        $options = [
            'delimiter' => $delimiter,
            'prefix' => $dir,
            'max-keys' => $maxKey,
            'marker' => $nextMarker
        ];
        try {
            $fileListInfo = $obj->listObjects($bucket, $options);
        } catch (OssException $e) {
            return $e->getMessage();
        }
        $nextMarker = $fileListInfo->getNextMarker();
        $fileItem = $fileListInfo->getObjectList();
        $dirItem = $fileListInfo->getPrefixList();
        $fileList = $fileItem;
        $dirList = $dirItem;
        if ($nextMarker === '') break;
    }
    if (!empty($fileList)) {
        $arr = [];
        foreach ($fileList as $item) {
            $arr['key'] = $item->getKey();
            $arr['lastModified'] = $item->getLastModified();
            $arr['formatLastModified'] = date('Y-m-d H:i:s',strtotime($arr['lastModified']));
            $arr['eTag'] = $item->getETag();
            $arr['type'] = $item->getType();
            $arr['size'] = $item->getSize();
            $arr['formatSize'] = formatFielSize($arr['size']);
            $arr['storageClass'] = $item->getStorageClass();
            $storageList['file'][] = $arr;
        }
    }
    $storageList['dir'] = $dir;
    if (!empty($dirList)) {
        foreach ($dirList as $item) {
            $storageList['nextDir'][] = $item->getPrefix();
        }
    }
    /*if (!empty($storageList['nextDir']) && $is_deep) {
        foreach ($storageList['nextDir'] as $item) {
            if($item!='./'){
                getFileList($obj,$bucket,$item);
            }
        }
    }*/
    return $storageList;
}

/*获取文件图片url*/
function getImageList($obj,$bucket,$dir='',$domain,$listData,$is_html=true)
{
    $imgList = [];
    if(!empty($listData)){
        if(!empty($listFile = $listData['file'])) {
            foreach ($listFile as $item) {
                $imgList[] = $domain.$item['key'];
            }
        }
    }
    if(!$is_html){
        return $imgList;
    } else{
        array_walk($imgList,function(&$value,$key){
            $value ='<a href="'.$value.'" target="_blank"> '.$value.' </a>';
        });
        return implode('<br />',$imgList);
    }
}


function formatFielSize($num){
    if (is_int($num)){
        if($num >= 1073741824) {
            $filesize = round($num/1073741824,2).'GB';
        } elseif($num>=1048576) {
            $filesize = round($num/1048576,2).'MB';
        } elseif($num >= 1024) {
            $filesize = round($num/1024,2).'KB';
        } else {
            $filesize = $num .'Bytes';
        }
        return $filesize;
    }else{
        return 'NAN';
    }
}