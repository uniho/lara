<?php

final class JJJ
{
    // $baseDir を基準とした $fullPath の相対パスを取得します。
    // 例：$baseDir = '/home/foo/', $fullPath = '/home/bar/path/to.txt' => 戻り値 '../bar/path/to.txt'
    public static function relativePath($baseDir, $fullPath) {
        // 末尾の "/" を削除する
        $baseDir = rtrim($baseDir, "/");
        $fullPath = rtrim($fullPath, "/");
        
        // 連続する "/" を一つにまとめる
        $baseDir = preg_replace('/\/+/', '/', $baseDir);
        $fullPath = preg_replace('/\/+/', '/', $fullPath);
        
        // "/" で分割する
        $baseDirItems = explode('/', $baseDir);
        $fullPathItems = explode('/', $fullPath);
        
        // パスの先頭の共通部分を除去する
        while (count($baseDirItems) > 0 && count($fullPathItems) > 0) {
            if ($baseDirItems[0] !== $fullPathItems[0]) break;
            array_shift($baseDirItems);
            array_shift($fullPathItems);
        }
            
        // 相対パスの先頭の ".", ".." 部分を構築する
        $pathItems = array();
        if (count($baseDirItems) > 0) {
            // ディレクトリを遡る
            for($i = 0; $i < count($baseDirItems); $i++) {
                $pathItems[] = '..';
            }
        } else {
            // $baseDir の下にある場合は "." で始める
            $pathItems[] = '.';
        }
        
        // ".", ".." 以降のパスをマージする
        $pathItems = array_merge($pathItems, $fullPathItems);
        
        return implode('/', $pathItems);
    }  

    //
    public static function relativeRoot($base = false) {
        if (!$base) $base = "/" . request()->path();
        return self::relativePath($base, "/");
    }
}
