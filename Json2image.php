<?php
/**
 * Created by PhpStorm.
 * User: Aaronzm
 * Date: 2019/7/22
 * Time: 16:10
 */

class Json2image
{

    /**
     * 生成宣传海报
     * @param array $config 参数,包括图片和文字
     * @param boolean $output 是否输出（流|base64）
     * @param string $filename 生成海报文件名,不传此参数则不生成文件,直接输出图片
     * @return bool|string
     */
    static function createPoster($config = array(), $output = false, $filename = "")
    {
        $imageDefault = array(
            'left' => 0,
            'top' => 0,
            'right' => 0,
            'bottom' => 0,
            'width' => 100,
            'height' => 100,
            'opacity' => 100
        );
        $textDefault = array(
            'text' => '',
            'left' => 0,
            'top' => 0,
            'fontSize' => 32,       //字号
            'fontColor' => '255,255,255', //字体颜色
            'angle' => 0,
        );

        if (empty($config['background'])) return false;

        // 创建模板
        $bom = self::createPosterModel($config['background']);
        $backgroundWidth = $bom['maxWidth'];  //背景宽度
        $backgroundHeight = $bom['maxHeight'];  //背景高度
        $color = $bom['color'];  //背景颜色
        $imageRes = $bom['card'];

        if (!empty($config['children'])) {
            foreach ($config['children'] as $key => $val) {
                if (!empty($val['type'])) {
                    switch ($val['type']) {
                        case 'image': //处理了图片
                            $val = array_merge($imageDefault, $val);
                            self::draw_image($imageRes, $val, array(
                                'maxWidth' => $backgroundWidth,
                                'maxHeight' => $backgroundHeight
                            ));
                            break;
                        case 'text': //处理文字
                            $val = array_merge($textDefault, $val);
                            self::draw_text($imageRes, $val, $backgroundWidth);
                            break;
                        case 'qrcode': //处理二维码
                            self::draw_qrcode($val, array(
                                'maxWidth' => $backgroundWidth,
                                'maxHeight' => $backgroundHeight
                            ), $imageRes);
                            break;
                    }
                }
            }
        }
        //生成图片
        if (!empty($filename)) {
            $res = imagepng($imageRes, $filename, 90); //保存到本地
            imagedestroy($imageRes);
            if (!$res) return false;
            return $filename;
        } else if (!empty($output) && $output) {
            return self::outputStream($imageRes);
        } else {
            header("Content-type:image/png");
            imagepng($imageRes);     //在浏览器上显示
            imagedestroy($imageRes);
        }
    }

    /**
     * 获取图片资源
     * @param array $val
     * @return array
     */
    static function getImgPublic($val)
    {
        if (empty($val)) return;
        $url = is_string($val) ? $val : (empty($val['url']) ? false : $val['url']);
        if ($url === false) return;
        if (!empty($val['stream']) && $val['stream'] === 1) {   //如果传的是字符串图像流
            $info = getimagesizefromstring($val['url']);
            $function = 'imagecreatefromstring';
        } else {
            $info = getimagesize($url);
            $function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        }
        $url = $function($url);
//        echo json_encode($info, JSON_UNESCAPED_UNICODE);
        $width = $info[0];  //宽度
        $height = $info[1]; //高度

        $imgInfo = array(
            'info' => $info,
            'function' => $function,
            'img' => $url,
            'width' => $width,
            'height' => $height
        );

        // 3：绘制图片
        $mode = empty($val['mode']) ? 'horizontal' : $val['mode'];
        if (!empty($val['width']) && !empty($val['height'])) {
            $imgInfo['realWidth'] = $mode === 'horizontal' ? $val['width'] : $val['height'] / $height * $width;
            $imgInfo['realHeight'] = $mode === 'vertical' ? $val['height'] : $val['width'] / $width * $height;
        } else {
            $imgInfo['realWidth'] = $width;
            $imgInfo['realHeight'] = $height;
        }
        return $imgInfo;
    }

