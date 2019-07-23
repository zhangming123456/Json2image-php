# Json2image-PHP

## Project 使用

```
$config = array(
            'children' => array(
                array(
                    'type' => 'image',              // 图片
                    'url' => '../public/gh_35ab02b1c6fd_258.jpg',       // 图片资源路径（支持url与本地文件）
                    'is_yuan' => true,              // true图片圆形处理 (功能未开发)
                    'stream' => 0,                  // 图片资源是否是字符串图像流
                    'left' => 182,                  // x
                    'top' => -140,                  // y
                    'right' => 0,
                    'bottom' => 0,
                    'width' => 300,                 // 图像宽
                    'height' => 300,                // 图像高
                    'opacity' => 100,               // 透明度 0-100
                    'bgColor' => '255,255,255',     // 背景颜色
                ),
                array(
                    'type' => 'qrcode',             // 二维码类型
                    'text' => '123456',             // 二维码内容
                    'left' => 200,                  // x   
                    'top' => -140,                  // y
                    'right' => 0,
                    'bottom' => 0,
                    'width' => 150,                 // 图像宽
                    'height' => 150,                // 图像高
                    'opacity' => 100,               // 透明度 0-100
                    'bgColor' => '0,255,255',
                    'logo' => '../public/logo.png' // logo图片资源路径（支持url与本地文件）
                ),
                array(
                    'type' => 'text',               // 文字类型
                    'text' => '你好',               // 文字内容
                    'left' => 182,                  // x 
                    'top' => 105,                   // y
                    'width' => 105,                 // 限制最大宽度
                    'fontPath' => '../public/azm-fonts/PingFang Regular.ttf',     // 字体文件位置
                    'fontSize' => 18,               // 字号
                    'fontColor' => '255,255,255',   // 字体颜色
                    'angle' => 0,                   // 旋转角度
                    'line' => 2,                    // 文字超长最大宽度的行数，显示...
                )
            ),
            'background' => 'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1563543094670&di=dfb8c2cbae0338e90e1994f8a0d62059&imgtype=0&src=http%3A%2F%2Fpic41.nipic.com%2F20140603%2F18347945_133954236142_2.jpg',// 背景图资源路径（支持url与本地文件）（必填）
            或
            'background' => array(
                    'url' => '../public/gh_35ab02b1c6fd_258.jpg',       // 背景图资源路径（支持url与本地文件）（可选）
                    'stream' => 0,                  // 背景图资源是否是字符串流
                    'width' => 300,                 // 背景宽
                    'height' => 300,                // 背景高
                    'opacity' => 100,               // 背景透明度 0-100
                    'bgColor' => '255,255,255',     // 背景颜色
            ),
        );
    
    /**
    * 生成宣传海报
    * @param array $config 参数,包括图片和文字
    * @param boolean $output 是否输出（流|base64）
    * @param string $filename 生成海报文件名,不传此参数则不生成文件,直接输出图片
    * @return bool|string
    */
    createPoster($config)
```