    /**
     * 创建模板
     * @param array $background
     * @return array
     */
    static function createPosterModel($background)
    {
        $width = 750;
        $height = 1334;
        $Default = array(
            'left' => 0,
            'top' => 0,
            'right' => 0,
            'bottom' => 0,
            'opacity' => 100,
//            'bgColor' => '255,255,255',
//            'width' => 740,
//            'height' => 1334,
        );


        if (empty($background) || is_array($background)) {
            $val = $Default;
            if (!empty($background) && is_array($background)) {
                $val = array_merge($Default, $background);
            }
            if (!empty($val['url'])) $publicInfo = self::getImgPublic($val);
            $maxWidth = empty($val['width']) ? (empty($publicInfo['width']) ? $width : $publicInfo['width']) : $val['width'];
            $maxHeight = empty($val['height']) ? (empty($publicInfo['height']) ? $height : $publicInfo['height']) : $val['height'];

            //创建画布
            $imageRes = imageCreatetruecolor($maxWidth, $maxHeight);

            if (!empty('angle')) $imageRes = imagerotate($imageRes, empty('angle'), 0);
            if (!empty($val['bgColor'])) {
                list($R, $G, $B) = explode(',', $val['bgColor']);
                $color = imagecolorallocate($imageRes, $R, $G, $B);
            } else {
                $color = imagecolorallocate($imageRes, 0, 0, 0);
            }
            if (empty($opts['bgColor'])) {
                imageColorTransparent($imageRes, $color);  //颜色透明
            } else {
                imagefill($imageRes, 0, 0, $color);
            }
            if (!empty($publicInfo)) {
                //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
                imagecopyresampled($imageRes, $publicInfo['img'], 0, 0, 0, 0, $publicInfo['realWidth'], $publicInfo['realHeight'], $publicInfo['width'], $publicInfo['height']);
            }
        } else if (!empty($background)) {
            $info = self::getImgPublic(array('url' => $background));
            $maxWidth = $info['width'];  //背景宽度
            $maxHeight = $info['height'];  //背景高度
            $imageRes = imageCreatetruecolor($maxWidth, $maxHeight);
            //创建画布
            $color = imagecolorallocate($imageRes, 0, 0, 0);
            imagefill($imageRes, 0, 0, $color);
            imagecopyresampled($imageRes, $info['img'], 0, 0, 0, 0, $maxWidth, $maxHeight, $maxWidth, $maxHeight);
        }
        return array(
            'card' => $imageRes,
            'maxWidth' => $maxWidth,
            'maxHeight' => $maxHeight,
            'color' => $color,
        );
    }

    /**
     * 输出文件类型  {流|base64}
     * @param $card
     * @param string $type 设置返回数据 （base64|流）
     * @param string $mimeType 返回 图片MIME类型 （jpeg|jpg|gif|wbmp|webp|bmp|png）
     * @return false|string
     */
    static function outputStream($card, $type = 'base64', $mimeType = 'png')
    {
        if (!empty($card)) {
            //打开缓冲区
            ob_start();
            $s = 'png';
            $mimeType = empty($mimeType) ? 'png' : $mimeType;
            if (!empty($mimeType) && is_string($mimeType)) {
                switch (strtoupper($mimeType)) {
                    case 'JPEG':
                        $s = 'jpeg';
                        header("Content-type:image/" . $s);
                        imagejpeg($card);     //在浏览器上显示
                        break;
                    case 'JPG':
                        $s = 'jpg';
                        header("Content-type:image/" . $s);
                        imagejpeg($card);     //在浏览器上显示
                        break;
                    case 'GIF':
                        $s = 'gif';
                        header("Content-type:image/" . $s);
                        imagegif($card);     //在浏览器上显示
                        break;
                    case 'WBMP':
                        $s = 'wbmp';
                        header("Content-type:image/" . $s);
                        imagewbmp($card);     //在浏览器上显示
                        break;
                    case 'WEBP':
                        $s = 'webp';
                        header("Content-type:image/" . $s);
                        imagewebp($card);     //在浏览器上显示
                        break;
                    case 'BMP':
                        $s = 'bmp';
                        header("Content-type:image/" . $s);
                        imagebmp($card);     //在浏览器上显示
                        break;
                    default:
                        $s = 'png';
                        header("Content-type:image/" . $s);
                        imagepng($card);     //在浏览器上显示
                }
            }
            //这里就是把生成的图片流从缓冲区保存到内存对象上。
            $imageString = ob_get_contents();
            imagedestroy($card);
            //关闭缓冲区
            ob_end_clean();
            if ($type === 'base64') {
                return 'data:image/' . $s . ';base64,' . base64_encode($imageString);
            } else {
                return $imageString;
            }
        }
    }

    /****************************图片区域******************************/
    /**
     * 图片绘制
     * @param $card
     * @param $val
     * @param array $opts
     */
    static function draw_image($card, $val, $opts = array())
    {
        $maxWidth = empty($opts['maxWidth']) ? 750 : $opts['maxWidth'];
        $maxHeight = empty($opts['maxHeight']) ? 1334 : $opts['maxHeight'];
        // 获取图片
        $image = self::getImgPublic($val);
        $res = $image['img'];
        $resWidth = $image['width'];
        $resHeight = $image['height'];
        // 1：建立画板 ，缩放图片至指定尺寸
        $canvas = imagecreatetruecolor($val['width'], $val['height']);

        // 2：绘制背景色
        if (empty($val['bgColor'])) {
            imageColorTransparent($canvas, imagecolorallocate($card, 0, 0, 0));  //颜色透明
        } else {
            list($R, $G, $B) = explode(',', $val['bgColor']);
            imagefill($canvas, 0, 0, imagecolorallocate($card, $R, $G, $B));
        }

        // 3：绘制图片
        //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
        imagecopyresampled($canvas, $res, 0, 0, 0, 0, $image['realWidth'], $image['realHeight'], $resWidth, $resHeight);

        // 4：定位位置
        $val['left'] = $val['left'] < 0 ? $maxWidth - abs($val['left']) - $val['width'] : $val['left'];
        $val['top'] = $val['top'] < 0 ? $maxHeight - abs($val['top']) - $val['height'] : $val['top'];
        //放置图像
        //左，上，右，下，宽度，高度，透明度
        imagecopymerge($card, $canvas, $val['left'], $val['top'], $val['right'], $val['bottom'], $val['width'], $val['height'], $val['opacity']);
    }
    /****************************图片区域******************************/
    /****************************二维码区域******************************/
    /**
     * 生成二维码(流)
     * 一般用于微信扫码支付二维码生成场景
     * @param string $text 二维码内容 示例数据：http://www.tf4.cn或weixin://wxpay/bizpayurl?pr=0tELnh9
     * @param int $size 点的大小：1到10,用于手机端4就可以了
     * @param string $level 容错级别 L（7%），M（15%），Q（25%），H（30%）
     *                      这个参数控制二维码容错率，不同的参数表示二维码可被覆盖的区域百分比。
     *                      利用二维维码的容错率，我们可以将头像放置在生成的二维码图片任何区域。
     * @param int $margin 控制生成二维码的空白区域大小
     * @return bool|false|string 返回的是二进制图片数据
     */
    static function createQRcode($text = '', $size = 300, $level = 'L', $margin = 1)
    {
        include './phpqrcode/phpqrcode.php';
        $object = new \QRcode();
        $modulus = 0.04;
        $errorCorrectionLevel = $level;
        $matrixPointSize = 4;
        if (empty($text)) return false;
        $outfile = false; //默认为否，不生成文件，只将二维码图片返回，否则需要给出存放生成二维码图片的路径
        //打开缓冲区
        ob_start();
        //生成二维码图片
        $object->png($text, $outfile, $errorCorrectionLevel, $matrixPointSize, $margin);
        //这里就是把生成的图片流从缓冲区保存到内存对象上。
        $imageString = ob_get_contents();
        //关闭缓冲区
        ob_end_clean();
        return $imageString;
    }

    /**
     * 生成二维码图片（可生成带logo的二维码）
     * @param array $val 二维码配置信息
     * @param $opts
     * @param $card
     * @return mixed 返回的是二进制图片数据
     */
    static function draw_qrcode($val, $opts = array(), $card = null)
    {
        $logo_default = array(
            'scale' => 25,
            'opacity' => 127
        );

        $level = 'L';
        if (!empty($val['logo'])) {
            $logo_size = !empty($val['logo']['scale']) ? $val['logo']['scale'] : $logo_default['scale'];
            switch (true) {
                case $logo_size > 30:
                    $level = 'H';
                    break;
                case $logo_size > 25:
                    $level = 'Q';
                    break;
                case $logo_size > 15:
                    $level = 'M';
                    break;
                default:
                    $level = 'L';
            }
        }

        $maxWidth = empty($opts['maxWidth']) ? 750 : $opts['maxWidth'];
        $maxHeight = empty($opts['maxHeight']) ? 1334 : $opts['maxHeight'];
        $logo = empty($val['logo']) ? false : $val['logo'];

        if (empty($val['text'])) return;
        $QR_img = self::createQRcode($val['text'], 300, $level); //二维码内容 示例数据：http://www.tf4.cn或weixin://wxpay/bizpayurl?pr=0tELnh9
        if ($QR_img === false) return false;
        $val_qr = array_merge($val, array(
            'url' => $QR_img,
            'stream' => 1,
        ));
        $QRCode = self::createPosterModel($val_qr);
        $qr_width = $QRCode['maxWidth'];  //二维码图片宽度
        $qr_height = $QRCode['maxHeight'];  //二维码图片高度
        $qr_color = $QRCode['color'];  //二维码背景颜色
        $QR = $QRCode['card'];

        //判断是否生成带logo的二维码 或 画板
        if ($logo || !empty($card)) {
            if (is_string($logo) || is_array($logo)) {
                //图片logo路径 示例数据：./Public/Default/logo.jpg
                //注意事项：1、前面记得带点（.）；2、建议图片Logo正方形，且为jpg格式图片；3、图片大小建议为xx*xx
                //注意：一般用于生成带logo的二维码
                if (is_string($logo)) $logo = array('url' => $logo);
                $logo_val = array_merge($logo_default, $logo);
                $LOGO = self::getImgPublic($logo_val);    //源图象连接资源。
                $logo_width = $LOGO['width'];        //logo图片宽度
                $logo_height = $LOGO['height'];        //logo图片高度
                $logo = $LOGO['img'];
                if (!empty($logo_val['scale'])) {
                    $scale = $logo_val['scale'] > 100 ? 100 : $logo_val['scale'];
                    $logo_qr_width = $qr_width / (100 / $scale);
                    $scale = $logo_width / $logo_qr_width;
                } else {
                    $logo_qr_width = $qr_width / 4;       //组合之后logo的宽度(占二维码的1/5)
                    $scale = $logo_width / $logo_qr_width;       //logo的宽度缩放比(本身宽度/组合后的宽度)
                }
                $logo_qr_height = $logo_height / $scale;  //组合之后logo的高度
                $from_width = ($qr_width - $logo_qr_width) / 2;   //组合之后logo左上角所在坐标点

                // 2：绘制背景色
                if (empty($logo_val['bgColor'])) {
                    imageColorTransparent($logo, imagecolorallocate($QR, 0, 0, 0));  //颜色透明
                } else {
                    list($R, $G, $B) = explode(',', $logo_val['bgColor']);
                    imagefill($logo, 0, 0, imagecolorallocate($QR, $R, $G, $B));
                }

                // 3：绘制图片
                // 关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
                imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
//                // 1：建立画板 ，缩放图片至指定尺寸
//                $canvas = imagecreatetruecolor($logo_width, $logo_height);
////                // 2：绘制背景色
////                if (empty($logo_val['bgColor'])) {
////                    imageColorTransparent($canvas, imagecolorallocate($QR, 0, 0, 0));  //颜色透明
////                } else {
////                    list($R, $G, $B) = explode(',', $logo_val['bgColor']);
////                    imagefill($canvas, 0, 0, imagecolorallocate($QR, $R, $G, $B));
////                }
//
//                // 3：绘制图片
//                //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
//                imagecopyresampled($canvas, $logo, 0, 0, 0, 0, $logo_width, $logo_width, $logo_width, $logo_height);
//
//                if (empty($logo_val['scale'])) {
//                    $logo_qr_width = $QR_width / 4;       //组合之后logo的宽度(占二维码的1/5)
//                    $scale = $logo_width / $logo_qr_width;       //logo的宽度缩放比(本身宽度/组合后的宽度)
//                } else {
//                    $scale = $logo_val['scale'];
//                    $logo_qr_width = $logo_width / $scale;
//                }
//                $logo_qr_height = $logo_height / $scale;  //组合之后logo的高度
//                $from_width = ($QR_width - $logo_qr_width) / 2;   //组合之后logo左上角所在坐标点
//
//                $opacity = empty($logo_val['opacity']) ? 100 : $logo_val['opacity'];
//
//                //放置图像
//                //左，上，右，下，宽度，高度，透明度
//                imagecopymerge($QR, $canvas, 0, 0, 0, 0, $logo_qr_width, $logo_qr_height, $opacity);
            }
        }
        if (!empty($card)) {
//            // 1：建立画板 ，缩放图片至指定尺寸
//            $canvas = imagecreatetruecolor($val['width'], $val['height']);
//
//            // 2：绘制背景色
//            if (empty($val['bgColor'])) {
//                imageColorTransparent($canvas, imagecolorallocate($card, 0, 0, 0));  //颜色透明
//            } else {
//                list($R, $G, $B) = explode(',', $val['bgColor']);
//                imagefill($canvas, 0, 0, imagecolorallocate($card, $R, $G, $B));
//            }
//
//            // 3：绘制图片
//            //关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
//            imagecopyresampled($canvas, $QR, 0, 0, 0, 0, $qr_width, $qr_width, $qr_width, $qr_width);

            // 定位位置
            $val['left'] = $val['left'] < 0 ? $maxWidth - abs($val['left']) - $val['width'] : $val['left'];
            $val['top'] = $val['top'] < 0 ? $maxHeight - abs($val['top']) - $val['height'] : $val['top'];

            $opacity = empty($val['opacity']) ? 100 : ($val['opacity'] > 100 ? 100 : $val['opacity']);

            //放置图像
            //左，上，右，下，宽度，高度，透明度
            imagecopymerge($card, $QR, $val['left'], $val['top'], $val['right'], $val['bottom'], $val['width'], $val['height'], $opacity);
        } else {// 没有画板
            return self::outputStream($QR, 'base64', 'jpeg');
        }
    }
    /****************************二维码区域******************************/

    /****************************文字区域******************************/

    /**
     * 绘制文字
     * @param $card
     * @param $pos
     * @param int $maxWidth
     * @return array
     */
    static function draw_text($card, $pos, $maxWidth = 750)
    {
        //    imagettftext
        //    使用指定的字体文件绘制文字
        //    参数2：字体大小
        //    参数3：字体倾斜的角度
        //    参数4、5：文字的x、y坐标
        //    参数6：文字的颜色
        //    参数7：字体文件
        //    参数8：绘制的文字
        list($R, $G, $B) = explode(',', $pos['fontColor']);
        $fontColor = imagecolorallocate($card, $R, $G, $B);
        $pos['left'] = $pos['left'] < 0 ? $maxWidth - abs($pos['left']) : $pos['left'];
        $pos['top'] = $pos['top'] < 0 ? $maxWidth - abs($pos['top']) : $pos['top'];
        $fontPath = $pos['fontPath'];
        $string = $pos['text'];
        $_string = '';
        $__string = '';
        if (empty($pos['width'])) return imagettftext($card, $pos['fontSize'], $pos['angle'], $pos['left'], $pos['top'], $fontColor, $pos['fontPath'], $string);
        $line = 1;
        $is_exceed = false;
        // 省略号宽度
        $box_ellipsis_width = self::get_font_width($pos['fontSize'], $fontPath, '...');
        for ($i = 0; $i < mb_strlen($string); $i++) {
            // 单个文字
            $current_string = mb_substr($string, $i, 1);
            // 拼接好的文字宽度
            $_string_length = self::get_font_width($pos['fontSize'], $fontPath, $_string);
            // 计算的单个文字宽度
            $current_string_length = self::get_font_width($pos['fontSize'], $fontPath, $current_string);
            if (!empty($pos['line']) && $pos['line'] > 0) {// 根据需要显示的行数，判断是否超出文字是否显示省略号
                // 是否开启超出文字检测
                $is__exceed = $line == $pos['line'];
                // 设置当前行是否需要添加省略号
                $w = $is__exceed === true ? $box_ellipsis_width : 0;
                if ($_string_length + $current_string_length + $w < $pos['width']) {// 判断当前行是否超出最大宽度
                    $_string .= $current_string;
                } else if ($is__exceed) { // 超出当前行最大宽度 截断添加省略号
                    $is_exceed = true;
                    break;
                } else { // 超出当前行最大宽度 换行
                    $__string .= $_string . "\n";
                    $_string = mb_substr($string, $i, 1);
                    $line++;
                }
            } else { // 不截断添加省略号
                if ($_string_length + $current_string_length < $pos['width']) {
                    $_string .= $current_string;
                } else {
                    $__string .= $_string . "\n";
                    $_string = mb_substr($string, $i, 1);
                }
            }
        }
        // 拼接多余字符
        $__string .= $_string;
        // 判断是否添加省略号在尾部
        if ($is_exceed === true && !empty($__string)) $__string .= "...";

        $box_height = self::get_font_height($pos['fontSize'], $fontPath, mb_substr($__string, 0, 1));
        imagettftext($card, $pos['fontSize'], 0, $pos['left'], $pos['top'] + $box_height, $fontColor, $fontPath, $__string);
    }

    /**
     * 计算文字宽度
     * @param $fontSize {Number} 像素单位的字体大小
     * @param $fontPath {String} TrueType 字体文件的文件名（可以是 URL）。根据 PHP 所使用的 GD 库版本，可能尝试搜索那些不是以 '/' 开头的文件名并加上 '.ttf' 的后缀并搜索库定义的字体路径
     * @param $text {String} 要度量的字符串
     * @return mixed 返回字体宽度
     */
    static function get_font_width($fontSize, $fontPath, $text)
    {
        // 参数1：像素单位的字体大小。
        // 参数2：将被度量的角度大小。
        // 参数3：TrueType 字体文件的文件名（可以是 URL）。根据 PHP 所使用的 GD 库版本，可能尝试搜索那些不是以 '/' 开头的文件名并加上 '.ttf' 的后缀并搜索库定义的字体路径。
        // 参数4：要度量的字符串。
        /**
         * 返回一个含有 8 个单元的数组表示了文本外框的四个角：
         * 0    左下角 X 位置
         * 1    左下角 Y 位置
         * 2    右下角 X 位置
         * 3    右下角 Y 位置
         * 4    右上角 X 位置
         * 5    右上角 Y 位置
         * 6    左上角 X 位置
         * 7    左上角 Y 位置
         */
        $box = imagettfbbox($fontSize, 0, $fontPath, $text);
        return $box[2] - $box[0];
    }

    /**
     * 计算文字高度
     * @param $fontSize {Number} 像素单位的字体大小
     * @param $fontPath {String} TrueType 字体文件的文件名（可以是 URL）。根据 PHP 所使用的 GD 库版本，可能尝试搜索那些不是以 '/' 开头的文件名并加上 '.ttf' 的后缀并搜索库定义的字体路径
     * @param $text {String} 要度量的字符串
     * @return mixed 返回字体宽度
     */
    static function get_font_height($fontSize, $fontPath, $text)
    {
        // 参数1：像素单位的字体大小。
        // 参数2：将被度量的角度大小。
        // 参数3：TrueType 字体文件的文件名（可以是 URL）。根据 PHP 所使用的 GD 库版本，可能尝试搜索那些不是以 '/' 开头的文件名并加上 '.ttf' 的后缀并搜索库定义的字体路径。
        // 参数4：要度量的字符串。
        /**
         * 返回一个含有 8 个单元的数组表示了文本外框的四个角：
         * 0    左下角 X 位置
         * 1    左下角 Y 位置
         * 2    右下角 X 位置
         * 3    右下角 Y 位置
         * 4    右上角 X 位置
         * 5    右上角 Y 位置
         * 6    左上角 X 位置
         * 7    左上角 Y 位置
         */
        $box = imagettfbbox($fontSize, 0, $fontPath, $text);
        return $box[3] - $box[7];
    }

    /****************************文字区域******************************/
}
